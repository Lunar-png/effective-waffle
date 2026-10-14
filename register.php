<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include 'phpfile/header.php'; ?>
  </head>
  <body class="auth-page">
    <?php include 'phpfile/bg.php'; ?>

    <main class="container auth-page-content py-4 py-lg-5">
      <section class="auth-layout register-layout">
        <aside class="auth-aside register-aside">
          <a class="auth-brand" href="index.php">
            <span class="auth-brand-icon"><i class="fa-solid fa-fire-burner" aria-hidden="true"></i></span>
            <span>Oppa's Samgyupsal</span>
          </a>
          <p class="auth-eyebrow">MEMBERSHIP</p>
          <h1>Join the table.</h1>
          <p class="auth-aside-copy">Create your member account using your PhilID or ePhilID.</p>
          <ol class="auth-steps">
            <li><span>1</span><div><strong>Upload your ID</strong><small>We read available details and use its portrait as the face reference.</small></div></li>
            <li><span>2</span><div><strong>Review your details</strong><small>OCR can make mistakes, so check and correct the filled-in fields.</small></div></li>
            <li><span>3</span><div><strong>Sign in with your face</strong><small>Login compares a live camera image with the uploaded ID portrait.</small></div></li>
          </ol>
          <p class="auth-aside-note"><i class="fa-solid fa-shield-halved me-2" aria-hidden="true"></i>Details are read from the image locally. This does not authenticate the ID with PSA/PhilSys.</p>
        </aside>

        <div class="auth-form-panel">
          <header class="auth-form-heading">
            <p class="auth-eyebrow">CREATE YOUR ACCOUNT</p>
            <h2>Let's get you started</h2>
            <p>Fields marked <span class="required-mark">*</span> are required.</p>
          </header>

          <div class="alert alert-warning small auth-notice" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>
            <strong>Local demo:</strong> OCR is not official PhilSys verification. Images and account data stay in this project's local storage.
          </div>

          <form id="registerForm" action="phpfile/registercon.php" method="POST" enctype="multipart/form-data" novalidate>
            <section class="form-section">
              <div class="form-section-title"><span>01</span><div><h3>Your PhilSys ID</h3><p>Upload a clear front image of your PhilID or ePhilID.</p></div></div>
              <div class="mb-3">
                <label for="regIdType" class="form-label">ID type <span class="required-mark">*</span></label>
                <select id="regIdType" name="id_type" class="form-select form-control-custom" required>
                  <option value="">Select your ID type</option>
                  <option>PhilID (physical National ID)</option>
                  <option>ePhilID (paper National ID)</option>
                </select>
                <div class="invalid-feedback">Select PhilID or ePhilID.</div>
              </div>
              <div class="qr-panel mb-3">
                <p class="form-label mb-1">Read the ID QR code</p>
                <p class="form-text mt-0">Readable QR data can prefill the details below. A decoded QR is not verified with PSA/PhilSys.</p>
                <div id="qrReader" class="qr-reader" aria-label="ID QR camera preview"></div>
                <div class="qr-actions">
                  <button type="button" id="qrStartButton" class="btn btn-primary">
                    <i class="fa-solid fa-camera me-2" aria-hidden="true"></i>Scan with camera
                  </button>
                  <label for="qrFile" class="btn btn-outline-primary mb-0">
                    <i class="fa-solid fa-image me-2" aria-hidden="true"></i>Choose QR image
                  </label>
                  <button type="button" id="qrDemoButton" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-flask me-2" aria-hidden="true"></i>Try sample data
                  </button>
                  <input type="file" id="qrFile" class="visually-hidden" accept="image/*">
                </div>
                <p id="qrStatus" class="qr-status" role="status" aria-live="polite">No QR read yet. Use your camera, choose a QR image, or try the sample data.</p>
                <div id="qrDetails" class="qr-details d-none" aria-live="polite"></div>
                <div id="qrReviewPanel" class="form-check qr-review d-none">
                  <input class="form-check-input" type="checkbox" id="qrReviewConfirmed" name="qr_review_confirmed" value="1">
                  <label class="form-check-label" for="qrReviewConfirmed">I reviewed the decoded details and confirm they match the ID presented. This is a local demo check, not identity authentication.</label>
                </div>
                <input type="hidden" id="qrScanned" name="qr_scanned" value="">
              </div>
              <div class="mb-3">
                <label for="regIdImage" class="form-label">ID image <span class="required-mark">*</span></label>
                <input type="file" id="regIdImage" name="id_image" class="form-control form-control-custom" accept="image/jpeg,image/png,image/webp" required>
                <div class="form-text">JPG, PNG, or WEBP · Up to 5 MB. Avoid glare and keep the whole ID in frame.</div>
                <div class="invalid-feedback">Choose a valid ID image up to 5 MB.</div>
              </div>
              <div class="face-capture-panel mb-3">
                <label class="form-label fw-semibold text-dark" for="regVideo">Face reference</label>
                <video id="regVideo" class="face-video d-none" autoplay muted playsinline></video>
                <div class="d-flex flex-wrap gap-2 mt-2">
                  <button type="button" id="regStartCamera" class="btn btn-outline-primary">Start camera</button>
                  <button type="button" id="regCompareFace" class="btn btn-primary" disabled>Compare face</button>
                </div>
                <p id="regFaceStatus" class="form-text mt-2 mb-0">Upload a clear ID image first so we can find its portrait.</p>
              </div>
              <div id="idReadPanel" class="id-read-panel d-none" aria-live="polite">
                <img id="regIdPreview" class="id-preview" alt="Preview of uploaded ID">
                <div>
                  <p id="idReadStatus" class="id-read-status">Reading ID…</p>
                  <small class="text-muted">Extracted details are suggestions. Review them below; OCR cannot confirm the ID is genuine.</small>
                </div>
              </div>
              <p id="idReadProgress" class="form-text mt-2" role="status" aria-live="polite"></p>
            </section>

            <section class="form-section">
              <div class="form-section-title"><span>02</span><div><h3>Details from your ID</h3><p>Check every field against the uploaded ID. You can correct OCR mistakes.</p></div></div>
              <div class="row g-3">
                <div class="col-12">
                  <label for="regName" class="form-label">Full name <span class="required-mark">*</span></label>
                  <input type="text" id="regName" name="fullname" class="form-control form-control-custom" placeholder="Read from your ID or enter it here" autocomplete="name" minlength="2" maxlength="100" required>
                  <div class="invalid-feedback">Enter your full name (2–100 characters).</div>
                </div>
                <div class="col-12 col-md-6">
                  <label for="regBirthDate" class="form-label">Date of birth</label>
                  <input type="date" id="regBirthDate" name="date_of_birth" class="form-control form-control-custom" autocomplete="bday">
                </div>
                <div class="col-12 col-md-6">
                  <label for="regSex" class="form-label">Sex</label>
                  <select id="regSex" name="sex" class="form-select form-control-custom">
                    <option value="">Not detected</option>
                    <option value="Female">Female</option>
                    <option value="Male">Male</option>
                  </select>
                </div>
                <div class="col-12">
                  <label for="regAddress" class="form-label">Address</label>
                  <textarea id="regAddress" name="address" class="form-control form-control-custom" rows="2" maxlength="300" autocomplete="street-address" placeholder="Read from your ID if detected"></textarea>
                </div>
              </div>
            </section>

            <section class="form-section">
              <div class="form-section-title"><span>03</span><div><h3>Account access</h3><p>Use an email address you can access for your face-match login.</p></div></div>
              <div class="row g-3">
                <div class="col-12">
                  <label for="regEmail" class="form-label">Email address <span class="required-mark">*</span></label>
                  <input type="email" id="regEmail" name="email" class="form-control form-control-custom" placeholder="you@example.com" autocomplete="email" maxlength="254" required>
                  <div class="invalid-feedback">Enter a valid email address.</div>
                </div>
              </div>
            </section>
            <input type="hidden" id="idDetails" name="id_details" value="">

            <div class="form-check auth-consent">
              <input class="form-check-input" type="checkbox" id="regConsent" name="consent" value="1" required>
              <label class="form-check-label" for="regConsent">I consent to storing my ID image, extracted details, and face template in this local demo. I understand this is not official ID verification.</label>
              <div class="invalid-feedback">Consent is required to create an account in this demo.</div>
            </div>

            <p id="regAlert" class="small fw-semibold text-center mb-3 d-none" role="status" aria-live="polite"></p>
            <button type="submit" id="registerSubmitBtn" class="button button-gradient-success w-100 py-3 rounded-3 fw-bold">
              Create account <i class="fa-solid fa-arrow-right ms-2" aria-hidden="true"></i>
            </button>
          </form>

          <footer class="auth-switch">
            Already have an account? <a href="login.php" class="auth-switch-link">Sign in</a>
          </footer>
        </div>
      </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5.1.1/dist/tesseract.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.15/dist/face-api.js"></script>
    <script src="assets/face-auth.js"></script>
    <script src="assets/id-reader.js"></script>
    <script src="assets/register-auth.js"></script>
  </body>
</html>
