<header>
  <?php $isLandingPage = basename($_SERVER['SCRIPT_NAME']) === 'landing.php'; ?>
  <?php $loginLink = $isLandingPage ? 'login.php' : '#login'; ?>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm py-3">
    <div class="container-fluid px-4">
      <a class="navbar-brand fw-bold text-danger d-flex align-items-center gap-2" href="index.php">
        <i class="fa-solid fa-fire-burner fs-4"></i> Oppa's Samgyupsal
      </a>
      
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link active fw-semibold" aria-current="page" href="#home">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#about">About</a>
          </li>
        </ul>

        <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-3">
          <form class="d-flex gap-2" role="search" onsubmit="event.preventDefault();">
            <input class="form-control me-2" type="search" placeholder="Search menu or offers..." aria-label="Search">
            <button class="btn btn-outline-danger text-white" type="submit">Search</button>
          </form>
          <?php if ($isLandingPage): ?>
            <div class="d-flex align-items-center gap-2 border-start-lg ps-lg-3">
              <span class="text-white fw-bold small" id="navUserDisplay"><?php echo htmlspecialchars($currentUserName ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
              <button id="logoutbutton" class="btn btn-danger btn-sm fw-semibold">
                <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
              </button>
            </div>
          <?php else: ?>
            <div class="d-flex align-items-center gap-2">
              <a class="btn btn-outline-light btn-sm fw-semibold" href="<?php echo htmlspecialchars($loginLink, ENT_QUOTES, 'UTF-8'); ?>">Sign In</a>
              <a class="btn btn-danger btn-sm fw-semibold" href="register.php">Create Account</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </nav>
</header>