/**
 * Real Estate Listing System
 * Authentication JavaScript File
 */

document.addEventListener('DOMContentLoaded', function() {
    // Register form validation
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Name validation
            const fullNameInput = document.getElementById('full_name');
            if (fullNameInput && fullNameInput.value.trim() === '') {
                isValid = false;
                showValidationError(fullNameInput, 'Please enter your full name');
            } else if (fullNameInput) {
                removeValidationError(fullNameInput);
            }
            
            // Email validation
            const emailInput = document.getElementById('email');
            if (emailInput) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (emailInput.value.trim() === '') {
                    isValid = false;
                    showValidationError(emailInput, 'Please enter your email');
                } else if (!emailRegex.test(emailInput.value.trim())) {
                    isValid = false;
                    showValidationError(emailInput, 'Please enter a valid email');
                } else {
                    removeValidationError(emailInput);
                }
            }
            
            // Phone validation
            const phoneInput = document.getElementById('phone');
            if (phoneInput) {
                const phoneRegex = /^\(?([0-9]{3})\)?[-. ]?([0-9]{3})[-. ]?([0-9]{4})$/;
                if (phoneInput.value.trim() === '') {
                    isValid = false;
                    showValidationError(phoneInput, 'Please enter your phone number');
                } else if (!phoneRegex.test(phoneInput.value.trim())) {
                    isValid = false;
                    showValidationError(phoneInput, 'Please enter a valid phone number (e.g., 123-456-7890)');
                } else {
                    removeValidationError(phoneInput);
                }
            }
            
            // Password validation
            const passwordInput = document.getElementById('password');
            if (passwordInput) {
                if (passwordInput.value === '') {
                    isValid = false;
                    showValidationError(passwordInput, 'Please enter a password');
                } else if (passwordInput.value.length < 8) {
                    isValid = false;
                    showValidationError(passwordInput, 'Password must be at least 8 characters long');
                } else {
                    removeValidationError(passwordInput);
                }
            }
            
            // Confirm password validation
            const confirmPasswordInput = document.getElementById('confirm_password');
            if (confirmPasswordInput && passwordInput) {
                if (confirmPasswordInput.value === '') {
                    isValid = false;
                    showValidationError(confirmPasswordInput, 'Please confirm your password');
                } else if (confirmPasswordInput.value !== passwordInput.value) {
                    isValid = false;
                    showValidationError(confirmPasswordInput, 'Passwords do not match');
                } else {
                    removeValidationError(confirmPasswordInput);
                }
            }
            
            // Role validation
            const roleInput = document.getElementById('role');
            if (roleInput && roleInput.value === '') {
                isValid = false;
                showValidationError(roleInput, 'Please select a role');
            } else if (roleInput) {
                removeValidationError(roleInput);
            }
            
            // Terms checkbox validation
            const termsCheckbox = document.getElementById('terms');
            if (termsCheckbox && !termsCheckbox.checked) {
                isValid = false;
                showValidationError(termsCheckbox, 'You must agree to the terms and conditions');
            } else if (termsCheckbox) {
                removeValidationError(termsCheckbox);
            }
            
            if (!isValid) {
                e.preventDefault();
                
                // Scroll to first error
                const firstErrorElement = document.querySelector('.is-invalid');
                if (firstErrorElement) {
                    firstErrorElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    }
    
    // Login form validation
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Email validation
            const emailInput = document.getElementById('email');
            if (emailInput && emailInput.value.trim() === '') {
                isValid = false;
                showValidationError(emailInput, 'Please enter your email');
            } else if (emailInput) {
                removeValidationError(emailInput);
            }
            
            // Password validation
            const passwordInput = document.getElementById('password');
            if (passwordInput && passwordInput.value === '') {
                isValid = false;
                showValidationError(passwordInput, 'Please enter your password');
            } else if (passwordInput) {
                removeValidationError(passwordInput);
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
    
    // Profile form validation
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Name validation
            const fullNameInput = document.getElementById('full_name');
            if (fullNameInput && fullNameInput.value.trim() === '') {
                isValid = false;
                showValidationError(fullNameInput, 'Please enter your full name');
            } else if (fullNameInput) {
                removeValidationError(fullNameInput);
            }
            
            // Email validation
            const emailInput = document.getElementById('email');
            if (emailInput) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (emailInput.value.trim() === '') {
                    isValid = false;
                    showValidationError(emailInput, 'Please enter your email');
                } else if (!emailRegex.test(emailInput.value.trim())) {
                    isValid = false;
                    showValidationError(emailInput, 'Please enter a valid email');
                } else {
                    removeValidationError(emailInput);
                }
            }
            
            // Phone validation
            const phoneInput = document.getElementById('phone');
            if (phoneInput) {
                const phoneRegex = /^\(?([0-9]{3})\)?[-. ]?([0-9]{3})[-. ]?([0-9]{4})$/;
                if (phoneInput.value.trim() === '') {
                    isValid = false;
                    showValidationError(phoneInput, 'Please enter your phone number');
                } else if (!phoneRegex.test(phoneInput.value.trim())) {
                    isValid = false;
                    showValidationError(phoneInput, 'Please enter a valid phone number (e.g., 123-456-7890)');
                } else {
                    removeValidationError(phoneInput);
                }
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
    
    // Change password form validation
    const passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Current password validation
            const currentPasswordInput = document.getElementById('current_password');
            if (currentPasswordInput && currentPasswordInput.value === '') {
                isValid = false;
                showValidationError(currentPasswordInput, 'Please enter your current password');
            } else if (currentPasswordInput) {
                removeValidationError(currentPasswordInput);
            }
            
            // New password validation
            const newPasswordInput = document.getElementById('new_password');
            if (newPasswordInput) {
                if (newPasswordInput.value === '') {
                    isValid = false;
                    showValidationError(newPasswordInput, 'Please enter a new password');
                } else if (newPasswordInput.value.length < 8) {
                    isValid = false;
                    showValidationError(newPasswordInput, 'New password must be at least 8 characters long');
                } else {
                    removeValidationError(newPasswordInput);
                }
            }
            
            // Confirm new password validation
            const confirmNewPasswordInput = document.getElementById('confirm_new_password');
            if (confirmNewPasswordInput && newPasswordInput) {
                if (confirmNewPasswordInput.value === '') {
                    isValid = false;
                    showValidationError(confirmNewPasswordInput, 'Please confirm your new password');
                } else if (confirmNewPasswordInput.value !== newPasswordInput.value) {
                    isValid = false;
                    showValidationError(confirmNewPasswordInput, 'New passwords do not match');
                } else {
                    removeValidationError(confirmNewPasswordInput);
                }
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
    
    // Function to display validation error
    function showValidationError(inputElement, errorMessage) {
        inputElement.classList.add('is-invalid');
        
        // Remove existing error message if any
        const existingErrorMsg = inputElement.nextElementSibling;
        if (existingErrorMsg && existingErrorMsg.classList.contains('invalid-feedback')) {
            existingErrorMsg.remove();
        }
        
        const errorElement = document.createElement('div');
        errorElement.className = 'invalid-feedback';
        errorElement.textContent = errorMessage;
        
        inputElement.parentNode.insertBefore(errorElement, inputElement.nextSibling);
    }
    
    // Function to remove validation error
    function removeValidationError(inputElement) {
        inputElement.classList.remove('is-invalid');
        
        const existingErrorMsg = inputElement.nextElementSibling;
        if (existingErrorMsg && existingErrorMsg.classList.contains('invalid-feedback')) {
            existingErrorMsg.remove();
        }
    }
});
