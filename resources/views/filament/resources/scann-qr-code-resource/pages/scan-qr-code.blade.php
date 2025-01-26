<x-filament-panels::page>
    <script src="{{ asset('build/libs/html5-qrcode/html5-qrcode.min.js') }}"></script>
    <div id="reader" style="width:300px"></div>
    <input type="text" id="qr-value" name="qr_value">

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const html5QrCode = new Html5Qrcode("reader");
            const qrCodeSuccessCallback = (decodedText, decodedResult) => {
                document.getElementById("qr-value").value = decodedText; // Setzen des Textfeldes mit dem Wert des QR-Codes
                // Optional: Stoppen Sie den Scanner, wenn Sie möchten.
                html5QrCode.stop().then((ignore) => {
                    // QR Scanning gestoppt.
                }).catch((err) => {
                    // Stoppen fehlgeschlagen, möglicherweise wegen eines Fehlers beim Zugriff auf die Kamera.
                });
            };
            const config = { fps: 10, qrbox: { width: 250, height: 250 } };
            html5QrCode.start({ facingMode: "environment" }, config, qrCodeSuccessCallback);
        });
        </script>
</x-filament-panels::page>
