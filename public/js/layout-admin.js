(function () {
  let sidebarState = localStorage.getItem('sidebar-state') || 'open';

  function getSidebar() { return document.getElementById('adminSidebar'); }
  function getOverlay() { return document.getElementById('mobileOverlay'); }

  function toggleSidebar() {
    const sidebar = getSidebar();
    if (!sidebar) return;
    if (sidebar.classList.contains('mini')) {
      sidebar.classList.remove('mini');
      sidebarState = 'open';
    } else {
      sidebar.classList.add('mini');
      sidebarState = 'mini';
    }
    localStorage.setItem('sidebar-state', sidebarState);
  }

  function setDrawer(open) {
    const sidebar = getSidebar();
    const overlay = getOverlay();
    if (!sidebar || !overlay) return;
    sidebar.classList.toggle('mobile-open', open);
    overlay.classList.toggle('active', open);
    document.body.classList.toggle('nav-drawer-open', open);
    document.querySelectorAll('[data-action="toggle-mobile-sidebar"]').forEach((el) => {
      el.setAttribute('aria-expanded', open ? 'true' : 'false');
      el.setAttribute('aria-label', open ? 'Fermer le menu' : 'Ouvrir le menu');
    });
  }

  function toggleMobileSidebar() {
    const sidebar = getSidebar();
    if (!sidebar) return;
    setDrawer(!sidebar.classList.contains('mobile-open'));
  }

  function closeMobileSidebar() {
    setDrawer(false);
  }

  function toggleSubmenu(menuId, element) {
    const submenu = document.getElementById('submenu-' + menuId);
    const parent = element?.closest('li');
    if (!submenu || !parent) return;

    const isOpen = submenu.classList.contains('open');
    const isMobile = window.innerWidth <= 768;

    if (!isMobile) {
      document.querySelectorAll('.sidebar-submenu').forEach((menu) => {
        if (menu !== submenu) {
          menu.classList.remove('open');
          menu.closest('li')?.classList.remove('open');
        }
      });
    }

    submenu.classList.toggle('open', !isOpen);
    parent.classList.toggle('open', !isOpen);
  }

  function toggleNotifications() {
    console.log('Toggle notifications');
  }

  function handleResize() {
    const sidebar = getSidebar();
    if (!sidebar) return;

    if (window.innerWidth <= 768) {
      sidebar.classList.add('mobile-hidden');
      sidebar.classList.remove('mini');
    } else if (window.innerWidth <= 1024) {
      sidebar.classList.add('mini');
      sidebar.classList.remove('mobile-hidden', 'mobile-open');
    } else {
      sidebar.classList.remove('mobile-hidden', 'mobile-open');
      if (sidebarState === 'open') sidebar.classList.remove('mini');
    }

    if (window.innerWidth > 768) setDrawer(false);
  }

  document.addEventListener('DOMContentLoaded', function () {
    const sidebar = getSidebar();
    if (sidebar) {
      if (window.innerWidth <= 1024 && window.innerWidth > 768) {
        sidebar.classList.add('mini');
        sidebarState = 'mini';
      } else if (window.innerWidth <= 768) {
        sidebar.classList.add('mobile-hidden');
        sidebarState = 'mobile-hidden';
      } else if (sidebarState === 'mini') {
        sidebar.classList.add('mini');
      }
    }

    document.querySelectorAll('[data-action="toggle-sidebar"]').forEach((el) => el.addEventListener('click', (e) => { e.preventDefault(); toggleSidebar(); }));
    document.querySelectorAll('[data-action="toggle-mobile-sidebar"]').forEach((el) => el.addEventListener('click', (e) => { e.preventDefault(); toggleMobileSidebar(); }));
    document.querySelectorAll('[data-action="close-mobile-sidebar"]').forEach((el) => el.addEventListener('click', (e) => { e.preventDefault(); closeMobileSidebar(); }));
    document.querySelectorAll('[data-action="toggle-submenu"]').forEach((el) => el.addEventListener('click', (e) => { e.preventDefault(); toggleSubmenu(el.dataset.menu, el); }));
    document.querySelectorAll('[data-action="toggle-notifications"]').forEach((el) => el.addEventListener('click', (e) => { e.preventDefault(); toggleNotifications(); }));
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeMobileSidebar();
  });

  window.addEventListener('resize', handleResize);
})();
