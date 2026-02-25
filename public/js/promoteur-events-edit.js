document.addEventListener('DOMContentLoaded', function() {
    // Aperçu de l'image avant upload
    const imageInput = document.getElementById('image');
    const preview = document.getElementById('preview');
    const imagePreview = document.getElementById('imagePreview');
    
    imageInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
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
});
