<?php

class Sailthru_Content_Settings {

	public function __construct() {

		add_action( 'admin_init', array( $this, 'init_settings'  ), 11 );
	}

	public function init_settings() {

		add_settings_section(
			'sailthru_content_setup_section',   
			__( '', 'sailthru-for-wordpress' ),   
			array( $this, 'sailthru_content_setup_section_callback'),  
			'sailthru_setup_options'   
		);

		add_settings_field(
			'sailthru_content_vars',
			__( 'Whitelist vars', 'text_domain' ),
			array( $this, 'render_sailthru_content_vars_field' ),
			'sailthru_setup_options',
			'sailthru_content_setup_section'
		);

	}

	/**
	 * Creates a section header for the Content Settings.
	 *
	 */
	function sailthru_content_setup_section_callback() {

		echo '<h3 class="sailthru-sub-section">Content Settings</h3>';
		echo "<p>Configure global settings for content that is added to the Sailthru Content Library.</p>";
	}


	function render_sailthru_content_vars_field() {

		// Retrieve data from the database.
		$options = get_option( 'sailthru_setup_options' );
		// Set default value.
		$value = isset( $options['content_vars'] ) ? $options['content_vars'] : '';

		// Field output.
		echo '<input type="text" name="sailthru_setup_options[content_vars]" class="regular-text sailthru_setup_options_content_vars_field" placeholder="' . esc_attr__( '', 'text_domain' ) . '" value="' . esc_attr( $value ) . '">';
		echo '<p class="description">' . __( 'Provide a comma separated list of vars that should be included when available on the content type. </p><p class="description">This is useful when you want to specify which vars are included in your content library and Sailthru data feeds.', 'text_domain' ) . '</p>';

	}

}

new Sailthru_Content_Settings;

