document.addEventListener('DOMContentLoaded', function() {
    // Génération automatique du slug
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');
    
    titleInput.addEventListener('input', function() {
        if (!slugInput.dataset.manual) {
            const slug = this.value
                .toLowerCase()
                .replace(/[^\w ]+/g, '')
                .replace(/ +/g, '-')
                .substring(0, 50);
            slugInput.value = slug;
            
            // Mettre à jour l'aperçu Google
            updateGooglePreview();
        }
    });
    
    slugInput.addEventListener('input', function() {
        this.dataset.manual = 'true';
        updateGooglePreview();
    });
    
    // Affichage conditionnel de l'ordre du menu
    const showInMenuCheckbox = document.getElementById('show_in_menu');
    const menuOrderGroup = document.getElementById('menu_order_group');
    
    showInMenuCheckbox.addEventListener('change', function() {
        menuOrderGroup.style.display = this.checked ? 'block' : 'none';
    });
    
    // Aperçu en temps réel du contenu
    const contentTextarea = document.getElementById('content');
    const previewContent = document.getElementById('preview-content');
    
    contentTextarea.addEventListener('input', function() {
        previewContent.innerHTML = this.value || '<p class="text-muted">Tapez du contenu pour voir l\'aperçu...</p>';
    });
    
    // Mise à jour de l'aperçu Google
    function updateGooglePreview() {
        const title = document.getElementById('meta_title').value || titleInput.value || 'Titre de votre page';
        const slug = slugInput.value || 'page';
        const description = document.getElementById('meta_description').value || 
                          document.getElementById('excerpt').value || 
                          'Description de votre page qui apparaîtra dans les résultats de recherche Google.';
        
        document.getElementById('google-title-preview').textContent = title.substring(0, 60);
        document.getElementById('google-url-preview').textContent = slug;
        document.getElementById('google-description-preview').textContent = description.substring(0, 160);
    }
    
    // Écouter les changements des champs SEO
    document.getElementById('meta_title').addEventListener('input', updateGooglePreview);
    document.getElementById('meta_description').addEventListener('input', updateGooglePreview);
    document.getElementById('excerpt').addEventListener('input', updateGooglePreview);
    
    // Compteur de caractères
    function addCharCounter(elementId, maxLength) {
        const element = document.getElementById(elementId);
        const counter = document.createElement('small');
        counter.className = 'text-muted float-end';
        element.parentNode.appendChild(counter);
        
        function updateCounter() {
            const remaining = maxLength - element.value.length;
            counter.textContent = `${element.value.length}/${maxLength}`;
            counter.className = remaining < 10 ? 'text-danger small float-end' : 'text-muted small float-end';
        }
        
        element.addEventListener('input', updateCounter);
        updateCounter();
    }
    
    // Ajouter les compteurs
    addCharCounter('meta_title', 60);
    addCharCounter('meta_description', 160);
    addCharCounter('excerpt', 500);
    
    // Initialiser l'aperçu Google
    updateGooglePreview();
});

// Fonction de réinitialisation du formulaire
function resetForm() {
    if (confirm('Êtes-vous sûr de vouloir réinitialiser le formulaire ? Toutes les données non sauvegardées seront perdues.')) {
        document.getElementById('pageForm').reset();
        document.getElementById('preview-content').innerHTML = '<p class="text-muted">Tapez du contenu pour voir l\'aperçu...</p>';
        document.getElementById('menu_order_group').style.display = 'none';
        
        // Réinitialiser les données de slug manuel
        document.getElementById('slug').removeAttribute('data-manual');
    }
}

// Validation avant soumission
document.getElementById('pageForm').addEventListener('submit', function(e) {
    const title = document.getElementById('title').value.trim();
    const content = document.getElementById('content').value.trim();
    
    if (!title) {
        alert('Le titre de la page est obligatoire.');
        e.preventDefault();
        document.getElementById('title').focus();
        return false;
    }
    
    if (!content) {
        alert('Le contenu de la page est obligatoire.');
        e.preventDefault();
        document.getElementById('content').focus();
        return false;
    }
});
