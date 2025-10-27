/**
 * MutationObserver Safety Wrapper
 * Prevents MutationObserver errors from breaking the site
 */
(function () {
	'use strict';

	// Store original immediately
	const OrigMO =
		window.MutationObserver ||
		window.WebKitMutationObserver ||
		window.MozMutationObserver;
	if (!OrigMO) {
		return;
	}

	// Create safe wrapper
	function SafeMutationObserver(callback) {
		// Wrap callback to catch errors
		const safeCallback = function (mutations, observer) {
			try {
				if (callback) {
					callback(mutations, observer);
				}
			} catch (e) {
				console.warn('MutationObserver callback error prevented:', e);
			}
		};

		// Create instance with safe callback
		const instance = new OrigMO(safeCallback);

		// Store original observe method
		const originalObserve = instance.observe;

		// Override observe with safety checks
		instance.observe = function (target, options) {
			// Comprehensive validation
			if (!target) {
				console.warn(
					'MutationObserver.observe called with null/undefined target'
				);
				return;
			}
			if (typeof target !== 'object') {
				console.warn(
					'MutationObserver.observe called with non-object target:',
					target
				);
				return;
			}
			if (
				!target.nodeType ||
				target.nodeType < 1 ||
				target.nodeType > 11
			) {
				console.warn(
					'MutationObserver.observe called with invalid node:',
					target
				);
				return;
			}

			try {
				return originalObserve.call(instance, target, options || {});
			} catch (e) {
				console.warn('MutationObserver.observe error prevented:', e);
			}
		};

		// Override disconnect to handle errors
		const originalDisconnect = instance.disconnect;
		instance.disconnect = function () {
			try {
				return originalDisconnect.call(instance);
			} catch (e) {
				console.warn('MutationObserver.disconnect error prevented:', e);
			}
		};

		return instance;
	}

	// Copy static properties
	for (const prop in OrigMO) {
		if (OrigMO.hasOwnProperty(prop)) {
			SafeMutationObserver[prop] = OrigMO[prop];
		}
	}

	// Set prototype
	SafeMutationObserver.prototype = OrigMO.prototype;

	// Replace global MutationObserver
	window.MutationObserver = SafeMutationObserver;
	window.WebKitMutationObserver = SafeMutationObserver;
	window.MozMutationObserver = SafeMutationObserver;

	console.log('MutationObserver safety wrapper installed');
})();
