document.addEventListener('DOMContentLoaded', function() {
    // Prévisualisation de l'image
    const imageInput = document.getElementById('image');
    const imagePreview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');

    imageInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                imagePreview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            imagePreview.style.display = 'none';
        }
    });

    // Validation des heures
    const eventTime = document.getElementById('event_time');
    const endTime = document.getElementById('end_time');

    function validateTimes() {
        if (eventTime.value && endTime.value) {
            if (endTime.value <= eventTime.value) {
                endTime.setCustomValidity('L\'heure de fin doit être après l\'heure de début');
            } else {
                endTime.setCustomValidity('');
            }
        }
    }

    eventTime.addEventListener('change', validateTimes);
    endTime.addEventListener('change', validateTimes);

    // Validation du formulaire
    const form = document.getElementById('eventForm');
    form.addEventListener('submit', function(e) {
        const action = e.submitter.value;
        
        if (action === 'publish') {
            if (!confirm('Êtes-vous sûr de vouloir publier cet événement immédiatement ?')) {
                e.preventDefault();
                return false;
            }
        }

        // Validation côté client
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        form.classList.add('was-validated');
    });

    // Auto-suggestion pour l'adresse (si API disponible)
    const addressInput = document.getElementById('address');
    // Ici vous pourriez intégrer une API de géolocalisation
});
