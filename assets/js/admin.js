/**
 * Blaze Widgets Admin & Editor Script
 */

(function () {
	'use strict';

	// Live slug preview in the builder: derive a slug from the title.
	const titleInput = document.getElementById('title');
	const slugInput = document.getElementById('slug');

	if (titleInput && slugInput && !slugInput.readOnly) {
		titleInput.addEventListener('input', () => {
			const value = titleInput.value
				.toLowerCase()
				.replace(/[^a-z0-9]+/g, '-')
				.replace(/(^-|-$)/g, '');
			slugInput.value = value;
		});
	}

	// Validate JSON before submitting the builder form.
	const builderForm = document.querySelector('form[data-blaze-builder]');
	if (builderForm) {
		builderForm.addEventListener('submit', (e) => {
			const jsonField = document.getElementById('controls_json');
			if (!jsonField) {
				return;
			}
			try {
				JSON.parse(jsonField.value);
			} catch (err) {
				e.preventDefault();
				alert('Controls JSON is invalid: ' + err.message);
			}
		});
	}

	// Ko-fi banner: visible by default; hidden only if the user dismissed it on this browser.
	const kofiBanner = document.getElementById('blaze-kofi-banner');
	if (kofiBanner) {
		const STORAGE_KEY = 'blaze_widgets_kofi_banner_dismissed_v2';
		let dismissed = false;
		try {
			dismissed = window.localStorage.getItem(STORAGE_KEY) === '1';
		} catch (err) {
			dismissed = false;
		}

		if (dismissed) {
			kofiBanner.setAttribute('hidden', '');
		}

		const closeBtn = kofiBanner.querySelector('[data-blaze-kofi-close]');
		if (closeBtn) {
			closeBtn.addEventListener('click', () => {
				try {
					window.localStorage.setItem(STORAGE_KEY, '1');
				} catch (err) {
					// Storage unavailable (private mode, sandboxed iframe, etc.).
				}
				kofiBanner.setAttribute('hidden', '');
			});
		}
	}
})();