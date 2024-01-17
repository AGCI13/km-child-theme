(function($) {
    $(document).ready(function() {
        var passwordField = $('.uael-registration-form input[name="password"]');
        var submitButton = $('.uael-registration-form button[type="submit"]');
        var passWrapper = $('.uael-registration-form .uael-pass-wrapper');

        // Créer l'élément .km-registration-notice s'il n'existe pas
        if (passWrapper.find('.km-registration-notice').length === 0) {
            passWrapper.append('<div class="km-registration-notice"></div>');
        }
        var passNotice = passWrapper.find('.km-registration-notice');

        function updateNotice(message, condition, className) {
            var notice = passNotice.find('.' + className);
            if (condition) {
                if (notice.length === 0) {
                    passNotice.append('<div class="' + className + '">' + message + '</div>');
                }
            } else {
                notice.remove();
            }
        }

        function checkPasswordValidity() {
            var password = passwordField.val();

            updateNotice('Le mot de passe doit contenir au moins 8 caractères.', password.length < 8, 'length-error');
            updateNotice('Le mot de passe doit inclure au moins un chiffre.', !/[0-9]/.test(password), 'digit-error');
            updateNotice('Le mot de passe doit inclure au moins une lettre minuscule.', !/[a-z]/.test(password), 'lowercase-error');
            updateNotice('Le mot de passe doit inclure au moins une lettre majuscule.', !/[A-Z]/.test(password), 'uppercase-error');
            updateNotice('Le mot de passe doit inclure au moins un caractère spécial.', !/[\W]/.test(password), 'specialchar-error');

            var isValid = password.length >= 8 && /[0-9]/.test(password) && /[a-z]/.test(password) && /[A-Z]/.test(password) && /[\W]/.test(password);
            submitButton.prop('disabled', !isValid);
        }

        passwordField.on('input', checkPasswordValidity);
    });
})(jQuery);
