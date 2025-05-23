<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Elementor form Hubspot action.
 *
 * Custom Elementor form action that sends form to Hubspot after form submission.
 *
 * @since 1.0.0
 */
class Hubspot_Action_After_Submit extends \ElementorPro\Modules\Forms\Classes\Action_Base {

	/**
	 * Get action name.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function get_name() {
		return 'hubspot';
	}

	/**
	 * Get action label.
	 *
	 * Retrieve Sendy action label.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function get_label() {
		return esc_html__( 'Hubspot', 'elementor-forms-hubspot-action' );
	}

	/**
	 * Register action controls.
	 *
	 * Add input fields to allow the user to customize the action settings.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param \Elementor\Widget_Base $widget
	 */
	public function register_settings_section( $widget ) {

		$widget->start_controls_section(
			'section_hubspot',
			[
				'label' => esc_html__( 'Hubspot', 'elementor-forms-hubspot-action' ),
				'condition' => [
					'submit_actions' => $this->get_name(),
				],
			]
		);

		// Add switch control for using dropdown or dynamic field
		$widget->add_control(
			'use_dropdown',
			[
				'label' => esc_html__( 'Use drop-down', 'elementor-forms-hubspot-action' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		// Fetch forms from the API
		$forms = $this->getHubspotForms();

		// Prepare options for the dropdown
		$options = [];
		if ( ! empty( $forms ) && is_array( $forms ) ) {
			foreach ( $forms as $form ) {
				$options[ $form['guid'] ] = $form['name'];
			}
		}

		// Dropdown field
		$widget->add_control(
			'hubspot_formid_dropdown',
			[
				'label' => esc_html__( 'Form', 'elementor-forms-hubspot-action' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'options' => $options,
				'description' => esc_html__( 'Select the Form', 'elementor-forms-hubspot-action' ),
				'condition' => [
					'use_dropdown' => 'yes',
				],
				'dynamic' => [
					'active' => true,
				],
			]
		);

		// Dynamic field
		$widget->add_control(
			'hubspot_formid_dynamic',
			[
				'label' => esc_html__( 'Form ID', 'elementor-forms-hubspot-action' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'description' => esc_html__( 'Enter the Form ID', 'elementor-forms-hubspot-action' ),
				'condition' => [
					'use_dropdown' => '',
				],
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$widget->end_controls_section();

	}


	/**
	 * Get Hubspot Forms.
	 *
	 * Performs an API call to retrieve Hubspot forms.
	 *
	 * @since 1.0.0
	 * @access private
	 * @return array
	 */
	private function getHubspotForms() {

		// Get the context values from the options, settings and fields
		$options = get_option( 'wporg_options' );
		$accessToken = isset( $options['hubspot_access_token'] ) ? $options['hubspot_access_token'] : '';

		// Create header with Authorization token
		$args = [
			'headers' => [
				'Authorization' => "Bearer $accessToken",
			],
		];

		$response = wp_remote_get( 'https://api.hubapi.com/forms/v2/forms', $args );

		if ( is_wp_error( $response ) ) {
			return [];
		}

		$body = wp_remote_retrieve_body( $response );
		$forms = json_decode( $body, true );

		return isset( $forms ) ? $forms : [];
	}

	/**
	 * Run action.
	 *
	 * Runs the Hubspot action after form submission.
	 *
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record
	 * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
	 *
	 * @throws Exception
	 * @since 1.0.0
	 * @access public
	 */
	public function run( $record, $ajax_handler ) {

		$settings = $record->get( 'form_settings' );

		// Make sure that there is a Hubspot access token.
		if ( empty( $settings['hubspot_access_token'] ) ) {
			return;
		}

		// Make sure that there is a portal ID.
		if ( empty( $settings['hubspot_portalid'] ) ) {
			return;
		}

		// Check if the form ID is set by dropdown or dynamic field
		if ( $settings['use_dropdown'] === 'yes' ) {
			if ( empty( $settings['hubspot_formid_dropdown'] ) ) {
				return;
			}
			$formGuid = $settings['hubspot_formid_dropdown'];
		} else {
			if ( empty( $settings['hubspot_formid_dynamic'] ) ) {
				return;
			}
			$formGuid = $settings['hubspot_formid_dynamic'];
		}

		// Get submitted form data.
		$raw_fields = $record->get( 'fields' );

		// Normalize form data and filter out privacy_consent field
		$fields = [];
		foreach ( $raw_fields as $id => $field ) {
			if ($field['id'] != 'privacy_consent')
				$fields[ $id ] = $field['value'];
		}

		// Get the context values from the options, settings and fields
		$options = get_option( 'wporg_options' );
		$accessToken = isset( $options['hubspot_access_token'] ) ? $options['hubspot_access_token'] : '';
		$portalId = isset( $options['hubspot_portalid'] ) ? $options['hubspot_portalid'] : '';

		$consentProcessText = isset( $options['hubspot_consenttoprocess'] ) ? $options['hubspot_consenttoprocess'] : '';

		// Initialize communications array
		$communications = [];
		for ($i = 1; $i <= 5; $i++) {
			$consentFieldId = 'hubspot_consent_option_' . $i . '_id';
			$consentFieldText = 'hubspot_consent_option_' . $i . '_text';
			$subscriptionTypeIdOption = 'hubspot_consent_option_' . $i . '_subscriptionTypeId';

			if (isset($fields[$consentFieldId])) {
				$communications[] = [
					'value' => !empty($fields[$consentFieldId]),
					'subscriptionTypeId' => isset($options[$consentFieldId]) ? $options[$consentFieldId] : '',
					'text' => isset($options[$consentFieldText]) ? $options[$consentFieldText] : ''
				];
				// Remove consent options from fields
				unset($fields[$consentFieldId]);
			}
		}

		$postID = $fields['post_id'] ?? '';

		// Validate and sanitize the 'post_id' from the POST data
		if ($postID != '' && is_numeric($postID)) {
			// Retrieve post title and URL, ensure the post ID is valid
			if (get_post_status($postID)) {
				$post_title = get_the_title($postID);
				$post_url = get_permalink($postID);
				// remove the post_id field from the array $fields as it is not submitted
				unset($fields['post_id']);
			} else throw new \Exception( 'Post does not exist' );
		} else throw new \Exception( 'post_id not provided' );

		$hutk = $_COOKIE['hubspotutk'] ?? null;

		$endpoint = "https://api.hsforms.com/submissions/v3/integration/secure/submit/$portalId/$formGuid";

		// Validate or sanitize the URL and IP Address (basic sanitization shown; adjust as needed for your context)
		$post_url = filter_var($post_url, FILTER_SANITIZE_URL);
		$ipAddress = filter_var(\ElementorPro\Core\Utils::get_client_ip(), FILTER_VALIDATE_IP);

		// Sanitize textual content (for JSON encoding, focus on ensuring clean data rather than HTML escaping)
		$post_title = filter_var($post_title, FILTER_SANITIZE_STRING);
		$consentProcessText = filter_var($consentProcessText, FILTER_SANITIZE_STRING);

		// Initialize the request body with the "fields", "context", and "legalConsentOptions" keys.
		$requestBody = [
			'fields' => [],
			'context' => [
				'hutk' => $hutk,
				'pageUri' => $post_url,
				'ipAddress' => $ipAddress,
				'pageName' => $post_title
			],
			'legalConsentOptions' => [
				'consent' => [
					'consentToProcess' => true,
					'text' => $consentProcessText,
					'communications' => $communications
				]
			]
		];

		// Assuming $fields array is already populated
		foreach ($fields as $name => $value) {
			$requestBody['fields'][] = [
				'objectTypeId' => '0-1',
				'name' => $name,
				'value' => $value
			];
		}

		// Convert the requestBody to JSON format for the API request.
		$jsonRequestBody = json_encode($requestBody);

		// Set up the arguments for wp_remote_post()
		$args = [
			'body'        => $jsonRequestBody,
			'headers'     => [
				'Content-Type' => 'application/json',
				'Authorization' => "Bearer $accessToken",
			],
			'method'      => 'POST',
			'data_format' => 'body',
		];

		// Make the request
		$response = wp_remote_post($endpoint, $args);

		// Check for error in the response
		if (is_wp_error($response)) {
			$error_message = $response->get_error_message();
			throw new \Exception( $error_message );
		}

		// Check for HTTP status code 400
		if (wp_remote_retrieve_response_code($response) == 400) {
			throw new \Exception( 'Bad Request: The server could not understand the request due to invalid syntax.' );
		}
	}

	/**
	 * On export.
	 *
	 * Clears Sendy form settings/fields when exporting.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param array $element
	 */
	public function on_export( $element ) {

		unset(
			$element['hubspot_formid']
		);

		return $element;

	}

}
