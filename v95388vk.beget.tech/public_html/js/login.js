function togglePassword() {
    var passwordInput = document.getElementById('password');
    var toggleButton = document.querySelector('.toggle-password');
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleButton.textContent = 'Скрыть';
    } else {
        passwordInput.type = 'password';
        toggleButton.textContent = 'Показать';
    }
}

document.querySelector('.login-form').addEventListener('submit', function(e) {
    var email    = document.getElementById('email').value;
    var password = document.getElementById('password').value;

    if (!email || !password) {
        e.preventDefault();
        showError('Пожалуйста, заполните все поля');
        return;
    }
    if (!isValidEmail(email)) {
        e.preventDefault();
        showError('Введите корректный email');
        return;
    }
    if (password.length < 6) {
        e.preventDefault();
        showError('Пароль должен быть не менее 6 символов');
        return;
    }
});

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function showError(message) {
    var errorElement = document.getElementById('errorMessage');
    if (!errorElement) {
        errorElement = document.createElement('div');
        errorElement.className = 'error-message';
        errorElement.id = 'errorMessage';
        document.querySelector('.login-form').insertBefore(
            errorElement,
            document.querySelector('.form-group')
        );
    }
    errorElement.textContent = message;
    errorElement.classList.add('show');
    setTimeout(function() {
        errorElement.classList.remove('show');
    }, 5000);
}

document.getElementById('email').addEventListener('input', clearError);
document.getElementById('password').addEventListener('input', clearError);

function clearError() {
    var e = document.getElementById('errorMessage');
    if (e) e.classList.remove('show');
}
