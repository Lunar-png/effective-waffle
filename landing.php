<?php
require_once __DIR__ . '/phpfile/authStorage.php';
if (empty($_SESSION['user_email']) || empty($_SESSION['fullname'])) {
    header('Location: login.php');
    exit;
}
$currentUserName = (string) $_SESSION['fullname'];
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include 'phpfile/header.php'; ?>
  </head>
  <body>
    <?php include 'phpfile/bg.php'; ?>
    <?php include 'phpfile/navbar.php'; ?>
    <?php include 'phpfile/sections.php'; ?>
    <?php include 'phpfile/modal.php'; ?>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <?php include 'phpfile/landingAuth.php'; ?>
  </body>
</html>
