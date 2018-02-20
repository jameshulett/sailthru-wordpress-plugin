<?php


class Sailthru_Subscribe_Fields {


	protected $admin_views = array();

	// Represents the nonce value used to save the post media
	private $nonce = 'wp_sailthru_nonce';


	/*--------------------------------------------*
	 * Constructor
	 *--------------------------------------------*/

	/**
	 * Initializes the plugin by setting localization, filters, and administration functions.
	 */
	function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'sailthru_init' ) );

		// Register admin styles and scripts
		// Documentation says: admin_print_styles should not be used to enqueue styles or scripts on the admin pages. Use admin_enqueue_scripts instead.
		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts' ) );

		// Register the menu
		add_action( 'admin_menu', array( $this, 'sailthru_menu' ) );

		// Setup the meta box hooks
		add_action( 'add_meta_boxes', array( $this, 'sailthru_post_metabox' ) );
		add_action( 'save_post', array( $this, 'save_custom_meta_data' ) );

	} // end constructor

	/**
	 * Fired when the plugin is activated.
	 *
	 * @param boolean $network_wide True if WPMU superadmin
	 *          uses "Network Activate" action, false if WPMU is
	 *          disabled or plugin is activated on an individual blog
	 */
	public static function activate( $network_wide ) {

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		// signal that it's ok to override WordPress's built-in email functions
		if ( false === get_option( 'sailthru_override_wp_mail' ) ) {
			add_option( 'sailthru_override_wp_mail', 1 );
		} // end if

	} // end activate

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @param boolean $network_wide True if WPMU superadmin
	 *          uses "Network Activate" action, false if WPMU is
	 *          disabled or plugin is activated on an individual blog
	 */
	public static function deactivate( $network_wide ) {

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		// stop overriding WordPress's built in email functions
		if ( false !== get_option( 'sailthru_override_wp_mail' ) ) {
			delete_option( 'sailthru_override_wp_mail' );
		}

		// we don't know if the API keys, etc, will still be
		// good, so kill the flag that said we knew.
		if ( false !== get_option( 'sailthru_setup_complete' ) ) {
			delete_option( 'sailthru_setup_complete' );
		}

		// remove all setup information including API key info
		if ( false !== get_option( 'sailthru_setup_options' ) ) {
			delete_option( 'sailthru_setup_options' );
		}

		// remove concierge settings
		if ( false !== get_option( 'sailthru_concierge_options' ) ) {
			delete_option( 'sailthru_concierge_options' );
		}

		// remove scout options
		if ( false !== get_option( 'sailthru_scout_options' ) ) {
			delete_option( 'sailthru_scout_options' );
		}

		// remove custom fields options
		if ( false !== get_option( 'sailthru_forms_options' ) ) {
			delete_option( 'sailthru_forms_options' );
		}

		// remove integrations options
		if ( false !== get_option( 'sailthru_integrations_options' ) ) {
			delete_option( 'sailthru_integrations_options' );
		}

	} // end deactivate

	/**
	 * Fired when the plugin is uninstalled.
	 *
	 * @param boolean $network_wide True if WPMU superadmin
	 *          uses "Network Activate" action, false if WPMU is
	 *          disabled or plugin is activated on an individual blog
	 */
	public static function uninstall( $network_wide ) {
		// nothing to see here.
	} // end uninstall


	public function sailthru_init() {

		/**
		 * Loads the plugin text domain for translation
		 */
		$domain = 'sailthru-for-wordpress-locale';
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
		load_textdomain( $domain, SAILTHRU_PLUGIN_PATH . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

		// Add a thumbnail size for concierge
		add_theme_support( 'post-thumbnails' );
		add_image_size( 'concierge-thumb', 50, 50 );

	} // end plugin_textdomain


	/*--------------------------------------------*
	 * Core Functions
	 *---------------------------------------------*/
	/**
	 * Add a top-level Sailthru menu and its submenus.
	 */
	function sailthru_menu() {

		$sailthru_menu                       = add_menu_page(
			'Sailthru',                                         // The value used to populate the browser's title bar when the menu page is active
			__( 'Sailthru', 'sailthru-for-wordpress' ),         // The text of the menu in the administrator's sidebar
			'manage_options',                                   // What roles are able to access the menu
			'sailthru_configuration_page',                      // The ID used to bind submenu items to this menu
			array( $this, 'load_sailthru_admin_display' ),      // The callback function used to render this menu
			SAILTHRU_PLUGIN_URL . 'img/sailthru-menu-icon.png'  // The icon to represent the menu item
		);
		$this->admin_views[ $sailthru_menu ] = 'sailthru_configuration_page';

		$redundant_menu                       = add_submenu_page(
			'sailthru_configuration_page',
			__( 'Welcome', 'sailthru-for-wordpress' ),
			__( 'Welcome', 'sailthru-for-wordpress' ),
			'manage_options',
			'sailthru_configuration_page',
			array( $this, 'load_sailthru_admin_display' )
		);
		$this->admin_views[ $redundant_menu ] = 'sailthru_configuration_page';

		$settings_menu                       = add_submenu_page(
			'sailthru_configuration_page',
			__( 'Settings', 'sailthru-for-wordpress' ),
			__( 'Settings', 'sailthru-for-wordpress' ),
			'manage_options',
			'settings_configuration_page',
			array( $this, 'load_sailthru_admin_display' )
		);
		$this->admin_views[ $settings_menu ] = 'settings_configuration_page';

		$concierge_menu                       = add_submenu_page(
			'sailthru_configuration_page',                          // The ID of the top-level menu page to which this submenu item belongs
			__( 'Concierge Options', 'sailthru-for-wordpress' ),    // The value used to populate the browser's title bar when the menu page is active
			__( 'Concierge Options', 'sailthru-for-wordpress' ),    // The label of this submenu item displayed in the menu
			'manage_options',                                       // What roles are able to access this submenu item
			'concierge_configuration_page',                         // The ID used to represent this submenu item
			array( $this, 'load_sailthru_admin_display' )           // The callback function used to render the options for this submenu item
		);
		$this->admin_views[ $concierge_menu ] = 'concierge_configuration_page';

		$scout_menu                       = add_submenu_page(
			'sailthru_configuration_page',
			__( 'Scout Options', 'sailthru-for-wordpress' ),
			__( 'Scout Options', 'sailthru-for-wordpress' ),
			'manage_options',
			'scout_configuration_page',
			array( $this, 'load_sailthru_admin_display' )
		);
		$this->admin_views[ $scout_menu ] = 'scout_configuration_page';

		$scout_menu                       = add_submenu_page(
			'sailthru_configuration_page',
			__( 'Subscribe Widget Fields', 'sailthru-for-wordpress' ),
			__( 'Subscribe Widget Fields', 'sailthru-for-wordpress' ),
			'manage_options',
			'custom_fields_configuration_page',
			array( $this, 'load_sailthru_admin_display' )
		);
		$this->admin_views[ $scout_menu ] = 'customforms_configuration_page';

		$forms_menu                       = add_submenu_page(
			'customforms_configuration_page',
			__( 'Custom Forms', 'sailthru-for-wordpress' ),
			__( 'Custom Forms', 'sailthru-for-wordpress' ),
			'manage_options',
			'customforms_configuration_page',
			array( $this, 'load_sailthru_admin_display' )
		);
		$this->admin_views[ $forms_menu ] = 'customforms_configuration_page';

		$integrations_menu                       = add_submenu_page(
			'sailthru_configuration_page',
			__( 'Integrations', 'sailthru-for-wordpress' ),
			__( 'Integrations', 'sailthru-for-wordpress' ),
			'manage_options',
			'integrations_configuration_page',
			array( $this, 'load_sailthru_admin_display' )
		);
		$this->admin_views[ $integrations_menu ] = 'integrations_configuration_page';

	} // end sailthru_menu

	/**
	 * Renders a simple page to display for the theme menu defined above.
	 */
	function load_sailthru_admin_display() {

		$active_tab = empty( $this->views[ current_filter() ] ) ? '' : $this->views[ current_filter() ];
		// display html
		include SAILTHRU_PLUGIN_PATH . 'views/admin.php';

	} // end sailthru_admin_display

	/*-------------------------------------------
	 * Utility Functions
	 *------------------------------------------*/

	/*
	 * Returns the portion of haystack which goes until the last occurrence of needle
	 * Credit: http://www.wprecipes.com/wordpress-improved-the_excerpt-function
	 */
	function reverse_strrchr( $haystack, $needle, $trail ) {
		return strrpos( $haystack, $needle ) ? substr( $haystack, 0, strrpos( $haystack, $needle ) + $trail ) : false;
	}

}
