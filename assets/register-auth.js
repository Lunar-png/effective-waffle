(function () {
  document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('registerForm');
    const alertBox = document.getElementById('regAlert');
    const idInput = document.getElementById('regIdImage');
    const idDetailsInput = document.getElementById('idDetails');
    const idPreview = document.getElementById('regIdPreview');
    const idReadPanel = document.getElementById('idReadPanel');
    const idReadStatus = document.getElementById('idReadStatus');
    const idReadProgress = document.getElementById('idReadProgress');
    const faceStatus = document.getElementById('regFaceStatus');
    const startCameraButton = document.getElementById('regStartCamera');
    const compareFaceButton = document.getElementById('regCompareFace');
    const video = document.getElementById('regVideo');
    const qrStatus = document.getElementById('qrStatus');
    const qrDetails = document.getElementById('qrDetails');
    const qrScanned = document.getElementById('qrScanned');
    const qrReviewPanel = document.getElementById('qrReviewPanel');
    const qrReviewConfirmed = document.getElementById('qrReviewConfirmed');
    const nameInput = document.getElementById('regName');
    const birthDateInput = document.getElementById('regBirthDate');
    const sexInput = document.getElementById('regSex');
    const addressInput = document.getElementById('regAddress');
    let idPortraitDescriptor = null;
    let faceVerifiedForCurrentId = false;
    let currentPreviewUrl = null;
    let imageReadSequence = 0;
    let activeQrScanner = null;

    function showMessage(message, isError) {
      alertBox.classList.remove('d-none', 'text-danger', 'text-success');
      alertBox.classList.add(isError ? 'text-danger' : 'text-success');
      alertBox.textContent = message;
    }

    function setFaceStatus(message, isError) {
      faceStatus.textContent = message;
      faceStatus.classList.toggle('text-danger', Boolean(isError));
      faceStatus.classList.toggle('text-success', !isError && faceVerifiedForCurrentId);
    }

    function getCurrentIdDetails() {
      return {
        full_name: nameInput.value.trim(),
        date_of_birth: birthDateInput.value,
        sex: sexInput.value,
        address: addressInput.value.trim()
      };
    }

    function refreshIdDetails() {
      idDetailsInput.value = JSON.stringify(getCurrentIdDetails());
    }

    function populateIdDetails(details, overwrite) {
      if (details.full_name && (overwrite || !nameInput.value.trim())) nameInput.value = details.full_name.slice(0, 100);
      if (details.date_of_birth && (overwrite || !birthDateInput.value)) birthDateInput.value = details.date_of_birth;
      if ((details.sex === 'Female' || details.sex === 'Male') && (overwrite || !sexInput.value)) sexInput.value = details.sex;
      if (details.address && (overwrite || !addressInput.value.trim())) addressInput.value = details.address.slice(0, 300);
      refreshIdDetails();
    }

    function clearFaceSetup() {
      idPortraitDescriptor = null;
      faceVerifiedForCurrentId = false;
      startCameraButton.disabled = true;
      compareFaceButton.disabled = true;
      FaceAuth.stopCamera(video);
      video.classList.add('d-none');
      setFaceStatus('Upload a clear ID image first so we can find its portrait.', false);
    }

    function decodeReadablePayload(text) {
      let value = String(text || '').trim();
      const segments = value.split('.');
      if (segments.length === 3) {
        try {
          let encoded = segments[1].replace(/-/g, '+').replace(/_/g, '/');
          encoded += '='.repeat((4 - encoded.length % 4) % 4);
          const bytes = Uint8Array.from(atob(encoded), function (character) {
            return character.charCodeAt(0);
          });
          value = new TextDecoder().decode(bytes);
        } catch (error) {
          return null;
        }
      }
      try {
        const payload = JSON.parse(value);
        return payload && typeof payload === 'object' && !Array.isArray(payload) ? payload : null;
      } catch (error) {
        return null;
      }
    }

    function flattenObject(value, fields, depth) {
      if (!value || typeof value !== 'object' || depth > 4) return;
      Object.keys(value).forEach(function (key) {
        const item = value[key];
        if (item && typeof item === 'object') {
          flattenObject(item, fields, depth + 1);
        } else if (typeof item === 'string' || typeof item === 'number') {
          fields[key.toLowerCase().replace(/[^a-z]/g, '')] = String(item).trim().slice(0, 300);
        }
      });
    }

    function renderQrDetails(decodedText) {
      qrDetails.replaceChildren();
      const payload = decodeReadablePayload(decodedText);
      if (!payload) {
        qrDetails.classList.add('d-none');
        return false;
      }

      const fields = {};
      flattenObject(payload, fields, 0);
      const first = fields.firstname || fields.fname || fields.givenname || fields.givennameen || fields.first;
      const middle = fields.middlename || fields.mname || fields.middle;
      const last = fields.lastname || fields.lname || fields.familyname || fields.surname;
      const details = {
        full_name: fields.fullname || fields.fullnameen || fields.name || [first, middle, last].filter(Boolean).join(' '),
        date_of_birth: fields.dateofbirth || fields.birthdate || fields.dob || '',
        sex: fields.sex || fields.gender || '',
        address: fields.address || fields.residentialaddress || fields.presentaddress || fields.permanentaddress || ''
      };

      if (details.sex.toLowerCase() === 'm' || details.sex.toLowerCase() === 'male') details.sex = 'Male';
      else if (details.sex.toLowerCase() === 'f' || details.sex.toLowerCase() === 'female') details.sex = 'Female';
      else details.sex = '';

      if (!/^\d{4}-\d{2}-\d{2}$/.test(details.date_of_birth)) {
        details.date_of_birth = '';
      }

      if (!details.full_name && !details.date_of_birth && !details.sex && !details.address) {
        qrDetails.classList.add('d-none');
        return false;
      }

      const visible = [
        ['Name', details.full_name],
        ['Date of birth', details.date_of_birth],
        ['Sex', details.sex],
        ['Address', details.address],
        ['PCN', fields.pcn || fields.philsyscardnumber]
      ].filter(function (item) { return item[1]; });

      const heading = document.createElement('p');
      heading.className = 'qr-details-heading';
      heading.textContent = 'Decoded QR fields · UNVERIFIED';
      qrDetails.append(heading);
      visible.forEach(function (item) {
        const row = document.createElement('p');
        const label = document.createElement('strong');
        label.textContent = item[0] + ': ';
        row.append(label, document.createTextNode(item[1]));
        qrDetails.append(row);
      });

      const note = document.createElement('p');
      note.className = 'mb-0';
      note.textContent = 'QR data is unverified and is used only to prefill the form; it is not sent to the server.';
      qrDetails.append(note);
      qrDetails.classList.remove('d-none');

      populateIdDetails(details, true);
      return true;
    }

    async function clearQrScanner(scanner) {
      if (!scanner) return;
      try {
        await scanner.stop();
      } catch (error) {
        // ignore
      }
      try {
        await scanner.clear();
      } catch (error) {
        // ignore
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
      qrScanned.value = '';
      qrReviewConfirmed.checked = false;
      qrReviewPanel.classList.add('d-none');
      qrStatus.classList.remove('is-success', 'is-error');
      qrStatus.textContent = 'Starting camera…';
      FaceAuth.stopCamera(video);
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
        qrStatus.textContent = 'Could not start the camera. Check browser permission or choose a QR image instead.';
        qrStatus.classList.add('is-error');
      } finally {
        button.disabled = false;
      }
    });

    document.getElementById('qrFile').addEventListener('change', async function () {
      const file = this.files[0];
      if (!file) return;
      if (!file.type.startsWith('image/') || file.size > 10 * 1024 * 1024) {
        qrStatus.textContent = 'Choose an image containing the QR code, no larger than 10 MB.';
        qrStatus.classList.add('is-error');
        this.value = '';
        return;
      }
      if (typeof Html5Qrcode === 'undefined') {
        qrStatus.textContent = 'The QR scanner could not be loaded. Check your internet connection and reload.';
        qrStatus.classList.add('is-error');
        return;
      }
      qrScanned.value = '';
      qrReviewConfirmed.checked = false;
      qrReviewPanel.classList.add('d-none');
      qrStatus.textContent = 'Reading QR image…';
      try {
        if (activeQrScanner) await clearQrScanner(activeQrScanner);
        const scanner = new Html5Qrcode('qrReader');
        const decodedText = await scanner.scanFile(file, true);
        await acceptQr(decodedText);
        await clearQrScanner(scanner);
      } catch (error) {
        qrStatus.textContent = 'No readable QR found. Use a clear, well-lit image of the QR.';
        qrStatus.classList.add('is-error');
      } finally {
        this.value = '';
      }
    });

    idInput.addEventListener('change', async function () {
      const sequence = ++imageReadSequence;
      const file = idInput.files[0];
      idInput.setCustomValidity('');
      idPortraitDescriptor = null;
      faceVerifiedForCurrentId = false;
      startCameraButton.disabled = true;
      compareFaceButton.disabled = true;
      FaceAuth.stopCamera(video);
      video.classList.add('d-none');
      setFaceStatus('Upload a clear ID image first so we can find its portrait.', false);
      idReadProgress.textContent = '';
      idReadPanel.classList.add('d-none');
      idDetailsInput.value = '';
      nameInput.value = '';
      birthDateInput.value = '';
      sexInput.value = '';
      addressInput.value = '';
      if (currentPreviewUrl) {
        URL.revokeObjectURL(currentPreviewUrl);
        currentPreviewUrl = null;
      }
      if (!file) return;
      if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
        idInput.setCustomValidity('Choose a JPG, PNG, or WEBP ID image no larger than 5 MB.');
        idInput.reportValidity();
        idInput.setCustomValidity('');
        idInput.value = '';
        return;
      }

      idReadPanel.classList.remove('d-none');
      currentPreviewUrl = URL.createObjectURL(file);
      idPreview.src = currentPreviewUrl;
      idReadStatus.textContent = 'Reading ID text and detecting the portrait…';
      setFaceStatus('Finding the face portrait on your ID…', false);

      const ocrTask = (async function () {
        try {
          const details = await IdReader.readId(file, function (progress) {
            if (sequence === imageReadSequence) idReadProgress.textContent = 'Reading ID text: ' + progress + '%';
          });
          if (sequence !== imageReadSequence) return;
          populateIdDetails(details, true);
          idReadStatus.textContent = details.full_name
            ? 'ID text read. Review and correct the fields below.'
            : 'No name could be read automatically. Enter your details manually and check them against the ID.';
        } catch (error) {
          if (sequence === imageReadSequence) {
            idReadStatus.textContent = error.message + ' You can still enter the details manually.';
          }
        }
      }());

      try {
        idPortraitDescriptor = await FaceAuth.getDescriptorFromFile(file);
        if (sequence !== imageReadSequence) return;
        startCameraButton.disabled = false;
        setFaceStatus('ID portrait found. Start the camera to compare your live face.', false);
      } catch (error) {
        if (sequence === imageReadSequence) {
          setFaceStatus(error.message, true);
          showMessage(error.message, true);
        }
      }
      await ocrTask;
    });

    startCameraButton.addEventListener('click', async function () {
      startCameraButton.disabled = true;
      try {
        if (activeQrScanner) await clearQrScanner(activeQrScanner);
        await FaceAuth.startCamera(video);
        compareFaceButton.disabled = false;
        setFaceStatus('Camera ready. Center your face and choose Compare face.', false);
      } catch (error) {
        setFaceStatus(error.message, true);
        startCameraButton.disabled = false;
      }
    });

    compareFaceButton.addEventListener('click', async function () {
      compareFaceButton.disabled = true;
      compareFaceButton.textContent = 'Comparing…';
      try {
        const liveDescriptor = await FaceAuth.getDescriptor(video);
        if (!FaceAuth.matches(idPortraitDescriptor, liveDescriptor)) {
          throw new Error('The live face did not match the ID portrait. Check the lighting and ID image, then try again.');
        }
        faceVerifiedForCurrentId = true;
        setFaceStatus('Face matched the ID portrait. You can create your account.', false);
        FaceAuth.stopCamera(video);
        video.classList.add('d-none');
        startCameraButton.disabled = false;
        startCameraButton.textContent = 'Retake comparison';
      } catch (error) {
        setFaceStatus(error.message, true);
        compareFaceButton.disabled = false;
      } finally {
        compareFaceButton.textContent = 'Compare face';
      }
    });

    form.addEventListener('input', refreshIdDetails);
    form.addEventListener('submit', async function (event) {
      event.preventDefault();
      form.classList.add('was-validated');
      if (!idPortraitDescriptor || !faceVerifiedForCurrentId) {
        showMessage('Upload an ID with a clear portrait, then complete the live face comparison.', true);
        return;
      }
      if (qrScanned.value === 'decoded_unverified' && !qrReviewConfirmed.checked) {
        qrStatus.textContent = 'Review the decoded QR fields and confirm them, or skip QR scanning. This does not verify the ID.';
        qrStatus.classList.add('is-error');
        qrReviewConfirmed.focus();
        return;
      }
      if (!form.reportValidity()) return;

      refreshIdDetails();
      const submitButton = document.getElementById('registerSubmitBtn');
      submitButton.disabled = true;
      submitButton.textContent = 'Creating account…';
      const data = new FormData(form);
      data.set('face_descriptor', JSON.stringify(idPortraitDescriptor));
      data.set('id_details', JSON.stringify(getCurrentIdDetails()));

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
      if (currentPreviewUrl) URL.revokeObjectURL(currentPreviewUrl);
    });
  });
}());
