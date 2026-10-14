<?php
declare(strict_types=1);
require_once __DIR__ . '/authStorage.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    auth_json_response(405, ['message' => 'Use the registration form to create an account.']);
}

$name = trim((string) ($_POST['fullname'] ?? ''));
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$password = (string) ($_POST['password'] ?? '');
$confirmPassword = (string) ($_POST['confirm_password'] ?? '');
$idType = (string) ($_POST['id_type'] ?? '');
$descriptorJson = (string) ($_POST['face_descriptor'] ?? '');
$idDetailsJson = (string) ($_POST['id_details'] ?? '');
$allowedIds = ['PhilID (physical National ID)', 'ePhilID (paper National ID)'];

if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
    auth_json_response(422, ['message' => 'Enter a name between 2 and 100 characters.']);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
    auth_json_response(422, ['message' => 'Enter a valid email address.']);
}
if (strlen($password) < 8 || strlen($password) > 72) {
    auth_json_response(422, ['message' => 'Use a password between 8 and 72 characters.']);
}
if ($password !== $confirmPassword) {
    auth_json_response(422, ['message' => 'Passwords do not match.']);
}
if (!in_array($idType, $allowedIds, true)) {
    auth_json_response(422, ['message' => 'Select PhilID or ePhilID.']);
}
if (($_POST['consent'] ?? '') !== '1') {
    auth_json_response(422, ['message' => 'Consent to local storage is required.']);
}

$details = json_decode($idDetailsJson, true);
if (!is_array($details)) {
    auth_json_response(422, ['message' => 'Review the details from your ID before registering.']);
}
foreach (['full_name', 'date_of_birth', 'sex', 'address'] as $field) {
    if (!isset($details[$field]) || !is_string($details[$field])) {
        auth_json_response(422, ['message' => 'The ID details are incomplete. Review the form and try again.']);
    }
}
$details = [
    'full_name' => trim($details['full_name']),
    'date_of_birth' => trim($details['date_of_birth']),
    'sex' => trim($details['sex']),
    'address' => trim($details['address']),
];
if (mb_strlen($details['full_name']) > 100 || mb_strlen($details['address']) > 300
    || $details['full_name'] !== $name) {
    auth_json_response(422, ['message' => 'Review the name and ID details before registering.']);
}
if ($details['date_of_birth'] !== ''
    && (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $details['date_of_birth'], $dateParts)
        || !checkdate((int) $dateParts[2], (int) $dateParts[3], (int) $dateParts[1]))) {
    auth_json_response(422, ['message' => 'Review the date of birth from your ID.']);
}
if ($details['sex'] !== '' && !in_array($details['sex'], ['Female', 'Male'], true)) {
    auth_json_response(422, ['message' => 'Review the sex field from your ID.']);
}

$descriptor = json_decode($descriptorJson, true);
if (!is_array($descriptor) || count($descriptor) !== 128) {
    auth_json_response(422, ['message' => 'The ID portrait face template is missing. Upload a clear ID image with one visible face.']);
}
foreach ($descriptor as $value) {
    if (!is_numeric($value) || !is_finite((float) $value) || abs((float) $value) > 2) {
        auth_json_response(422, ['message' => 'The ID portrait face template is invalid. Upload the ID image again.']);
    }
}

$idImage = auth_validate_image_upload('id_image');
auth_ensure_storage();

$lock = fopen(auth_storage_path('accounts.lock'), 'c');
if ($lock === false || !flock($lock, LOCK_EX)) {
    error_log('Unable to acquire local account storage lock for registration.');
    auth_json_response(500, ['message' => 'Local account storage is unavailable.']);
}

$accountsPath = auth_storage_path('accounts.json');
$content = is_file($accountsPath) ? file_get_contents($accountsPath) : '{}';
$accounts = $content === false ? null : json_decode($content, true);
if (!is_array($accounts)) {
    flock($lock, LOCK_UN);
    fclose($lock);
    error_log('Local accounts file contains invalid JSON during registration.');
    auth_json_response(500, ['message' => 'Local account storage is damaged.']);
}
if (isset($accounts[$email])) {
    flock($lock, LOCK_UN);
    fclose($lock);
    auth_json_response(409, ['message' => 'An account already exists for this email address.']);
}

$idFilename = auth_store_image($idImage);
$accounts[$email] = [
    'fullname' => $name,
    'email' => $email,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'id_type' => $idType,
    'id_image' => $idFilename,
    'id_details' => $details,
    'face_descriptor' => array_map('floatval', $descriptor),
    'face_reference' => 'uploaded_id_portrait',
    'verification_status' => 'unverified_local_demo',
    'created_at' => gmdate('c'),
];
$json = json_encode($accounts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
$savedBytes = $json === false ? false : file_put_contents($accountsPath, $json, LOCK_EX);
flock($lock, LOCK_UN);
fclose($lock);

if ($savedBytes === false || $savedBytes !== strlen($json)) {
    auth_remove_stored_image($idFilename);
    error_log('Unable to persist a new local account.');
    auth_json_response(500, ['message' => 'Could not save the account. Check local folder permissions.']);
}

auth_json_response(201, ['message' => 'Account created. Use your email and face to sign in.']);
