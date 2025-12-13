  <!-- Dark Mode Toggle Script -->
  <script>
    // Dark mode toggle - universal handler
    (function() {
      var toggleBtn = document.getElementById('darkModeToggle');
      if (!toggleBtn) return;
      
      toggleBtn.addEventListener('click', function() {
        fetch('toggle_dark_mode.php', { method: 'POST' })
          .then(function(response) { return response.json(); })
          .then(function(data) {
            if (data.success) {
              document.body.classList.toggle('dark-mode', data.darkMode);
              var icon = document.getElementById('darkModeIcon');
              var text = document.getElementById('darkModeText');
              if (icon) icon.textContent = data.darkMode ? '☀️' : '🌙';
              if (text) text.textContent = data.darkMode ? 'Light' : 'Dark';
            }
          })
          .catch(function(err) { console.error('Dark mode toggle failed:', err); });
      });
    })();
    
    // Register Service Worker for PWA
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('sw.js').catch(function() {});
    }
  </script>
  <?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>
