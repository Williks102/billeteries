document.addEventListener('DOMContentLoaded', function() {
    // Toggle entre vue grille et liste
    const gridViewBtn = document.getElementById('grid-view');
    const listViewBtn = document.getElementById('list-view');
    const eventsGrid = document.getElementById('events-grid');
    const eventsList = document.getElementById('events-list');

    gridViewBtn.addEventListener('click', function() {
        gridViewBtn.classList.add('active');
        listViewBtn.classList.remove('active');
        eventsGrid.classList.remove('d-none');
        eventsList.classList.add('d-none');
    });

    listViewBtn.addEventListener('click', function() {
        listViewBtn.classList.add('active');
        gridViewBtn.classList.remove('active');
        eventsList.classList.remove('d-none');
        eventsGrid.classList.add('d-none');
    });

    // Auto-submit du formulaire lors du changement de filtre
    const filterSelects = document.querySelectorAll('select[name="category"], select[name="date_filter"], select[name="sort"]');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            this.form.submit();
        });
    });
});
