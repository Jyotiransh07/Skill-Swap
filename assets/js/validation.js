/**
 * SkillSwap Campus - Form Validation & Password Strength Evaluator
 */

document.addEventListener('DOMContentLoaded', function () {
  const registerForm = document.getElementById('registerForm');
  const passwordInput = document.getElementById('password');
  const confirmPasswordInput = document.getElementById('confirm_password');
  const strengthBar = document.getElementById('passwordStrengthBar');

  if (passwordInput && strengthBar) {
    passwordInput.addEventListener('input', function () {
      const val = passwordInput.value;
      let score = 0;

      if (val.length >= 6) score += 25;
      if (val.length >= 10) score += 25;
      if (/[A-Z]/.test(val)) score += 20;
      if (/[0-9]/.test(val)) score += 15;
      if (/[^A-Za-z0-9]/.test(val)) score += 15;

      strengthBar.style.width = score + '%';

      if (score < 40) {
        strengthBar.style.backgroundColor = '#ef4444'; // Red
      } else if (score < 75) {
        strengthBar.style.backgroundColor = '#f59e0b'; // Orange/Yellow
      } else {
        strengthBar.style.backgroundColor = '#10b981'; // Green
      }
    });
  }

  if (registerForm) {
    registerForm.addEventListener('submit', function (e) {
      let isValid = true;

      if (passwordInput && confirmPasswordInput) {
        if (passwordInput.value !== confirmPasswordInput.value) {
          e.preventDefault();
          alert('Passwords do not match. Please verify your confirm password field.');
          confirmPasswordInput.focus();
          isValid = false;
        }
      }
      return isValid;
    });
  }
});
