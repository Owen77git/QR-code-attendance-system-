
    // Hamburger toggle
    document.getElementById("hamburger").onclick = () => {
      document.getElementById("sidebar").classList.toggle("show");
    };

    // QR Scanner
    const startScanBtn = document.getElementById("startScan");
    const resultElem = document.getElementById("scanResult");
    const videoElem = document.getElementById("qr-video");

    startScanBtn.addEventListener("click", () => {
      const html5QrCode = new Html5Qrcode("qr-video");
      videoElem.style.display = "block";
      html5QrCode.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: 250 },
        qrCodeMessage => {
          resultElem.innerText = `✅ Attendance marked: ${qrCodeMessage}`;
          html5QrCode.stop();
          videoElem.style.display = "none";
        }
      ).catch(err => {
        resultElem.innerText = "❌ Error starting camera: " + err;
      });
    });

    // Manual entry
    document.getElementById("submitCode").addEventListener("click", () => {
      const code = document.getElementById("manualCode").value;
      document.getElementById("manualResult").innerText = 
        code ? `✅ Attendance marked with code: ${code}` 
             : "❌ Please enter a valid code.";
    });
