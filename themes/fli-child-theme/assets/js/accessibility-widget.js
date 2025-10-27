/**
 * Accessibility Widget JavaScript
 * Extracted for better performance
 */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		// Toggle panel visibility
		const toggleBtn = document.getElementById('fli-a11y-toggle');
		const panel = document.getElementById('fli-a11y-panel');

		if (toggleBtn && panel) {
			toggleBtn.addEventListener('click', function () {
				panel.style.display =
					panel.style.display === 'none' ? 'block' : 'none';
			});
		}

		// Load saved preferences
		if (localStorage.getItem('a11y-high-contrast') === 'true') {
			document.body.classList.add('high-contrast');
		}
		if (localStorage.getItem('a11y-large-text') === 'true') {
			document.body.classList.add('large-text');
		}
		if (localStorage.getItem('a11y-readable-font') === 'true') {
			document.body.classList.add('readable-font');
		}
	});

	// Global functions for button clicks
	window.toggleHighContrast = function () {
		document.body.classList.toggle('high-contrast');
		localStorage.setItem(
			'a11y-high-contrast',
			document.body.classList.contains('high-contrast')
		);
	};

	window.toggleLargeText = function () {
		document.body.classList.toggle('large-text');
		localStorage.setItem(
			'a11y-large-text',
			document.body.classList.contains('large-text')
		);
	};

	window.toggleReadableFont = function () {
		document.body.classList.toggle('readable-font');
		localStorage.setItem(
			'a11y-readable-font',
			document.body.classList.contains('readable-font')
		);
	};

	window.resetAccessibility = function () {
		document.body.classList.remove(
			'high-contrast',
			'large-text',
			'readable-font'
		);
		localStorage.removeItem('a11y-high-contrast');
		localStorage.removeItem('a11y-large-text');
		localStorage.removeItem('a11y-readable-font');
	};
})();
