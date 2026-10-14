<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
      $pageTitles = [
        'index.php' => 'Sign In',
        'login.php' => 'Sign In',
        'register.php' => 'Create Account',
        'landing.php' => 'Home',
      ];
      $pageTitle = $pageTitles[basename($_SERVER['SCRIPT_NAME'])] ?? 'Member Portal';
    ?>
    <title><?php echo htmlspecialchars($pageTitle . " | Oppa's Samgyupsal", ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">