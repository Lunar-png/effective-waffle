<script>
  (function () {
    document.addEventListener('DOMContentLoaded', function () {
      const form = document.getElementById('loginForm');
      const alertBox = document.getElementById('loginAlert');
      const emailInput = document.getElementById('loginFaceEmail');
      const cameraPanel = document.getElementById('loginCameraPanel');
      const startCameraButton = document.getElementById('loginStartCamera');
      const verifyFaceButton = document.getElementById('loginVerifyFace');
      const submitButton = document.getElementById('loginSubmitBtn');
      const video = document.getElementById('loginVideo');

      function showMessage(message, isError) {
        alertBox.classList.remove('d-none', 'text-danger', 'text-success');
        alertBox.classList.add(isError ? 'text-danger' : 'text-success');
        alertBox.textContent = message;
      }

      async function postData(url, values) {
        const response = await fetch(url, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
          body: new URLSearchParams(values)
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || 'The sign-in request failed.');
        return result;
      }

      function resetCameraUi() {
        startCameraButton.disabled = false;
        startCameraButton.textContent = 'Start camera';
        verifyFaceButton.disabled = true;
        verifyFaceButton.textContent = 'Verify face & sign in';
        cameraPanel.classList.add('d-none');
        submitButton.classList.remove('d-none');
        submitButton.disabled = false;
        submitButton.textContent = 'Continue to face check';
        FaceAuth.stopCamera(video);
      }

      form.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (!form.reportValidity()) return;

        const email = emailInput.value.trim();
        submitButton.disabled = true;
        submitButton.textContent = 'Checking account…';
        try {
          const result = await postData('phpfile/conditionlogin.php', {
            action: 'challenge',
            username: email
          });
          showMessage(result.message || 'Account ready. Start the camera and verify your face to sign in.', false);
          await FaceAuth.loadModels();
          cameraPanel.classList.remove('d-none');
          submitButton.classList.add('d-none');
          startCameraButton.disabled = false;
          verifyFaceButton.disabled = true;
        } catch (error) {
          showMessage(error.message, true);
          submitButton.disabled = false;
          submitButton.textContent = 'Continue to face check';
        }
      });

      startCameraButton.addEventListener('click', async function () {
        try {
          await FaceAuth.startCamera(video);
          startCameraButton.disabled = true;
          startCameraButton.textContent = 'Camera ready';
          verifyFaceButton.disabled = false;
          verifyFaceButton.textContent = 'Verify face & sign in';
          showMessage('Center your face in the frame, then verify the face.', false);
        } catch (error) {
          showMessage(error.message, true);
        }
      });

      verifyFaceButton.addEventListener('click', async function () {
        verifyFaceButton.disabled = true;
        verifyFaceButton.textContent = 'Checking face…';
        try {
          const liveDescriptor = await FaceAuth.getDescriptor(video);
          const result = await postData('phpfile/conditionlogin.php', {
            action: 'complete',
            face_descriptor: JSON.stringify(liveDescriptor)
          });
          FaceAuth.stopCamera(video);
          showMessage('Face matched. Signing you in…', false);
          setTimeout(function () {
            window.location.href = result.redirect;
          }, 500);
        } catch (error) {
          FaceAuth.stopCamera(video);
          showMessage(error.message, true);
          resetCameraUi();
        }
      });
    });
  }());
</script>
