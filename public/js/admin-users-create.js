document.addEventListener('DOMContentLoaded', function() {
    // Gestion de l'affichage/masquage du mot de passe
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        const icon = this.querySelector('i');
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    });

    // Prévisualisation de l'avatar
    const avatarInput = document.getElementById('avatar');
    const avatarPreview = document.getElementById('avatarPreview');
    const previewImg = document.getElementById('previewImg');

    avatarInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                avatarPreview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            avatarPreview.style.display = 'none';
        }
    });

    // Gestion des champs spécifiques au promoteur
    const roleSelect = document.getElementById('role');
    const promoteurFields = document.getElementById('promoteurFields');

    function togglePromoteurFields() {
        if (roleSelect.value === 'promoteur') {
            promoteurFields.style.display = 'block';
            // Rendre certains champs requis pour les promoteurs
            document.getElementById('company_name').setAttribute('required', '');
        } else {
            promoteurFields.style.display = 'none';
            // Enlever la contrainte required
            document.getElementById('company_name').removeAttribute('required');
        }
    }

    roleSelect.addEventListener('change', togglePromoteurFields);
    togglePromoteurFields(); // Appel initial

    // Validation du mot de passe
    const passwordConfirmation = document.getElementById('password_confirmation');
    
    function validatePasswords() {
        if (passwordInput.value !== passwordConfirmation.value) {
            passwordConfirmation.setCustomValidity('Les mots de passe ne correspondent pas');
        } else {
            passwordConfirmation.setCustomValidity('');
        }
    }

    passwordInput.addEventListener('input', validatePasswords);
    passwordConfirmation.addEventListener('input', validatePasswords);

    // Validation du formulaire
    const form = document.getElementById('userForm');
    form.addEventListener('submit', function(e) {
        const action = e.submitter.value;
        
        if (action === 'save_and_notify') {
            if (!confirm('Créer l\'utilisateur et lui envoyer un email de notification ?')) {
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

    // Génération automatique de mot de passe
    const generatePasswordBtn = document.createElement('button');
    generatePasswordBtn.type = 'button';
    generatePasswordBtn.className = 'btn btn-outline-info btn-sm mt-2';
    generatePasswordBtn.innerHTML = '<i class="fas fa-random me-1"></i>Générer un mot de passe';
    
    generatePasswordBtn.addEventListener('click', function() {
        const password = generateSecurePassword();
        passwordInput.value = password;
        passwordConfirmation.value = password;
        
        // Afficher le mot de passe généré
        passwordInput.type = 'text';
        togglePassword.querySelector('i').classList.remove('fa-eye');
        togglePassword.querySelector('i').classList.add('fa-eye-slash');
        
        // Validation
        validatePasswords();
        
        alert('Mot de passe généré: ' + password + '\n\nAssurez-vous de le communiquer à l\'utilisateur de manière sécurisée.');
    });
    
    document.getElementById('password').parentNode.parentNode.appendChild(generatePasswordBtn);

    // Validation de l'email en temps réel
    const emailInput = document.getElementById('email');
    let emailTimeout;

    emailInput.addEventListener('input', function() {
        clearTimeout(emailTimeout);
        const email = this.value;
        
        if (email.length > 5 && email.includes('@')) {
            emailTimeout = setTimeout(() => {
                checkEmailAvailability(email);
            }, 500);
        }
    });

    // Formatage du numéro de téléphone
    const phoneInput = document.getElementById('phone');
    phoneInput.addEventListener('input', function() {
        let value = this.value.replace(/\D/g, ''); // Enlever tout sauf les chiffres
        
        if (value.startsWith('225')) {
            // Format ivoirien: +225 XX XX XX XX XX
            if (value.length <= 13) {
                value = value.replace(/(\d{3})(\d{2})(\d{2})(\d{2})(\d{2})/, '+$1 $2 $3 $4 $5');
            }
        } else if (value.length > 0) {
            // Ajouter le préfixe +225 automatiquement
            value = '225' + value;
            if (value.length <= 13) {
                value = value.replace(/(\d{3})(\d{2})(\d{2})(\d{2})(\d{2})/, '+$1 $2 $3 $4 $5');
            }
        }
        
        this.value = value;
    });
});

// Fonction pour générer un mot de passe sécurisé
function generateSecurePassword(length = 12) {
    const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
    let password = "";
    
    // S'assurer qu'on a au moins un caractère de chaque type
    password += "ABCDEFGHIJKLMNOPQRSTUVWXYZ"[Math.floor(Math.random() * 26)]; // Majuscule
    password += "abcdefghijklmnopqrstuvwxyz"[Math.floor(Math.random() * 26)]; // Minuscule
    password += "0123456789"[Math.floor(Math.random() * 10)]; // Chiffre
    password += "!@#$%^&*"[Math.floor(Math.random() * 8)]; // Symbole
    
    // Compléter avec des caractères aléatoires
    for (let i = password.length; i < length; i++) {
        password += charset[Math.floor(Math.random() * charset.length)];
    }
    
    // Mélanger le mot de passe
    return password.split('').sort(() => Math.random() - 0.5).join('');
}

// Fonction pour vérifier la disponibilité de l'email
async function checkEmailAvailability(email) {
    try {
        const response = await fetch('/admin/users/check-email', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ email: email })
        });
        
        const data = await response.json();
        const emailInput = document.getElementById('email');
        
        if (data.available) {
            emailInput.classList.remove('is-invalid');
            emailInput.classList.add('is-valid');
        } else {
            emailInput.classList.remove('is-valid');
            emailInput.classList.add('is-invalid');
            
            // Afficher un message d'erreur
            let feedback = emailInput.parentNode.querySelector('.invalid-feedback');
            if (!feedback) {
                feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                emailInput.parentNode.appendChild(feedback);
            }
            feedback.textContent = 'Cette adresse email est déjà utilisée.';
        }
    } catch (error) {
        console.error('Erreur lors de la vérification de l\'email:', error);
    }
}
