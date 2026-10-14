<main class="container min-vh-100 d-fl
    ex justify-content-center align-items-center py-5 px-3">
      <section class="card auth-card p-4 p-sm-5 border-0">
        
        <header class="text-center mb-4">
          <span class="badge background-danger background-opacity-10 text-danger px-3 py-2 rounded-pill fw-bold mb-2">
            <i class="fa-solid fa-utensils me-1"></i> Oppa's Samgyupsal
          </span>
          <h1 class="h2 fw-bold text-dark mb-1">Create Account</h1>
          <p class="text-muted small">Join us and claim VIP member rewards</p>
        </header>

        <form id="registerForm" action="#" method="POST" novalidate>
          
          <div class="mb-3">
            <input type="text" id="regName" name="fullname" class="form-control form-control-custom" placeholder="Name" autocomplete="name">
            <label for="regName" class="field-label-below">
              <i class="fa-regular fa-user me-1" aria-hidden="true"></i> Full Name
            </label>
          </div>

          <div class="mb-3">
            <input type="email" id="regEmail" name="email" class="form-control form-control-custom" placeholder="user@example.com" autocomplete="email">
            <label for="regEmail" class="field-label-below">
              <i class="fa-regular fa-envelope me-1" aria-hidden="true"></i> Email Address
            </label>
          </div>

          <div class="mb-3">
            <input type="password" id="regPassword" name="password" class="form-control form-control-custom" placeholder="Minimum 8 characters" autocomplete="new-password">
            <label for="regPassword" class="field-label-below">
              <i class="fa-solid fa-lock me-1" aria-hidden="true"></i> Password
            </label>
          </div>

          <div class="mb-3">
            <input type="password" id="regPasswordConfirm" name="confirm_password" class="form-control form-control-custom" placeholder="Re-enter password" autocomplete="new-password">
            <label for="regPasswordConfirm" class="field-label-below">
              <i class="fa-solid fa-shield-halved me-1" aria-hidden="true"></i> Confirm Password
            </label>
          </div>

          <div class="mb-3">
            <div class="form-check">
              <input class="form-check-input me-2" type="checkbox" id="regTerms" name="terms">
              <label class="form-check-label text-secondary small" for="regTerms">
                I agree to the <a href="#terms" class="text-dark fw-semibold">Terms of Service</a> & <a href="#privacy" class="text-dark fw-semibold">Privacy Policy</a>
              </label>
            </div>
          </div>

          <p id="regAlert" class="small fw-semibold text-center mb-3 d-none"></p>

          <button type="submit" class="button button-gradient-success button-lg w-100 py-3 rounded-3 fw-bold mb-3">
            Create Account <i class="fa-solid fa-user-plus ms-2" aria-hidden="true"></i>
          </button>
        </form>

        <footer class="text-center pt-3 border-top">
          <p class="text-muted small mb-0">
            Already have an account? 
            <a href="index.php" class="auth-switch-link">Sign In</a>
          </p>
        </footer>

      </section>
    </main>