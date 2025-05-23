<?php

/**
 * Class Hubspot_Action_Settings
 *
 * Configure the plugin settings page.
 */
class Hubspot_Action_Settings {

	/**
	 * Capability required by the user to access the My Plugin menu entry.
	 *
	 * @var string $capability
	 */
	private $capability = 'manage_options';

	/**
	 * Array of fields that should be displayed in the settings page.
	 *
	 * @var array $fields
	 */
	private $fields = [
		[
			'id' => 'hubspot_access_token',
			'label' => 'Hubspot Access Token',
			'description' => '',
			'type' => 'text',
		],
		[
			'id' => 'hubspot_portalid',
			'label' => 'Hubspot Portalid',
			'description' => '',
			'type' => 'text',
		],
		[
			'id' => 'hubspot_consenttoprocess',
			'label' => 'Consent to Process Text',
			'description' => '',
			'type' => 'textarea',
		],
	];

	/**
	 * Constructor to add consent fields dynamically.
	 */
	function __construct() {
		for ($i = 1; $i <= 5; $i++) {
			$this->fields[] = [
				'id' => 'hubspot_consent_option_' . $i . '_id',
				'label' => 'Consent Option ' . $i . ' Subscription Type ID',
				'description' => '',
				'type' => 'text',
			];
			$this->fields[] = [
				'id' => 'hubspot_consent_option_' . $i . '_text',
				'label' => 'Consent Option ' . $i . ' Text',
				'description' => '',
				'type' => 'textarea',
			];
		}
		add_action('admin_init', [$this, 'settings_init']);
		add_action('admin_menu', [$this, 'options_page']);
	}

	/**
	 * Register the settings and all fields.
	 */
	function settings_init() : void {
		// Register a new setting this page.
		register_setting('hubspot-action-plugin-settings', 'wporg_options');

		// Register a new section.
		add_settings_section(
			'hubspot-action-plugin-settings-section',
			__('', 'hubspot-action-plugin-settings'),
			[$this, 'render_section'],
			'hubspot-action-plugin-settings'
		);

		/* Register All The Fields. */
		foreach ($this->fields as $field) {
			// Register a new field in the main section.
			add_settings_field(
				$field['id'], /* ID for the field. Only used internally. To set the HTML ID attribute, use $args['label_for']. */
				__($field['label'], 'hubspot-action-plugin-settings'), /* Label for the field. */
				[$this, 'render_field'], /* The name of the callback function. */
				'hubspot-action-plugin-settings', /* The menu page on which to display this field. */
				'hubspot-action-plugin-settings-section', /* The section of the settings page in which to show the box. */
				[
					'label_for' => $field['id'], /* The ID of the field. */
					'class' => 'wporg_row', /* The class of the field. */
					'field' => $field, /* Custom data for the field. */
				]
			);
		}
	}

	/**
	 * Add a subpage to the WordPress Settings menu.
	 */
	function options_page() : void {
		add_submenu_page(
			'tools.php', /* Parent Menu Slug */
			'Settings Hubspot Action', /* Page Title */
			'Elementor Forms Hubspot Action', /* Menu Title */
			$this->capability, /* Capability */
			'hubspot-action-plugin-settings', /* Menu Slug */
			[$this, 'render_options_page'], /* Callback */
		);
	}

	/**
	 * Render the settings page.
	 */
	function render_options_page() : void {
		// check user capabilities
		if (!current_user_can($this->capability)) {
			return;
		}

		// check if the user have submitted the settings
		// WordPress will add the "settings-updated" $_GET parameter to the url
		if (isset($_GET['settings-updated'])) {
			// add settings saved message with the class of "updated"
			add_settings_error('wporg_messages', 'wporg_message', __('Settings Saved', 'hubspot-action-plugin-settings'), 'updated');
		}

		// show error/update messages
		settings_errors('wporg_messages');
		?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <h2 class="description">Manage the settings of the hubspot action</h2>
            <form action="options.php" method="post">
				<?php
				/* output security fields for the registered setting "wporg" */
				settings_fields('hubspot-action-plugin-settings');
				/* output setting sections and their fields */
				/* (sections are registered for "wporg", each field is registered to a specific section) */
				do_settings_sections('hubspot-action-plugin-settings');
				/* output save settings button */
				submit_button('Save Settings');
				?>
            </form>
        </div>
		<?php
	}

	/**
	 * Render a settings field.
	 *
	 * @param array $args Args to configure the field.
	 */
	function render_field(array $args) : void {
		$field = $args['field'];

		// Get the value of the setting we've registered with register_setting()
		$options = get_option('wporg_options');

		switch ($field['type']) {
			case "text": {
				?>
                <input
                        type="text"
                        id="<?php echo esc_attr($field['id']); ?>"
                        name="wporg_options[<?php echo esc_attr($field['id']); ?>]"
                        value="<?php echo isset($options[$field['id']]) ? esc_attr($options[$field['id']]) : ''; ?>"
                >
                <p class="description">
					<?php esc_html_e($field['description'], 'hubspot-action-plugin-settings'); ?>
                </p>
				<?php
				break;
			}

			case "textarea": {
				?>
                <textarea
                        id="<?php echo esc_attr($field['id']); ?>"
                        name="wporg_options[<?php echo esc_attr($field['id']); ?>]"
                ><?php echo isset($options[$field['id']]) ? esc_attr($options[$field['id']]) : ''; ?></textarea>
                <p class="description">
					<?php esc_html_e($field['description'], 'hubspot-action-plugin-settings'); ?>
                </p>
				<?php
				break;
			}
		}
	}

	/**
	 * Render a section on a page, with an ID and a text label.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args {
	 *     An array of parameters for the section.
	 *
	 *     @type string $id The ID of the section.
	 * }
	 */
	function render_section(array $args) : void {
		?>
        <p id="<?php echo esc_attr($args['id']); ?>"><?php esc_html_e('Settings', 'hubspot-action-plugin-settings'); ?></p>
		<?php
	}

}

new Hubspot_Action_Settings();