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
})();