import { BrowserMultiFormatReader } from '@zxing/library';

const codeReader = new BrowserMultiFormatReader();
console.log('Barcode scanner initialized');

let isScanning = false;
let currentElementID = null;
let videoInputDevices = [];

window.openScannerModal = openScannerModal;
window.closeScannerModal = closeScannerModal;
window.changeCamera = changeCamera;
window.scanFromImage = scanFromImage;
window.requestCameraPermission = requestCameraPermission;

function openScannerModal(id) {
    currentElementID = id;
    // Open the Filament modal
    window.dispatchEvent(new CustomEvent('open-modal', { detail: { id: 'barcode-scanner-modal' } }));
}

function closeScannerModal() {
    // Close the Filament modal
    window.dispatchEvent(new CustomEvent('close-modal', { detail: { id: 'barcode-scanner-modal' } }));
    stopScanning(); // Make sure to stop the camera when the modal closes
}

function startScanner(selectedDeviceId) {
    codeReader.decodeFromVideoDevice(
        selectedDeviceId,
        'scanner',
        (result, err) => {
            const scanArea = document.querySelector('.scan-area');
            if (result) {
                console.log('Result: ' + result.text);
                document.getElementById(currentElementID).value = result.text; // Set barcode value
                document.getElementById(currentElementID).dispatchEvent(new Event('input')); // Trigger for Livewire
                scanArea.style.borderColor = 'green';
                stopScanning(); // Optionally stop scanning after successful read
                closeScannerModal(); // Close the modal after successful scan
            } else {
                scanArea.style.borderColor = 'red';
            }
        },
        {
            timeBetweenDecodingAttempts: 5,
        }
    );
}

function stopScanning() {
    isScanning = false;
    const video = document.getElementById('scanner');
    if (video.srcObject) {
        video.srcObject.getTracks().forEach(track => track.stop());
    }
    video.style.display = 'none';
}

function startCamera(selectedDeviceId = null) {
    // Stop any active scanning before starting a new camera
    stopScanning();

    navigator.mediaDevices.enumerateDevices()
        .then(devices => {
            videoInputDevices = devices.filter(device => device.kind === 'videoinput');

            if (videoInputDevices.length === 0) {
                console.error("No video input devices found.");
                alert("No camera found on this device.");
                return;
            }

            // Default to the first camera if no specific device is selected
            const deviceId = selectedDeviceId || videoInputDevices[0].deviceId;

            navigator.mediaDevices.getUserMedia({
                video: {
                    deviceId: { exact: deviceId },
                    width: { ideal: 640 },
                    height: { ideal: 480 },
                    frameRate: { ideal: 30, max: 60 }
                }
            })
            .then(stream => {
                const video = document.getElementById('scanner');
                video.srcObject = stream;
                video.style.display = 'block';
                startScanner(deviceId); // Start the scanner with the selected camera
            })
            .catch(err => {
                localStorage.removeItem('cameraPermission'); // Delete the entry in localStorage
                console.error("Error accessing the camera: ", err);
                alert("Failed to access the camera. Camera access has been reseted. Please close and then open the modal again.");
            });
        })
        .catch(err => {
            console.error("Error enumerating devices: ", err);
            alert("Unable to access camera devices.");
        });
}


function listCameras() {
    navigator.mediaDevices.enumerateDevices().then(devices => {
        videoInputDevices = devices.filter(device => device.kind === 'videoinput');
        const cameraSelect = document.getElementById('cameraSelect');
        cameraSelect.innerHTML = ''; // Clear existing options

        videoInputDevices.forEach((device, index) => {
            const option = document.createElement('option');
            option.value = device.deviceId;
            option.text = device.label || `Camera ${index + 1}`;
            cameraSelect.appendChild(option);
        });

        // Check whether a camera is stored in localStorage
        const savedCameraId = localStorage.getItem('selectedCameraId');
        const defaultCameraId = savedCameraId || (videoInputDevices.length > 0 ? videoInputDevices[0].deviceId : null);

        if (defaultCameraId) {
            cameraSelect.value = defaultCameraId;
            startCamera(defaultCameraId); // Start with the saved or first camera
        }
    }).catch(err => {
        console.error("Error listing cameras: ", err);
    });
}

function changeCamera() {
    const selectedDeviceId = document.getElementById('cameraSelect').value;
    console.log("Switching to camera with ID:", selectedDeviceId);

    // Speichere die zuletzt ausgewählte Kamera in localStorage
    localStorage.setItem('selectedCameraId', selectedDeviceId);

    // Stop the current camera
    stopScanning();

    // Start the selected camera
    startCamera(selectedDeviceId);
}

function scanFromImage(file) {
    const reader = new FileReader();
    const img = new Image();

    // If the file has been loaded, try to read the barcode
    reader.onload = function (event) {
        img.src = event.target.result;

        img.onload = function () {
            codeReader.decodeFromImageElement(img)
                .then(result => {
                    console.log('Result from image:', result.text);
                    document.getElementById(currentElementID).value = result.text; // Set the barcode value
                    document.getElementById(currentElementID).dispatchEvent(new Event('input')); // Trigger Livewire
                    closeScannerModal(); // Close the modal after a successful scan
                })
                .catch(err => {
                    alert("No barcode found in the uploaded image. Please try another image.");
                });
        };
    };

    // Read the file as a data URL
    reader.readAsDataURL(file);
}

// Listen for modal opening and start camera
window.addEventListener('open-modal', event => {
    console.log(event);
    if (event.detail.id === 'barcode-scanner-modal') {
        console.log("Modal opened, requesting camera permission");

        requestCameraPermission()
            .then((granted) => {
                if (granted) {
                    console.log("Listing cameras and starting the saved one.");
                    listCameras();
                    startCamera();
                } else {
                    console.log("Camera access denied or error occurred.");
                    // Führen Sie hier Code aus, der ausgeführt wird, wenn der Zugriff verweigert wurde oder ein Fehler aufgetreten ist.
                }
            })
            .catch((error) => {
                console.error("An error occurred:", error);
                // Führen Sie hier Code aus, wenn ein Fehler aufgetreten ist.
            });
    }
});

// Listen for modal closing and stop camera
window.addEventListener('close-modal', event => {
    if (event.detail.id === 'barcode-scanner-modal') {
        console.log("Modal closed, stopping camera");
        stopScanning();
    }
});


function requestCameraPermission() {
    return new Promise((resolve, reject) => {
        const permissionStatus = localStorage.getItem('cameraPermission');
        if (permissionStatus === 'granted') {
            console.log("Camera access has already been granted.");
            resolve(true);
        } else {
            navigator.mediaDevices.getUserMedia({ video: true })
                .then(stream => {
                    console.log("Camera access was granted.");
                    localStorage.setItem('cameraPermission', 'granted');
                    resolve(true);
                })
                .catch(err => {
                    console.error("Error requesting camera access:", err);
                    if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                        alert("Camera access was denied. Please check your browser settings and grant permission.");
                        localStorage.removeItem('cameraPermission'); // Delete the entry in localStorage
                    } else if (err.name === 'NotFoundError' || err.name === 'OverconstrainedError') {
                        alert("No valid camera found. Please ensure a camera is connected or installed.");
                    } else {
                        alert("An error occurred: " + err.message);
                    }
                    reject(false);
                });
        }
    });
}
