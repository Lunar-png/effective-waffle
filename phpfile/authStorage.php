<?php
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Strict',
    'path' => '/',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function auth_storage_path(string $name = ''): string
{
    $base = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage';
    return $name === '' ? $base : $base . DIRECTORY_SEPARATOR . $name;
}

function auth_json_response(int $status, array $payload): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function auth_ensure_storage(): void
{
    $directory = auth_storage_path();
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        error_log('Unable to create local auth storage directory.');
        auth_json_response(500, ['message' => 'Local account storage is unavailable. Check folder permissions.']);
    }
    if (!is_dir(auth_storage_path('uploads')) && !mkdir(auth_storage_path('uploads'), 0700, true) && !is_dir(auth_storage_path('uploads'))) {
        error_log('Unable to create local auth uploads directory.');
        auth_json_response(500, ['message' => 'Local upload storage is unavailable. Check folder permissions.']);
    }
}

function auth_read_accounts(): array
{
    auth_ensure_storage();
    $lock = fopen(auth_storage_path('accounts.lock'), 'c');
    if ($lock === false || !flock($lock, LOCK_SH)) {
        error_log('Unable to read local account storage lock.');
        auth_json_response(500, ['message' => 'Local account storage is unavailable.']);
    }

    $path = auth_storage_path('accounts.json');
    $content = is_file($path) ? file_get_contents($path) : '{}';
    flock($lock, LOCK_UN);
    fclose($lock);
    if ($content === false) {
        error_log('Unable to read local accounts file.');
        auth_json_response(500, ['message' => 'Local account storage could not be read.']);
    }

    $accounts = json_decode($content, true);
    if (!is_array($accounts)) {
        error_log('Local accounts file contains invalid JSON.');
        auth_json_response(500, ['message' => 'Local account storage is damaged.']);
    }
    return $accounts;
}

function auth_validate_image_upload(string $field): array
{
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
        auth_json_response(422, ['message' => 'A required image upload is missing. Choose the image again.']);
    }
    $upload = $_FILES[$field];
    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name'] ?? '')) {
        auth_json_response(422, ['message' => 'One of the image uploads failed. Choose each image again.']);
    }
    if (($upload['size'] ?? 0) < 1 || $upload['size'] > 5 * 1024 * 1024) {
        auth_json_response(422, ['message' => 'Each image must be no larger than 5 MB.']);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($upload['tmp_name']);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    $dimensions = @getimagesize($upload['tmp_name']);
    if (!isset($extensions[$mime]) || $dimensions === false || $dimensions[0] < 200 || $dimensions[1] < 200) {
        auth_json_response(422, ['message' => 'Upload a valid JPG, PNG, or WEBP image at least 200 by 200 pixels.']);
    }

    return [
        'tmp_name' => $upload['tmp_name'],
        'extension' => $extensions[$mime],
    ];
}

function auth_store_image(array $image): string
{
    $filename = bin2hex(random_bytes(24)) . '.' . $image['extension'];
    $destination = auth_storage_path('uploads') . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($image['tmp_name'], $destination)) {
        error_log('Unable to move an uploaded identity image into local storage.');
        auth_json_response(500, ['message' => 'Could not save the uploaded images. Check folder permissions.']);
    }
    return $filename;
}

function auth_remove_stored_image(string $filename): void
{
    $path = auth_storage_path('uploads') . DIRECTORY_SEPARATOR . basename($filename);
    if (is_file($path) && !unlink($path)) {
        error_log('Unable to remove an incomplete identity image upload.');
    }
}
