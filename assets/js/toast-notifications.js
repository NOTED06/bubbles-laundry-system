

(function (window) {
  'use strict';

  class ToastNotificationManager {
    constructor() {
      this.container = null;
      this.audioContext = null;
      this.init();
    }

    init() {
      
      if (!document.getElementById('toast-container')) {
        this.container = document.createElement('div');
        this.container.id = 'toast-container';
        this.container.setAttribute('aria-live', 'polite');
        document.body.appendChild(this.container);
      } else {
        this.container = document.getElementById('toast-container');
      }
    }

    
    playTone(type) {
      try {
        if (!this.audioContext) {
          const AudioContext = window.AudioContext || window.webkitAudioContext;
          if (AudioContext) {
            this.audioContext = new AudioContext();
          }
        }
        if (!this.audioContext) return;

        if (this.audioContext.state === 'suspended') {
          this.audioContext.resume();
        }

        const osc = this.audioContext.createOscillator();
        const gain = this.audioContext.createGain();
        osc.connect(gain);
        gain.connect(this.audioContext.destination);

        const now = this.audioContext.currentTime;
        gain.gain.setValueAtTime(0.05, now);

        if (type === 'success') {
          osc.frequency.setValueAtTime(587.33, now); 
          osc.frequency.exponentialRampToValueAtTime(880.00, now + 0.15); 
          gain.gain.exponentialRampToValueAtTime(0.001, now + 0.25);
          osc.start(now);
          osc.stop(now + 0.25);
        } else if (type === 'error') {
          osc.frequency.setValueAtTime(400, now);
          osc.frequency.exponentialRampToValueAtTime(200, now + 0.2);
          gain.gain.exponentialRampToValueAtTime(0.001, now + 0.25);
          osc.start(now);
          osc.stop(now + 0.25);
        }
      } catch (e) {
        
      }
    }

    show(options = {}) {
      if (typeof options === 'string') {
        options = { message: options };
      }

      const defaults = {
        type: 'info', 
        title: '',
        message: '',
        duration: 4000,
        sound: true,
        actionText: null,
        onAction: null
      };

      const settings = Object.assign({}, defaults, options);

      
      const icons = {
        success: 'bi-check-circle-fill text-success',
        error: 'bi-exclamation-triangle-fill text-danger',
        warning: 'bi-exclamation-circle-fill text-warning',
        info: 'bi-info-circle-fill text-info'
      };

      if (!settings.title) {
        settings.title = settings.type.charAt(0).toUpperCase() + settings.type.slice(1);
      }

      const toast = document.createElement('div');
      toast.className = `toast-item toast-${settings.type}`;

      let actionHtml = '';
      if (settings.actionText) {
        actionHtml = `<button class="btn btn-sm btn-outline-primary py-0 px-2 mt-1 toast-action-btn" style="font-size: 0.75rem;">${settings.actionText}</button>`;
      }

      toast.innerHTML = `
        <div class="toast-icon">
          <i class="bi ${icons[settings.type] || icons.info}"></i>
        </div>
        <div class="toast-content">
          <div class="toast-title">${settings.title}</div>
          <div class="toast-message">${settings.message}</div>
          ${actionHtml}
        </div>
        <button type="button" class="toast-close" aria-label="Close">
          <i class="bi bi-x"></i>
        </button>
        <div class="toast-progress">
          <div class="toast-progress-bar" style="transition-duration: ${settings.duration}ms;"></div>
        </div>
      `;

      if (this.container) {
        this.container.appendChild(toast);
      }

      if (settings.sound) {
        this.playTone(settings.type);
      }

      
      requestAnimationFrame(() => {
        toast.classList.add('show');
        const bar = toast.querySelector('.toast-progress-bar');
        if (bar) {
          bar.style.width = '0%';
        }
      });

      
      const removeToast = () => {
        toast.classList.remove('show');
        toast.classList.add('hide');
        setTimeout(() => {
          if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
          }
        }, 350);
      };

      
      let timeoutId = setTimeout(removeToast, settings.duration);

      
      toast.addEventListener('mouseenter', () => clearTimeout(timeoutId));
      toast.addEventListener('mouseleave', () => {
        timeoutId = setTimeout(removeToast, 2000);
      });

      
      const closeBtn = toast.querySelector('.toast-close');
      if (closeBtn) {
        closeBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          clearTimeout(timeoutId);
          removeToast();
        });
      }

      
      if (settings.onAction && toast.querySelector('.toast-action-btn')) {
        toast.querySelector('.toast-action-btn').addEventListener('click', (e) => {
          e.stopPropagation();
          settings.onAction();
          removeToast();
        });
      }

      return {
        close: removeToast
      };
    }

    success(message, title = 'Success', duration = 4000) {
      return this.show({ type: 'success', title, message, duration });
    }

    error(message, title = 'Error', duration = 5000) {
      return this.show({ type: 'error', title, message, duration });
    }

    warning(message, title = 'Warning', duration = 4500) {
      return this.show({ type: 'warning', title, message, duration });
    }

    info(message, title = 'Information', duration = 4000) {
      return this.show({ type: 'info', title, message, duration });
    }
  }

  
  window.Toast = new ToastNotificationManager();

  
  document.addEventListener('DOMContentLoaded', () => {
    const flashSuccess = document.querySelector('meta[name="flash-success"]');
    if (flashSuccess && flashSuccess.content) {
      window.Toast.success(flashSuccess.content);
    }
    const flashError = document.querySelector('meta[name="flash-error"]');
    if (flashError && flashError.content) {
      window.Toast.error(flashError.content);
    }
  });

})(window);
