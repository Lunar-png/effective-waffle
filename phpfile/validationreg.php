<script>
  document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('registerForm');
    const alertBox = document.getElementById('regAlert');
    const idInput = document.getElementById('regIdImage');
    const idReadPanel = document.getElementById('idReadPanel');
    const idReadStatus = document.getElementById('idReadStatus');
    const idReadProgress = document.getElementById('idReadProgress');
    const qrStatus = document.getElementById('qrStatus');
    const qrDetails = document.getElementById('qrDetails');
    const qrScanned = document.getElementById('qrScanned');
    const qrReviewPanel = document.getElementById('qrReviewPanel');
    const qrReviewConfirmed = document.getElementById('qrReviewConfirmed');
    const idDetails = {
      full_name: document.getElementById('regName'),
      date_of_birth: document.getElementById('regBirthDate'),
      sex: document.getElementById('regSex'),
      address: document.getElementById('regAddress')
    };
    let faceDescriptor = null;
    let previewUrl = null;
    let activeQrScanner = null;

    function showMessage(message, isError) {
      alertBox.classList.remove('d-none', 'text-danger', 'text-success');
      alertBox.classList.add(isError ? 'text-danger' : 'text-success');
      alertBox.textContent = message;
    }

    function applyOcrDetails(details) {
      Object.keys(idDetails).forEach(function (key) {
        const input = idDetails[key];
        const value = details[key] || '';
        if (value && (!input.value || input.dataset.ocrFilled === 'true')) {
          input.value = value;
          input.dataset.ocrFilled = 'true';
        }
      });
    }

    function flattenObject(value, result, depth) {
      if (!value || typeof value !== 'object' || depth > 3) return;
      if (Array.isArray(value)) {
        value.forEach(function (item) {
          flattenObject(item, result, depth + 1);
        });
        return;
      }
      Object.keys(value).forEach(function (key) {
        const item = value[key];
        if (item && typeof item === 'object') {
          flattenObject(item, result, depth + 1);
        } else if ((typeof item === 'string' || typeof item === 'number') && String(item).trim()) {
          result[key.toLowerCase().replace(/[^a-z]/g, '')] = String(item).trim().slice(0, 300);
        }
      });
    }

    function decodeReadablePayload(text) {
      let value = text.trim();
      const parts = value.split('.');
      if (parts.length === 3) {
        try {
          let base64 = parts[1].replace(/-/g, '+').replace(/_/g, '/');
          base64 += '='.repeat((4 - (base64.length % 4)) % 4);
          const bytes = Uint8Array.from(atob(base64), function (character) {
            return character.charCodeAt(0);
          });
          value = new TextDecoder().decode(bytes);
        } catch (error) {
          return null;
        }
      }
      try {
        return JSON.parse(value);
      } catch (error) {
        return null;
      }
    }

    function normalizeQrDate(value) {
      const isoDate = value.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
      if (isoDate) return isoDate[1] + '-' + isoDate[2].padStart(2, '0') + '-' + isoDate[3].padStart(2, '0');
      const usDate = value.match(/^(\d{1,2})[/.](\d{1,2})[/.](\d{4})$/);
      if (usDate) return usDate[3] + '-' + usDate[1].padStart(2, '0') + '-' + usDate[2].padStart(2, '0');
      return '';
    }

    function renderQrDetails(decodedText) {
      qrDetails.replaceChildren();
      const payload = decodeReadablePayload(decodedText);
      if (!payload || typeof payload !== 'object' || Array.isArray(payload)) {
        const note = document.createElement('p');
        note.textContent = 'The QR was readable, but it does not contain locally readable profile details. Protected QR data needs an authorized PhilSys service; it cannot be verified here.';
        qrDetails.append(note);
        qrDetails.classList.remove('d-none');
        return false;
      }

      const fields = {};
      flattenObject(payload, fields, 0);
      const first = fields.firstname || fields.fname || fields.givenname || fields.givennameen || fields.first;
      const middle = fields.middlename || fields.mname || fields.middle;
      const last = fields.lastname || fields.lname || fields.familyname || fields.surname;
      const fullName = fields.fullname || fields.fullnameen || fields.name ||
        [first, middle, last].filter(Boolean).join(' ');
      const birthDate = fields.dateofbirth || fields.birthdate || fields.dob || '';
      const sex = fields.sex || fields.gender || '';
      const address = fields.address || fields.residentialaddress || fields.presentaddress || fields.permanentaddress || '';
      const pcn = fields.pcn || fields.philsyscardnumber || '';
      const details = [];
      if (fullName) details.push(['Name', fullName]);
      if (birthDate) details.push(['Date of birth', birthDate]);
      if (sex) details.push(['Sex', sex]);
      if (address) details.push(['Address', address]);
      if (pcn) details.push(['PCN', pcn]);

      if (details.length === 0) {
        const note = document.createElement('p');
        note.textContent = 'The QR was readable, but no supported profile fields could be identified. No verification was performed.';
        qrDetails.append(note);
        qrDetails.classList.remove('d-none');
        return false;
      }

      const heading = document.createElement('p');
      heading.className = 'qr-details-heading';
      heading.textContent = 'Readable QR fields · UNVERIFIED';
      qrDetails.append(heading);
      details.forEach(function (detail) {
        const row = document.createElement('p');
        const label = document.createElement('strong');
        label.textContent = detail[0] + ': ';
        row.append(label, document.createTextNode(detail[1]));
        qrDetails.append(row);
      });
      const note = document.createElement('p');
      note.className = 'mt-2 mb-0';
      note.textContent = 'This only decodes readable QR data; it does not authenticate the signature or verify the ID with PSA/PhilSys. The QR and PCN are not saved.';
      qrDetails.append(note);
      qrDetails.classList.remove('d-none');

      const qrValues = {
        full_name: fullName,
        date_of_birth: normalizeQrDate(birthDate),
        sex: /^(female|f|babae)$/i.test(sex) ? 'Female' : (/^(male|m|lalaki)$/i.test(sex) ? 'Male' : ''),
        address: address.slice(0, 300)
      };
      Object.keys(qrValues).forEach(function (key) {
        if (qrValues[key]) {
          idDetails[key].value = qrValues[key].slice(0, key === 'full_name' ? 100 : 300);
          idDetails[key].dataset.ocrFilled = 'true';
        }
      });
      return true;
    }

    async function clearQrScanner(scanner) {
      if (!scanner) return;
      try {
        await scanner.stop();
      } catch (error) {
        // The QR reader can already be stopped after reading a code or image.
      }
      try {
        await scanner.clear();
      } catch (error) {
        // A reader that did not start has nothing to clear.
      }
      if (activeQrScanner === scanner) activeQrScanner = null;
    }

    async function acceptQr(decodedText) {
      qrScanned.value = '';
      qrReviewConfirmed.checked = false;
      qrReviewPanel.classList.add('d-none');
      qrStatus.classList.remove('is-success', 'is-error');
      if (renderQrDetails(decodedText)) {
        qrScanned.value = 'decoded_unverified';
        qrReviewPanel.classList.remove('d-none');
        qrStatus.textContent = 'Readable profile details filled in. Review them below; the ID remains unverified.';
        qrStatus.classList.add('is-success');
      } else {
        qrStatus.textContent = 'QR detected, but supported profile details could not be read. No verification was performed.';
        qrStatus.classList.add('is-error');
      }
      if (activeQrScanner) await clearQrScanner(activeQrScanner);
    }

    function resetQrReview(message) {
      qrScanned.value = '';
      qrReviewConfirmed.checked = false;
      qrReviewPanel.classList.add('d-none');
      qrDetails.replaceChildren();
      qrDetails.classList.add('d-none');
      qrStatus.classList.remove('is-success', 'is-error');
      qrStatus.textContent = message;
    }

    document.getElementById('qrDemoButton').addEventListener('click', function () {
      const samplePayload = {
        pcn: '1234-5678-9012-3456',
        fname: 'JUAN',
        mname: 'SILVA',
        lname: 'DELA CRUZ',
        dob: '1998-05-21',
        sex: 'Male',
        address: '123 Demo Street, Quezon City'
      };
      acceptQr(JSON.stringify(samplePayload));
    });

    document.getElementById('qrStartButton').addEventListener('click', async function () {
      const button = this;
      if (typeof Html5Qrcode === 'undefined') {
        qrStatus.textContent = 'The QR scanner could not be loaded. Check your internet connection and reload.';
        qrStatus.classList.add('is-error');
        return;
      }
      button.disabled = true;
      resetQrReview('Starting camera…');
      try {
        const scanner = new Html5Qrcode('qrReader');
        activeQrScanner = scanner;
        await scanner.start(
          { facingMode: 'environment' },
          { fps: 10, qrbox: { width: 240, height: 240 } },
          acceptQr,
          function () {}
        );
        qrStatus.textContent = 'Point your camera at the ID QR code.';
      } catch (error) {
        if (activeQrScanner) await clearQrScanner(activeQrScanner);
        qrStatus.textContent = 'Could not start the camera. Check browser permission, or choose a QR image instead.';
        qrStatus.classList.add('is-error');
      } finally {
        button.disabled = false;
      }
    });

    document.getElementById('qrFile').addEventListener('change', async function () {
      const file = this.files[0];
      if (!file) return;
      if (!file.type.startsWith('image/')) {
        qrStatus.textContent = 'Choose an image file containing the ID QR code.';
        qrStatus.classList.add('is-error');
        this.value = '';
        return;
      }
      if (file.size > 10 * 1024 * 1024) {
        qrStatus.textContent = 'The QR image must be no larger than 10 MB.';
        qrStatus.classList.add('is-error');
        this.value = '';
        return;
      }
      if (typeof Html5Qrcode === 'undefined') {
        qrStatus.textContent = 'The QR scanner could not be loaded. Check your internet connection and reload.';
        qrStatus.classList.add('is-error');
        return;
      }

      resetQrReview('Reading QR image…');
      if (activeQrScanner) await clearQrScanner(activeQrScanner);
      const scanner = new Html5Qrcode('qrReader');
      try {
        const decodedText = await scanner.scanFile(file, true);
        await acceptQr(decodedText);
      } catch (error) {
        qrStatus.textContent = 'No readable QR found. Use a sharp, well-lit photo of the QR code.';
        qrStatus.classList.add('is-error');
      } finally {
        await clearQrScanner(scanner);
        this.value = '';
      }
    });

    function validatePasswordConfirmation() {
      const password = document.getElementById('regPassword');
      const confirmation = document.getElementById('regPasswordConfirm');
      confirmation.setCustomValidity(
        confirmation.value && confirmation.value !== password.value ? 'Passwords do not match.' : ''
      );
    }
    document.getElementById('regPassword').addEventListener('input', validatePasswordConfirmation);
    document.getElementById('regPasswordConfirm').addEventListener('input', validatePasswordConfirmation);

    idInput.addEventListener('change', async function () {
      faceDescriptor = null;
      idReadProgress.textContent = '';
      resetQrReview('No QR read yet. Scan the QR on the selected ID to continue.');
      const file = idInput.files[0];
      if (!file) {
        idReadPanel.classList.add('d-none');
        return;
      }
      if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
        idInput.setCustomValidity('Choose a JPG, PNG, or WEBP image no larger than 5 MB.');
        idInput.reportValidity();
        idInput.setCustomValidity('');
        idInput.value = '';
        idReadPanel.classList.add('d-none');
        return;
      }

      if (previewUrl) URL.revokeObjectURL(previewUrl);
      previewUrl = URL.createObjectURL(file);
      const preview = document.getElementById('regIdPreview');
      preview.src = previewUrl;
      idReadPanel.classList.remove('d-none');
      idReadStatus.textContent = 'Preparing to read your ID…';
      idReadProgress.textContent = 'Analyzing the uploaded image. Your image is sent only to this local PHP application; OCR models are loaded from the CDN.';

      try {
        const results = await Promise.allSettled([
          IdReader.readId(file, function (percentage) {
            idReadStatus.textContent = 'Reading ID text… ' + percentage + '%';
          }),
          FaceAuth.getDescriptorFromFile(file)
        ]);
        const ocrResult = results[0];
        const faceResult = results[1];

        if (ocrResult.status === 'fulfilled') {
          const details = ocrResult.value;
          applyOcrDetails(details);
          idReadStatus.textContent = details.full_name
            ? 'ID text read. Check the suggested details below.'
            : 'ID image read, but the name was not clear enough to extract. Enter the details below.';
          idReadProgress.textContent = 'Review the prefilled fields against your ID. OCR is an estimate and does not verify the ID.';
        } else {
          idReadStatus.textContent = 'Could not read the ID text. You can enter its details manually.';
          idReadProgress.textContent = ocrResult.reason.message;
        }

        if (faceResult.status === 'fulfilled') {
          faceDescriptor = faceResult.value;
          idReadStatus.textContent += ' Face portrait is ready as the sign-in reference.';
        } else {
          faceDescriptor = null;
          idReadStatus.textContent += ' ' + faceResult.reason.message;
        }
      } catch (error) {
        idReadStatus.textContent = error.message;
      }
    });

    form.addEventListener('submit', async function (event) {
      event.preventDefault();
      form.classList.add('was-validated');
      validatePasswordConfirmation();

      if (!idInput.files.length || !faceDescriptor) {
        showMessage('Upload a clear front image of your PhilID/ePhilID with one visible portrait.', true);
        idInput.focus();
        return;
      }
      if (!qrScanned.value) {
        qrStatus.textContent = 'Read the ID QR with the camera, an image, or the sample data before continuing.';
        qrStatus.classList.add('is-error');
        document.getElementById('qrStartButton').focus();
        return;
      }
      if (!qrReviewConfirmed.checked) {
        qrStatus.textContent = 'Review the decoded fields and confirm they match the ID. This does not authenticate the ID.';
        qrStatus.classList.add('is-error');
        qrReviewConfirmed.focus();
        return;
      }
      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      const submitButton = document.getElementById('registerSubmitBtn');
      submitButton.disabled = true;
      submitButton.textContent = 'Creating account…';
      const data = new FormData(form);
      data.set('face_descriptor', JSON.stringify(faceDescriptor));
      data.set('id_details', JSON.stringify({
        full_name: idDetails.full_name.value.trim(),
        date_of_birth: idDetails.date_of_birth.value,
        sex: idDetails.sex.value,
        address: idDetails.address.value.trim()
      }));

      try {
        const response = await fetch('phpfile/registercon.php', { method: 'POST', body: data });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || 'Registration failed.');
        showMessage(result.message, false);
        setTimeout(function () {
          window.location.href = 'login.php?registered=1';
        }, 900);
      } catch (error) {
        showMessage(error.message, true);
        submitButton.disabled = false;
        submitButton.innerHTML = 'Create account <i class="fa-solid fa-arrow-right ms-2" aria-hidden="true"></i>';
      }
    });

    window.addEventListener('beforeunload', function () {
      if (previewUrl) URL.revokeObjectURL(previewUrl);
    });
  });
</script>
