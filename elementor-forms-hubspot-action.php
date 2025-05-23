<?php

/**
 * Plugin Name: Elementor Forms Hubspot Action
 * Description: Custom addon which sends the form to Hubspot after form submission.
 * Plugin URI:  https://moreaweasome.co
 * Version:     0.11
 * Author:      More Awesome B.V.
 * Author URI:  https://moreawesome.co
 * Text Domain: elementor-forms-hubspot-action
 *
 * Requires Plugins: elementor
 * Elementor tested up to: 3.20.0
 * Elementor Pro tested up to: 3.20.0
 */

require_once plugin_dir_path( __FILE__ ) . 'Hubspot_Action_Settings.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Send form data to Hubspot
 *
 * @param ElementorPro\Modules\Forms\Registrars\Form_Actions_Registrar $form_actions_registrar
 *
 * @return void
 * @since 1.0.0
 */
function add_new_hubspot_form_action( $form_actions_registrar ) {

	include_once( __DIR__ . '/form-actions/hubspot.php' );

	$form_actions_registrar->register( new Hubspot_Action_After_Submit() );

}

add_action( 'elementor_pro/forms/actions/register', 'add_new_hubspot_form_action' );
