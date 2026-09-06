/**
 * Blaze Example Card Frontend & Editor Handler
 *
 * Modern Vanilla JavaScript for Example Card Widget.
 */

(function () {
	'use strict';

	/**
	 * Handler for Blaze Example Card Widget instance.
	 *
	 * @param {jQuery} $scope The widget wrapper element as jQuery object.
	 */
	const BlazeExampleCardHandler = function ($scope) {
		const cardElement = $scope[0]?.querySelector('.blaze-example-card');
		if (!cardElement) {
			return;
		}

		// Example interactive behavior/enhancement if needed
		// Self-contained, zero global scope pollution.
	};

	// Hook into Elementor frontend lifecycle.
	window.addEventListener('elementor/frontend/init', () => {
		if (window.elementorFrontend && window.elementorFrontend.hooks) {
			window.elementorFrontend.hooks.addAction(
				'frontend/element_ready/blaze-example-card.default',
				BlazeExampleCardHandler
			);
		}
	});
})();
