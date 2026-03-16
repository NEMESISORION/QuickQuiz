/**
 * QuickQuiz - Common JavaScript Functions
 * Shared functionality across all pages
 */

const QuickQuiz = {
  /**
   * Initialize dark mode toggle
   */
  initDarkMode: function() {
    const toggleBtn = document.getElementById('darkModeToggle');
    if (!toggleBtn) return;
    
    toggleBtn.addEventListener('click', function() {
      fetch('toggle_dark_mode.php', { method: 'POST' })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            document.body.classList.toggle('dark-mode', data.darkMode);
            const icon = document.getElementById('darkModeIcon');
            const text = document.getElementById('darkModeText');
            if (icon) icon.textContent = data.darkMode ? '☀️' : '🌙';
            if (text) text.textContent = data.darkMode ? 'Light' : 'Dark';
          }
        })
        .catch(err => console.error('Dark mode toggle failed:', err));
    });
  },

  /**
   * Animate stat numbers on load
   */
  animateNumbers: function() {
    document.querySelectorAll('.stat-value, .welcome-stat .value').forEach(el => {
      const target = parseInt(el.textContent);
      if (!isNaN(target) && target > 0) {
        let current = 0;
        const increment = target / 30;
        const timer = setInterval(() => {
          current += increment;
          if (current >= target) {
            el.textContent = target;
            clearInterval(timer);
          } else {
            el.textContent = Math.floor(current);
          }
        }, 30);
      }
    });
  },

  /**
   * Copy text to clipboard
   */
  copyToClipboard: function(text, successCallback) {
    navigator.clipboard.writeText(text).then(() => {
      if (successCallback) successCallback();
    }).catch(err => {
      // Fallback for older browsers
      const textarea = document.createElement('textarea');
      textarea.value = text;
      document.body.appendChild(textarea);
      textarea.select();
      document.execCommand('copy');
      document.body.removeChild(textarea);
      if (successCallback) successCallback();
    });
  },

  /**
   * Show toast notification
   */
  showToast: function(message, type = 'success', duration = 3000) {
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.innerHTML = message;
    toast.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 1rem 1.5rem;
      border-radius: 12px;
      background: ${type === 'success' ? 'linear-gradient(135deg, #22c55e 0%, #16a34a 100%)' : 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)'};
      color: white;
      font-weight: 600;
      box-shadow: 0 10px 40px rgba(0,0,0,0.2);
      z-index: 9999;
      animation: toastEnter 0.3s ease;
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
      toast.style.animation = 'toastExit 0.3s ease forwards';
      setTimeout(() => toast.remove(), 300);
    }, duration);
  },

  /**
   * Confirm dialog
   */
  confirm: function(message) {
    return window.confirm(message);
  },

  /**
   * Format time in MM:SS
   */
  formatTime: function(seconds) {
    const mm = Math.floor(seconds / 60);
    let ss = seconds % 60;
    ss = ss < 10 ? '0' + ss : ss;
    return mm + ':' + ss;
  },

  /**
   * Register service worker
   */
  registerSW: function() {
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('sw.js')
        .then(() => console.log('SW registered'))
        .catch(() => {});
    }
  },

  /**
   * Initialize all common features
   */
  init: function() {
    this.initDarkMode();
    this.animateNumbers();
    this.registerSW();
  }
};

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => QuickQuiz.init());
