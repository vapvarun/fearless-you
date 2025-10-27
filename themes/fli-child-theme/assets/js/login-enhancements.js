/**
 * Login Page Enhancements
 * Adds placeholders and accessibility improvements
 */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		// Add placeholders for ALL devices for better accessibility
		const userLogin = document.getElementById('user_login');
		const userPass = document.getElementById('user_pass');

		if (userLogin) {
			userLogin.placeholder = 'Username or Email Address';
			userLogin.setAttribute('aria-label', 'Username or Email Address');
			userLogin.setAttribute('autocomplete', 'username');
		}

		if (userPass) {
			userPass.placeholder = 'Password';
			userPass.setAttribute('aria-label', 'Password');
			userPass.setAttribute('autocomplete', 'current-password');
		}

		// Add aria-label to remember me checkbox
		const rememberMe = document.getElementById('rememberme');
		if (rememberMe) {
			rememberMe.setAttribute('aria-label', 'Remember Me');
		}

		// Add aria-label to submit button
		const submitBtn = document.getElementById('wp-submit');
		if (submitBtn) {
			submitBtn.setAttribute('aria-label', 'Login with Password');
		}

		// Mobile optimizations
		if (window.innerWidth <= 1024) {
			// Hide any emoji or decorative elements on mobile
			const emojis = document.querySelectorAll(
				'.emoji, [data-emoji], .wp-emoji'
			);
			emojis.forEach(function (emoji) {
				emoji.style.display = 'none';
			});
		}

		// Remove empty error messages on page load
		const errorDiv = document.getElementById('login_error');
		if (errorDiv && errorDiv.innerHTML.trim() === '') {
			errorDiv.style.display = 'none';
		}
	});
})();
