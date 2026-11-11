(function () {
  function autoHideAlerts() {
    setTimeout(function () {
      const alerts = document.querySelectorAll('.alert.auto-hide');
      alerts.forEach(function (alert) {
        if (window.bootstrap && window.bootstrap.Alert) {
          const bsAlert = new bootstrap.Alert(alert);
          bsAlert.close();
        }
      });
    }, 5000);
  }

  class NotificationSystem {
    constructor() {
      this.container = document.getElementById('toastContainer');
      if (!this.container) this.createContainer();
    }

    createContainer() {
      this.container = document.createElement('div');
      this.container.id = 'toastContainer';
      this.container.className = 'toast-container position-fixed top-0 end-0 p-3';
      this.container.style.zIndex = '9999';
      document.body.appendChild(this.container);
    }

    show(message, type = 'info', options = {}) {
      const config = { duration: 5000, showClose: true, title: this.getDefaultTitle(type), ...options };
      const toast = this.createToast(message, type, config);
      this.container.appendChild(toast);
      requestAnimationFrame(() => { toast.style.display = 'block'; });
      if (config.duration > 0) {
        setTimeout(() => this.hide(toast), config.duration);
      }
      return toast;
    }

    createToast(message, type, config) {
      const toast = document.createElement('div');
      toast.className = `toast-custom ${type}`;
      toast.style.display = 'none';
      const icon = this.getIcon(type);

      // F-3 : title et message sont insérés en TEXTE (textContent), jamais en HTML.
      const header = document.createElement('div');
      header.className = 'toast-header-custom';
      const iconWrap = document.createElement('div');
      iconWrap.className = `toast-icon ${type}`;
      const iconEl = document.createElement('i');
      iconEl.className = icon;
      iconWrap.appendChild(iconEl);
      const titleEl = document.createElement('h6');
      titleEl.className = 'toast-title';
      titleEl.textContent = config.title;
      header.appendChild(iconWrap);
      header.appendChild(titleEl);
      if (config.showClose) {
        const closeEl = document.createElement('button');
        closeEl.type = 'button';
        closeEl.className = 'toast-close';
        closeEl.setAttribute('aria-label', 'Fermer');
        const closeIcon = document.createElement('i');
        closeIcon.className = 'fas fa-times';
        closeEl.appendChild(closeIcon);
        header.appendChild(closeEl);
      }
      const body = document.createElement('div');
      body.className = 'toast-body-custom';
      body.textContent = message;
      toast.appendChild(header);
      toast.appendChild(body);

      if (config.showClose) {
        const closeBtn = toast.querySelector('.toast-close');
        closeBtn?.addEventListener('click', () => this.hide(toast));
      }

      return toast;
    }

    hide(toast) {
      toast.classList.add('hiding');
      setTimeout(() => toast.parentNode?.removeChild(toast), 300);
    }

    getIcon(type) {
      return ({ success: 'fas fa-check', error: 'fas fa-times', warning: 'fas fa-exclamation-triangle', info: 'fas fa-info' })[type] || 'fas fa-info';
    }

    getDefaultTitle(type) {
      return ({ success: 'Succès', error: 'Erreur', warning: 'Attention', info: 'Information' })[type] || 'Information';
    }

    success(message, options = {}) { return this.show(message, 'success', options); }
    error(message, options = {}) { return this.show(message, 'error', options); }
    warning(message, options = {}) { return this.show(message, 'warning', options); }
    info(message, options = {}) { return this.show(message, 'info', options); }
    cartSuccess(message) { return this.show(message, 'success', { title: '🛒 Panier mis à jour', duration: 3000 }); }
  }

  function showBootstrapNotification(message, type = 'info') {
    const alertContainer = document.createElement('div');
    alertContainer.className = 'container mt-3';
    const icon = type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle';
    // F-3 : message inséré en TEXTE (textContent), jamais en HTML.
    const safeType = ['success', 'error', 'warning', 'info'].includes(type) ? type : 'info';
    const alertEl = document.createElement('div');
    alertEl.className = `alert alert-${safeType} alert-dismissible fade show`;
    alertEl.setAttribute('role', 'alert');
    const iconEl = document.createElement('i');
    iconEl.className = `fas fa-${icon} me-2`;
    const textEl = document.createElement('span');
    textEl.textContent = message;
    const closeEl = document.createElement('button');
    closeEl.type = 'button';
    closeEl.className = 'btn-close';
    closeEl.setAttribute('data-bs-dismiss', 'alert');
    alertEl.appendChild(iconEl);
    alertEl.appendChild(textEl);
    alertEl.appendChild(closeEl);
    alertContainer.appendChild(alertEl);
    document.body.insertBefore(alertContainer, document.querySelector('main'));
    setTimeout(() => {
      const alert = alertContainer.querySelector('.alert');
      if (alert && window.bootstrap && window.bootstrap.Alert) {
        new bootstrap.Alert(alert).close();
      }
    }, 5000);
  }

  document.addEventListener('DOMContentLoaded', function () {
    autoHideAlerts();

    window.notifications = new NotificationSystem();
    window.showNotification = function (message, type = 'info', options = {}) {
      return options && Object.keys(options).length ? window.notifications.show(message, type, options) : showBootstrapNotification(message, type);
    };
    window.showSuccessNotification = (message, options = {}) => window.notifications.success(message, options);
    window.showErrorNotification = (message, options = {}) => window.notifications.error(message, options);

    document.addEventListener('cartUpdated', function (e) {
      if (e.detail && e.detail.message) {
        window.notifications.cartSuccess(e.detail.message, e.detail.count);
      }
    });

    const flash = document.getElementById('flash-messages');
    if (flash) {
      const success = flash.dataset.success;
      const error = flash.dataset.error;
      const warning = flash.dataset.warning;
      const info = flash.dataset.info;
      if (success) window.notifications.success(success);
      if (error) window.notifications.error(error);
      if (warning) window.notifications.warning(warning);
      if (info) window.notifications.info(info);
    }
  });
})();
