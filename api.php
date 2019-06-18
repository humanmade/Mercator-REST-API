<?php
/**
 * Mercator REST API
 *
 * WordPress multisite domain mapping API.
 *
 * @package Mercator
 */

namespace Mercator\REST;

add_action( 'rest_api_init', __NAMESPACE__ . '\\rest_api_init' );

/**
 * Load REST Controller
 */
function rest_api_init() {
	if ( class_exists( 'Mercator\REST\Mappings_Controller' ) ) {
		return;
	}
	require_once __DIR__ . '/classes/class-rest-mappings-controller.php';
	require_once __DIR__ . '/classes/class-rest-primary-domain-controller.php';

	// Mappings.
	$mappings = new Mappings_Controller;
	$mappings->register_routes();

	// Primary domains.
	$primary = new Primary_Domain_Controller();
	$primary->register_routes();
}
