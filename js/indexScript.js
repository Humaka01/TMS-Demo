document.addEventListener('DOMContentLoaded', () => {
    // Initially hide the registration form
    var x = document.getElementById("register_container");
    x.style.display = "none";

    // Failed Login
    const urlParams = new URLSearchParams(window.location.search);
    const loginFailed = urlParams.get('loginFailed');
    if (loginFailed) {
        const errorMessage = document.getElementById('login-error');
        if (errorMessage) {
            errorMessage.textContent = 'Invalid username or password. Please try again.';
        }
    }

    // Failed Registration
    const registerFailed = urlParams.get('registerFailed');
    if (registerFailed) {
        const errorMessage = document.getElementById('register-error');
        if (errorMessage) {
            errorMessage.textContent = 'Email is already taken. Please use a different email.';
            x.style.display = "block";
        }
    }
});

function showTheRegister() {
    var x = document.getElementById("register_container");
    if (x.style.display === "none") {
        x.style.display = "block";
        
    } else {
        x.style.display = "none";
    }
}
