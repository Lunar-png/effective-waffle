<?php
declare(strict_types=1);
require_once __DIR__ . '/authStorage.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    auth_json_response(405, ['message' => 'Use the sign-in form to continue.']);
}

$action = (string) ($_POST['action'] ?? '');

if ($action === 'challenge') {
    $attempts = (int) ($_SESSION['login_attempts'] ?? 0);
    if ($attempts >= 5) {
        auth_json_response(429, ['message' => 'Too many attempts in this session. Refresh the page and try again later.']);
    }

    $username = strtolower(trim((string) ($_POST['username'] ?? '')));
    $mode = strtolower((string) ($_POST['mode'] ?? 'face'));
    $password = (string) ($_POST['password'] ?? '');

    if (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['login_attempts'] = $attempts + 1;
        auth_json_response(401, ['message' => 'Check the email or create an account.']);
    }

    $accounts = auth_read_accounts();
    $account = $accounts[$username] ?? null;
    if (!is_array($account)) {
        $_SESSION['login_attempts'] = $attempts + 1;
        auth_json_response(401, ['message' => 'Could not find that account. Check the email or create one.']);
    }

    if ($mode === 'password') {
        $storedHash = (string) ($account['password_hash'] ?? '');
        if ($storedHash === '' || !password_verify($password, $storedHash)) {
            $_SESSION['login_attempts'] = $attempts + 1;
            auth_json_response(401, ['message' => 'Email or password is incorrect.']);
        }

        session_regenerate_id(true);
        $_SESSION['user_email'] = $username;
        $_SESSION['fullname'] = $account['fullname'];
        $_SESSION['login_attempts'] = 0;
        auth_json_response(200, ['redirect' => 'landing.php']);
    }

    if (!isset($account['face_descriptor']) || !is_array($account['face_descriptor']) || count($account['face_descriptor']) !== 128) {
        error_log('Account has no valid face template: ' . $username);
        auth_json_response(500, ['message' => 'This account has no valid face template. Please register again.']);
    }

    unset($_SESSION['user_email'], $_SESSION['fullname']);
    $_SESSION['pending_login_email'] = $username;
    $_SESSION['pending_login_at'] = time();
    auth_json_response(200, ['message' => 'Account found. Complete the face check to sign in.']);
}

if ($action === 'complete') {
    $email = strtolower(trim((string) ($_POST['username'] ?? ($_SESSION['pending_login_email'] ?? ''))));
    $startedAt = (int) ($_SESSION['pending_login_at'] ?? 0);
    if ($email === '' || $startedAt < time() - 300) {
        unset($_SESSION['pending_login_email'], $_SESSION['pending_login_at']);
        auth_json_response(401, ['message' => 'Sign-in check expired or failed. Enter your email and try again.']);
    }

    $accounts = auth_read_accounts();
    $account = $accounts[$email] ?? null;
    $liveDescriptor = json_decode((string) ($_POST['face_descriptor'] ?? ''), true);
    if (!is_array($account) || !isset($account['fullname'], $account['face_descriptor'])
        || !is_array($account['face_descriptor']) || count($account['face_descriptor']) !== 128) {
        unset($_SESSION['pending_login_email'], $_SESSION['pending_login_at']);
        auth_json_response(401, ['message' => 'Account not found. Please sign in again.']);
    }
    if (!is_array($liveDescriptor) || count($liveDescriptor) !== 128) {
        unset($_SESSION['pending_login_email'], $_SESSION['pending_login_at']);
        auth_json_response(422, ['message' => 'The face check returned invalid data. Enter your email and try again.']);
    }

    $squaredDistance = 0.0;
    foreach ($account['face_descriptor'] as $index => $storedValue) {
        if (!is_numeric($storedValue) || !isset($liveDescriptor[$index]) || !is_numeric($liveDescriptor[$index])
            || !is_finite((float) $storedValue) || !is_finite((float) $liveDescriptor[$index])) {
            unset($_SESSION['pending_login_email'], $_SESSION['pending_login_at']);
            auth_json_response(422, ['message' => 'The face check returned invalid data. Enter your email and try again.']);
        }
        $difference = (float) $storedValue - (float) $liveDescriptor[$index];
        $squaredDistance += $difference * $difference;
    }

    unset($_SESSION['pending_login_email'], $_SESSION['pending_login_at']);
    if (sqrt($squaredDistance) > 0.5) {
        $_SESSION['login_attempts'] = (int) ($_SESSION['login_attempts'] ?? 0) + 1;
        if ($_SESSION['login_attempts'] >= 5) {
            auth_json_response(429, ['message' => 'Too many sign-in attempts in this session. Try again later.']);
        }
        auth_json_response(401, ['message' => 'Face did not match. Enter your email and try again in good lighting.']);
    }

    session_regenerate_id(true);
    $_SESSION['user_email'] = $email;
    $_SESSION['fullname'] = $account['fullname'];
    $_SESSION['login_attempts'] = 0;
    auth_json_response(200, ['redirect' => 'landing.php']);
}

if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'],
        ]);
    }
    session_destroy();
    auth_json_response(200, ['redirect' => 'login.php']);
}

auth_json_response(400, ['message' => 'Unknown sign-in action.']);
