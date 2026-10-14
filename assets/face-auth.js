(function () {
  const MODEL_URL = 'https://justadudewhohacks.github.io/face-api.js/models';
  let modelPromise;
  let detectorOptions;

  async function loadModels() {
    if (typeof faceapi === 'undefined') {
      throw new Error('The face-recognition library could not be loaded. Check your internet connection and reload.');
    }
    if (!modelPromise) {
      detectorOptions = new faceapi.TinyFaceDetectorOptions({
        inputSize: 416,
        scoreThreshold: 0.35
      });
      modelPromise = Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
        faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
        faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
      ]).catch(function (error) {
        modelPromise = null;
        throw new Error('Face models could not be loaded. Check your internet connection and try again.');
      });
    }
    return modelPromise;
  }

  async function startCamera(video) {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      throw new Error('Camera access requires HTTPS or localhost and a supported browser.');
    }
    await loadModels();
    const stream = await navigator.mediaDevices.getUserMedia({
      audio: false,
      video: { facingMode: 'user' }
    });
    video.srcObject = stream;
    video.classList.remove('d-none');
    await video.play();
  }

  function stopCamera(video) {
    if (video.srcObject) {
      video.srcObject.getTracks().forEach(function (track) {
        track.stop();
      });
      video.srcObject = null;
    }
  }

  async function getDescriptor(video) {
    await loadModels();
    const detections = await faceapi
      .detectAllFaces(video, detectorOptions)
      .withFaceLandmarks()
      .withFaceDescriptors();
    if (detections.length !== 1) {
      throw new Error('Make sure exactly one face is clearly visible.');
    }
    return Array.from(detections[0].descriptor);
  }

  async function getDescriptorFromFile(file) {
    const imageUrl = URL.createObjectURL(file);
    const image = new Image();
    image.src = imageUrl;
    try {
      await image.decode();
      if (image.naturalWidth < 200 || image.naturalHeight < 200) {
        throw new Error('The ID image is too small. Upload a clear image at least 200 by 200 pixels.');
      }
      return await getDescriptor(image);
    } catch (error) {
      if (error instanceof Error && error.message.indexOf('exactly one face') !== -1) {
        throw new Error('Could not find exactly one face on this ID. Upload a clear front image with the portrait visible.');
      }
      throw error;
    } finally {
      URL.revokeObjectURL(imageUrl);
    }
  }

  async function capture(video) {
    const descriptor = await getDescriptor(video);
    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
    const blob = await new Promise(function (resolve, reject) {
      canvas.toBlob(function (image) {
        if (image) resolve(image);
        else reject(new Error('Could not capture the selfie. Please try again.'));
      }, 'image/jpeg', 0.88);
    });
    return { descriptor: descriptor, blob: blob };
  }

  function matches(registered, live) {
    if (!Array.isArray(registered) || registered.length !== 128 || live.length !== 128) {
      return false;
    }
    let squaredDistance = 0;
    for (let index = 0; index < registered.length; index += 1) {
      const difference = Number(registered[index]) - Number(live[index]);
      if (!Number.isFinite(difference)) return false;
      squaredDistance += difference * difference;
    }
    return Math.sqrt(squaredDistance) <= 0.5;
  }

  window.FaceAuth = {
    capture: capture,
    getDescriptor: getDescriptor,
    getDescriptorFromFile: getDescriptorFromFile,
    loadModels: loadModels,
    matches: matches,
    startCamera: startCamera,
    stopCamera: stopCamera
  };
}());
