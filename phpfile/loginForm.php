<main class="container auth-page-content py-4 py-lg-5">
  <section class="auth-layout login-layout">
    <aside class="auth-aside login-aside">
      <a class="auth-brand" href="index.php">
        <span class="auth-brand-icon"><i class="fa-solid fa-fire-burner" aria-hidden="true"></i></span>
        <span>Oppa's Samgyupsal</span>
      </a>
      <p class="auth-eyebrow">MEMBER ACCESS</p>
      <h1>Good food.<br>Good times.</h1>
      <p class="auth-aside-copy">Sign in to continue to your Oppa's Samgyupsal member experience.</p>
      <div class="login-feature">
        <span><i class="fa-solid fa-face-smile" aria-hidden="true"></i></span>
        <div><strong>Face-match sign in</strong><small>Use your registered PhilID portrait as your face reference.</small></div>
      </div>
      <p class="auth-aside-note"><i class="fa-solid fa-circle-info me-2" aria-hidden="true"></i>This local face check cannot detect a photo/video replay or verify an ID with PSA/PhilSys.</p>
    </aside>

    <div class="auth-form-panel login-form-panel">
      <header class="auth-form-heading">
        <p class="auth-eyebrow">WELCOME BACK</p>
        <h2>Sign in with your face</h2>
        <p>Enter your email to find your account, then verify your face.</p>
      </header>

      <div id="registeredNotice" class="alert alert-success small d-none" role="status">
        Account created. Enter your email and verify your face to sign in.
      </div>

      <form id="loginForm" action="#" method="POST" novalidate>
        <div class="mb-3">
          <label for="loginFaceEmail" class="form-label">Email address</label>
          <input type="email" id="loginFaceEmail" name="face_username" class="form-control form-control-custom" placeholder="you@example.com" autocomplete="email" maxlength="254" required>
          <div class="invalid-feedback">Enter a valid email address.</div>
        </div>

        <div id="loginCameraPanel" class="face-capture-panel mb-3 d-none">
          <label class="form-label fw-semibold text-dark" for="loginVideo">Face check</label>
          <video id="loginVideo" class="face-video" autoplay muted playsinline></video>
          <div class="d-flex flex-wrap gap-2 mt-2">
            <button type="button" id="loginStartCamera" class="btn btn-outline-primary">Start camera</button>
            <button type="button" id="loginVerifyFace" class="btn btn-primary" disabled>Verify face &amp; sign in</button>
          </div>
          <small class="text-muted d-block mt-2">Compared with the portrait from your registered ID. Camera face match is not liveness detection.</small>
        </div>

        <p id="loginAlert" class="small fw-semibold text-center mb-3 d-none" role="status" aria-live="polite"></p>
        <button type="submit" id="loginSubmitBtn" class="button button-gradient-success w-100 py-3 rounded-3 fw-bold">
          Continue to face check <i class="fa-solid fa-arrow-right ms-2" aria-hidden="true"></i>
        </button>
      </form>

      <footer class="auth-switch">
        New to Oppa's? <a href="register.php" class="auth-switch-link">Create an account</a>
      </footer>
    </div>
  </section>
</main>
<script>
  if (new URLSearchParams(window.location.search).get('registered') === '1') {
    document.getElementById('registeredNotice').classList.remove('d-none');
  }
</script>
