(function () {

  function installLiveOfflineMessage() {
    var videoElement = document.getElementById('videoPlayer');
    if (!videoElement) return;

    var contentType = String(
      videoElement.getAttribute('data-contenttype') ||
      videoElement.getAttribute('data-content_type') ||
      ''
    ).toLowerCase();

    // Extra safety: do absolutely nothing unless this is Live TV.
    if (contentType !== 'livetv') return;

    function replaceErrorMessage(player) {
      setTimeout(function () {
        try {
          var root = player.el();
          if (!root) return;

          var errorMessage = root.querySelector(
            '.vjs-error-display .vjs-modal-dialog-content'
          );

          if (!errorMessage) return;

          errorMessage.innerHTML =
            '<div style="' +
              'display:inline-block;' +
              'background:rgba(0,0,0,0.88);' +
              'padding:20px 32px;' +
              'border-radius:10px;' +
              'color:#ffffff;' +
              'text-align:center;' +
              'line-height:1.35;' +
              'max-width:90%;' +
              'box-sizing:border-box;' +
            '">' +
              '<div style="' +
                'font-size:clamp(22px,3vw,30px);' +
                'font-weight:700;' +
              '">' +
                'This live stream is currently offline.' +
              '</div>' +
              '<div style="' +
                'font-size:clamp(16px,2.2vw,21px);' +
                'font-weight:600;' +
                'margin-top:6px;' +
              '">' +
                'Please check back when the broadcast begins.' +
              '</div>' +
            '</div>';

        } catch (e) {
          console.error('Live offline message error:', e);
        }
      }, 100);
    }

    // Wait for Video.js to finish creating the player.
    var attempts = 0;

    var timer = setInterval(function () {
      attempts++;

      var player = videoElement.player;

      if (player && typeof player.on === 'function') {
        clearInterval(timer);

        player.on('error', function () {
          replaceErrorMessage(player);
        });

        // Also handle an error that happened before this handler attached.
        try {
          if (typeof player.error === 'function' && player.error()) {
            replaceErrorMessage(player);
          }
        } catch (e) {}
      }

      // Stop looking after about 10 seconds.
      if (attempts >= 100) {
        clearInterval(timer);
      }

    }, 100);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', installLiveOfflineMessage);
  } else {
    installLiveOfflineMessage();
  }

})();
