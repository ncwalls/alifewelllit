<?php
/**
 * @package GFAddOn
 * @author  Rocketgenius
 */

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

use Gravity_Forms\Gravity_Forms\Settings\Settings;
use Gravity_Forms\Gravity_Forms\Settings\GF_Settings_Encryption;
use Gravity_Forms\Gravity_Forms\TranslationsPress_Updater;
use Gravity_Forms\Gravity_Forms\Save_Form\GF_Save_Form_Service_Provider;
use Gravity_Forms\Gravity_Forms\Save_Form\GF_Save_Form_Helper;
use Gravity_Forms\Gravity_Forms\Theme_Layers\Framework\Engines\Output_Engines\Form_CSS_Properties_Output_Engine;
use Gravity_Forms\Gravity_Forms\Theme_Layers\API\Fluent\Theme_Layer_Builder;

/**
 * Class GFAddOn
 *
 * Handles all tasks mostly common to any Gravity Forms Add-On, including third party ones.
 */
abstract class GFAddOn {

	/**
	 * @var string Version number of the Add-On
	 */
	protected $_version;
	/**
	 * The minimum Gravity Forms version required for the add-on to load.
	 *
	 * @var string Gravity Forms minimum version requirement
	 */
	protected $_min_gravityforms_version;

	/**
	 * The minimum Gravity Forms version required to support all the features of an add-on.
	 *
	 * Failing to meet this version won't prevent the add-on from loading, but some features of the add-on will not work as expected or will be disabled,
	 * A notice will be displayed in the admin asking the user to upgrade to the latest Gravity Form version.
	 *
	 * @var string Gravity Forms minimum version for supporting all features.
	 *
	 * @since 2.7.12
	 */
	protected $_min_compatible_gravityforms_version;

	/**
	 * @var string URL-friendly identifier used for form settings, add-on settings, text domain localization...
	 */
	protected $_slug;
	/**
	 * @var string Relative path to the plugin from the plugins folder. Example "gravityforms/gravityforms.php"
	 */
	protected $_path;
	/**
	 * @var string Full path to the plugin. Example: __FILE__
	 */
	protected $_full_path;
	/**
	 * @var string URL to the Gravity Forms website. Example: 'http://www.gravityforms.com' OR affiliate link.
	 */
	protected $_url;
	/**
	 * @var string Title of the plugin to be used on the settings page, form settings and plugins page. Example: 'Gravity Forms MailChimp Add-On'
	 */
	protected $_title;
	/**
	 * @var string Short version of the plugin title to be used on menus and other places where a less verbose string is useful. Example: 'MailChimp'
	 */
	protected $_short_title;
	/**
	 * @var array Members plugin integration. List of capabilities to add to roles.
	 */
	protected $_capabilities = array();
	/**
	 * @var string The hook suffix for the app menu
	 */
	public $app_hook_suffix;

	/**
	 * @var string The '.min' suffix to append to asset files in production mode.
	 */
	protected $_asset_min;

	private $_saved_settings = array();
	private $_previous_settings = array();

	/**
	 * Stores the current instance of the Settings renderer handling plugin/form/feed settings.
	 *
	 * @var false|\Gravity_Forms\Gravity_Forms\Settings\Settings
	 */
	private $_settings_renderer = false;

	/**
	 * @var array Stores a copy of setting fields that failed validation; only populated after validate_settings() has been called.
	 */
	private $_setting_field_errors = array();

	/**
	 * Stores the current instance of the Settings encryption class.
	 *
	 * @var \Gravity_Forms\Gravity_Forms\Settings\GF_Settings_Encryption
	 */
	private $_encryptor;

	// ------------ Permissions -----------
	/**
	 * @var string|array A string or an array of capabilities or roles that have access to the settings page
	 */
	protected $_capabilities_settings_page = array();
	/**
	 * @var string|array A string or an array of capabilities or roles that have access to the form settings
	 */
	protected $_capabilities_form_settings = array();
	/**
	 * @var string|array A string or an array of capabilities or roles that have access to the plugin page
	 */
	protected $_capabilities_plugin_page = array();
	/**
	 * @var string|array A string or an array of capabilities or roles that have access to the app menu
	 */
	protected $_capabilities_app_menu = array();
	/**
	 * @var string|array A string or an array of capabilities or roles that have access to the app settings page
	 */
	protected $_capabilities_app_settings = array();
	/**
	 * @var string|array A string or an array of capabilities or roles that can uninstall the plugin
	 */
	protected $_capabilities_uninstall = array();

	// ------------ RG Autoupgrade -----------

	/**
	 * @var bool Used by Rocketgenius plugins to activate auto-upgrade.
	 * @ignore
	 */
	protected $_enable_rg_autoupgrade = false;

	// ----------- Enable Theme Layer ------

	protected $_enable_theme_layer = false;

	// ------------ Private -----------

	private $_no_conflict_scripts = array();
	private $_no_conflict_styles = array();
	private $_preview_styles = array();
	private $_print_styles = array();
	private static $_registered_addons = array( 'active' => array(), 'inactive' => array() );

	/**
	 * Stores instances of the add-ons that implement the results/sales pages.
	 *
	 * @since 2.5.13
	 *
	 * @var array
	 */
	private static $results_addons = array();

	/**
	 * stores a list of the scripts that will be enqueued after passing _can_enqueue_script.
	 *
	 * @since 2.6
	 *
	 * @var array
	 */
	private static $registered_scripts = array();

	/**
	 * stores a list of the styles that will be enqueued after passing _can_enqueue_script.
	 *
	 * @since 2.6
	 *
	 * @var array
	 */
	private static $registered_styles = array();

	/**
	 * Class constructor which hooks the instance into the WordPress init action
	 */
	function __construct() {
		$this->_asset_min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$this->update_path();
		$this->bootstrap();

		if ( $this->_enable_rg_autoupgrade ) {
			require_once( 'class-gf-auto-upgrade.php' );
			$is_gravityforms_supported = $this->is_gravityforms_supported( $this->_min_gravityforms_version );
			new GFAutoUpgrade( $this->get_slug(), $this->_version, $this->_min_gravityforms_version, $this->_title, $this->_full_path, $this->get_path(), $this->_url, $is_gravityforms_supported );
		}

		$this->pre_init();
	}

	/**
	 * Attaches any filters or actions needed to bootstrap the addon.
	 *
	 * @since 2.5
	 */
	public function bootstrap() {
		add_action( 'init', array( $this, 'init' ), 15 );

		$is_admin_ajax = defined('DOING_AJAX') && DOING_AJAX;
		if ( $this->_enable_theme_layer && ! $is_admin_ajax ) {
			add_action( 'init', array( $this, 'init_theme_layer' ), 0, 0 );
		}
	}

	/**
	 * Initializes the theme layer process for the add-on.
	 *
	 * @since Unknown
	 *
	 */
	public function init_theme_layer() {
		$layer = new Theme_Layer_Builder();
		$layer->set_name( $this->theme_layer_slug() )
			  ->set_short_title( $this->theme_layer_title() )
			  ->set_priority( $this->theme_layer_priority() )
			  ->set_icon( $this->theme_layer_icon() )
			  ->set_settings_fields( $this->theme_layer_settings_fields() )
			  ->set_overidden_fields( $this->theme_layer_overridden_fields() )
			  ->set_form_css_properties( array( $this, 'theme_layer_form_css_properties' ) )
			  ->set_styles( array( $this, 'theme_layer_styles' ) )
			  ->set_scripts( array( $this, 'theme_layer_scripts' ) )
			  ->set_capability( $this->get_form_settings_capabilities() )
			  ->register();
		add_action( 'gform_form_after_open', array( $this, 'output_third_party_styles' ), 998, 2 );
	}

	/**
	 * Helper method that returns the theme styles that should be enqueued for the add-on. Returns an array in the format accepted by the Gravity Forms theme layer set_styles() method
	 *
	 * @since 2.9.0
	 *
	 * @param array  $form               The current form object to enqueue styles for.
	 * @param string $field_type         The field type associated with the add-on. Styles will only be enqueued on the frontend if the form has a field with the specified field type.
	 * @param string $gravity_theme_path The path to the gravity theme style. Optional. Only needed for add-ons that implement the gravity theme outside the default /assets/css/dist/theme.css path.
	 *
	 * @return array Returns and array of styles to enqueue in the format accepted by the Gravity Forms theme layer set_styles() method.
	 */
	public function get_theme_layer_styles( $form, $field_type = '', $gravity_theme_path = '' ) {

		if ( GFCommon::output_default_css() === false ) {
			return array();
		}

		$themes = $this->get_themes_to_enqueue( $form, $field_type );
		$styles = array();

		// Maybe enqueue theme framework.
		if ( in_array( 'orbital', $themes ) ) {
			$styles['foundation'] = array(
				array( "{$this->_slug}_theme_foundation", $this->get_base_url() . "/assets/css/dist/theme-foundation{$this->_asset_min}.css" ),
			);
			$styles['framework'] = array(
				array( "{$this->_slug}_theme_framework", $this->get_base_url() . "/assets/css/dist/theme-framework{$this->_asset_min}.css" ),
			);
		}

		// Maybe enqueue gravity theme.
		if ( in_array( 'gravity-theme', $themes ) ) {
			$path = $gravity_theme_path ? $gravity_theme_path : $this->get_base_url() . "/assets/css/dist/theme{$this->_asset_min}.css";
			$styles['theme'] = array(
				array( "{$this->_slug}_gravity_theme", $path ),
			);
		}

		return $styles;
	}

	/**
	 * Helper method that returns the themes that should be enqueued for the add-on. Returns an array of theme slugs.
	 *
	 * @since 2.9.0
	 *
	 * @param array        $form        The current form object to enqueue styles for.
	 * @param string|array $field_types The field type(s) associated with the add-on. Themes will only be enqueued on the frontend if the form has a field with the specified field type(s). Can be a string with a single field type or an array of strings with multiple field types.
	 *
	 * @return array Returns and array of theme slugs to enqueue.
	 */
	public function get_themes_to_enqueue ( $form, $field_types = '' ) {
		return \GFFormDisplay::get_themes_to_enqueue( $form, $field_types );
	}

	/**
	 * Registers an addon so that it gets initialized appropriately
	 *
	 * @param string $class - The class name
	 * @param string $overrides - Specify the class to replace/override
	 */
	public static function register( $class, $overrides = null ) {

		//Ignore classes that have been marked as inactive
		if ( in_array( $class, self::$_registered_addons['inactive'] ) ) {
			return;
		}

		//Mark classes as active. Override existing active classes if they are supposed to be overridden
		$index = array_search( $overrides, self::$_registered_addons['active'] );
		if ( $index !== false ) {
			self::$_registered_addons['active'][ $index ] = $class;
		} else {
			self::$_registered_addons['active'][] = $class;
		}

		//Mark overridden classes as inactive.
		if ( ! empty( $overrides ) ) {
			self::$_registered_addons['inactive'][] = $overrides;
		}

	}

	/**
	 * Gets all active, registered Add-Ons.
	 *
	 * @since Unknown
	 * @since 2.5.6  Added the $return_instances param.
	 * @since 2.9.2  Added the $slug_as_key param.
	 *
	 * @param bool $return_instances Indicates if the current instances of the add-ons should be returned. Default is false.
	 * @param bool $slug_as_key      Indicates if the add-on slug should be used as the key to the add-on instance. Default is false.
	 *
	 * @return string[]|(GFAddOn|GFFeedAddOn|GFPaymentAddOn)[] An array of class names or instances.
	 */
	public static function get_registered_addons( $return_instances = false, $slug_as_key = false ) {
		$active_addons = array_unique( self::$_registered_addons['active'] );

		if ( ! $return_instances ) {
			return $active_addons;
		}

		$instances = array();

		foreach ( $active_addons as $addon ) {
			$callback = array( $addon, 'get_instance' );
			if ( ! is_callable( $callback ) ) {
				continue;
			}

			/** @var GFAddOn|GFFeedAddOn|GFPaymentAddOn $instance */
			$instance = call_user_func( $callback );

			if ( $slug_as_key ) {
				$instances[ $instance->get_slug() ] = $instance;
			} else {
				$instances[] = $instance;
			}
		}

		return $instances;
	}

	/**
	 * Finds a registered add-on by its slug and return its instance.
	 *
	 * @since 2.9.1
	 *
	 * @param string $slug The add-on slug.
	 *
	 * @return GFAddOn Returns an instance of the add-on with the specified slug.
	 */
	public static function get_addon_by_slug( $slug ) {

		static $map = array();

		if ( isset( $map[ $slug ] ) ) {
			return $map[ $slug ];
		}

		$addons = GFAddOn::get_registered_addons( true );

		foreach ( $addons as $addon ) {
			$map[ $addon->get_slug() ] = $addon;
		}

		return rgar( $map, $slug );
	}

	/**
	 * Initializes all addons.
	 *
	 * @since Unknown
	 * @since 2.5.6 Updated to use get_registered_addons().
	 */
	public static function init_addons() {
		self::get_registered_addons( true );
	}

	/**
	 * Gets executed before all init functions. Override this function to perform initialization tasks that must be done prior to init
	 */
	public function pre_init() {

		if ( $this->is_gravityforms_supported() ) {

			//Entry meta
			if ( $this->method_is_overridden( 'get_entry_meta' ) ) {
				add_filter( 'gform_entry_meta', array( $this, 'get_entry_meta' ), 10, 2 );
			}
		}
	}

	/**
	 * Plugin starting point. Handles hooks and loading of language files.
	 */
	public function init() {

		$this->load_text_domain();
		$this->init_translations();

		add_filter( 'gform_logging_supported', array( $this, 'set_logging_supported' ) );

		add_action( 'gform_post_upgrade', array( $this, 'post_gravityforms_upgrade' ), 10, 3 );

		// Get minimum requirements state.
		$meets_requirements = $this->meets_minimum_requirements();

		// If saving form via AJAX initialize add-ons admin to catch any actions hooked to the after form save actions.
		$save_form_helper = GFForms::get_service_container()->get( GF_Save_Form_Service_Provider::GF_SAVE_FROM_HELPER );
		if ( RG_CURRENT_PAGE == 'admin-ajax.php' && $save_form_helper->is_ajax_save_action() ) {
			$this->init_admin();
		}

		if ( RG_CURRENT_PAGE == 'admin-ajax.php' ) {

			//If gravity forms is supported, initialize AJAX
			if ( $this->is_gravityforms_supported() && $meets_requirements['meets_requirements'] ) {
				$this->init_ajax();
			}
		} elseif ( is_admin() ) {

			$this->init_admin();

		} else {

			if ( $this->is_gravityforms_supported() && $meets_requirements['meets_requirements'] ) {
				$this->init_frontend();
			}
		}

	}

	/**
	 * Override this function to add initialization code (i.e. hooks) for the admin site (WP dashboard)
	 */
	public function init_admin() {
		$this->maybe_cache_gravityapi_oauth_response();

		// enqueues admin scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10, 0 );

		// message enforcing min version of Gravity Forms
		if ( isset( $this->_min_gravityforms_version ) && RG_CURRENT_PAGE == 'plugins.php' ) {
			add_action( 'after_plugin_row_' . $this->get_path(), array( $this, 'plugin_row' ), 10, 2 );
		}

		// STOP HERE IF CANNOT PASS MINIMUM REQUIREMENTS CHECK.
		$meets_requirements = $this->meets_minimum_requirements();
		if ( ! $meets_requirements['meets_requirements'] ) {
			$this->failed_requirements_init();
			return;
		}

		$this->setup();

		// Add form settings only when there are form settings fields configured or form_settings() method is implemented.
		if ( $this::has_form_settings_page() ) {
			/*
			 * Despite the "init_admin" name, the parent function is executed at init hook,
			 * so we need to run form_settings_init in admin_init to allow addons filter the settings.
			 */
			add_action( 'admin_init', array( $this, 'form_settings_init' ) );
		}

		// Add plugin page when there is a plugin page configured or plugin_page() method is implemented
		if ( self::has_plugin_page() ) {
			$this->plugin_page_init();
		}

		// Add addon settings page only when there are addon settings fields configured or settings_page() method is implemented
		if ( self::has_plugin_settings_page() ) {
			if ( $this->current_user_can_any( $this->_capabilities_settings_page ) ) {
				$this->plugin_settings_init();
			}
		}

		// creates the top level app left menu
		if ( self::has_app_menu() ) {
			if ( $this->current_user_can_any( $this->_capabilities_app_menu ) ) {
				add_action( 'admin_menu', array( $this, 'create_app_menu' ) );
			}
		}


		// Members plugin integration.
		if ( $this->has_members_plugin( ) ) {
			add_action( 'members_register_cap_groups', array( $this, 'members_register_cap_group' ), 11 );
			add_action( 'members_register_caps', array( $this, 'members_register_caps' ), 11 );
		}

		// User Role Editor integration.
		add_filter( 'ure_capabilities_groups_tree', array( $this, 'filter_ure_capabilities_groups_tree' ), 11 );
		add_filter( 'ure_custom_capability_groups', array( $this, 'filter_ure_custom_capability_groups' ), 10, 2 );

		// Results page
		if ( $this->method_is_overridden( 'get_results_page_config' ) ) {
			$results_page_config  = $this->get_results_page_config();
			$results_capabilities = rgar( $results_page_config, 'capabilities' );
			if ( $results_page_config && $this->current_user_can_any( $results_capabilities ) ) {
				$this->results_page_init( $results_page_config );
				// Store the configuration as it will be used later to decide which forms have results/sales page.
				self::$results_addons[] = $this->get_results_page_config();
			}
		}

		// Locking
		if ( $this->method_is_overridden( 'get_locking_config' ) ) {
			require_once( GFCommon::get_base_path() . '/includes/locking/class-gf-locking.php' );
			require_once( 'class-gf-addon-locking.php' );
			$config = $this->get_locking_config();
			new GFAddonLocking( $config, $this );
		}

		// No conflict scripts
		add_filter( 'gform_noconflict_scripts', array( $this, 'register_noconflict_scripts' ) );
		add_filter( 'gform_noconflict_styles', array( $this, 'register_noconflict_styles' ) );
		add_action( 'gform_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10, 2 );
		add_action( 'admin_notices', array( $this, 'maybe_display_upgrade_notice' ) );

	}

	/**
	 * Returns instances of the add-ons that implement the results/sales pages.
	 *
	 * @since 2.5.13
	 *
	 * @return array
	 */
	public static function get_results_addon() {
		return self::$results_addons;
	}

	/**
	 * Returns a list of the registered scripts that will be enqueued.
	 *
	 * This contains the scripts that pass _can_enqueue_script.
	 *
	 * @since 2.6
	 *
	 * @return array
	 */
	public static function get_registered_scripts() {
		return self::$registered_scripts;
	}

	/**
	 * Returns a list of the registered styles.
	 *
	 * This contains the styles that pass _can_enqueue_script.
	 *
	 * @since 2.6
	 *
	 * @return array
	 */
	public static function get_registered_styles() {
		return self::$registered_styles;
	}

	/**
	 * Override this function to add initialization code (i.e. hooks) for the public (customer facing) site
	 */
	public function init_frontend() {

		$this->setup();

		add_filter( 'gform_preview_styles', array( $this, 'enqueue_preview_styles' ), 10, 2 );
		add_filter( 'gform_print_styles', array( $this, 'enqueue_print_styles' ), 10, 2 );
		add_action( 'gform_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10, 2 );

	}

	/**
	 * Check for a response from the Gravity API and temporarily cache the value to a transient.
	 *
	 * This method cannot be extended because it's intended for use only by first-party Gravity Forms add-ons.
	 *
	 * @since 2.4.23
	 */
	private function maybe_cache_gravityapi_oauth_response() {
		GFForms::include_gravity_api();

		$referer     = isset( $_SERVER['HTTP_REFERER'] ) ? wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) ) : array();
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) : array();

		if (
			( rgar( $referer, 'host' ) !== rgar( wp_parse_url( GRAVITY_API_URL ), 'host' ) )
			|| empty( $request_uri )
		) {
			return;
		}

		// Set up post data.
		$data = array_filter(
			array(
				'auth_payload' => sanitize_text_field( rgpost( 'auth_payload' ) ),
				'state'        => sanitize_text_field( rgpost( 'state' ) ),
			)
		);

		// Get the query string to check which add-on is being authenticated.
		parse_str(
			rgar( $request_uri, 'query' ),
			$query
		);

		$addon = rgar( $query, 'subview' );

		if (
			// Couldn't determine the add-on, no request was cached, or the response doesn't contain what we expect.
			! $addon
			|| ! get_transient( "gravityapi_request_{$addon}" )
			|| count( $data ) !== 2
		) {
			return;
		}

		set_transient( "gravityapi_response_{$addon}", $data, 10 * MINUTE_IN_SECONDS );
	}

	/**
	 * Override this function to add AJAX hooks or to add initialization code when an AJAX request is being performed
	 */
	public function init_ajax() {
		if ( rgpost( 'view' ) == 'gf_results_' . $this->get_slug() ) {
			require_once( GFCommon::get_base_path() . '/tooltips.php' );
			require_once( 'class-gf-results.php' );
			$gf_results = new GFResults( $this->get_slug(), $this->get_results_page_config() );
			add_action( 'wp_ajax_gresults_get_results_gf_results_' . $this->get_slug(), array( $gf_results, 'ajax_get_results' ) );
			add_action( 'wp_ajax_gresults_get_more_results_gf_results_' . $this->get_slug(), array( $gf_results, 'ajax_get_more_results' ) );
		} elseif ( $this->method_is_overridden( 'get_locking_config' ) ) {
			require_once( GFCommon::get_base_path() . '/includes/locking/class-gf-locking.php' );
			require_once( 'class-gf-addon-locking.php' );
			$config = $this->get_locking_config();
			new GFAddonLocking( $config, $this );
		}

		if ( $this->has_plugin_settings_page() && $this->current_user_can_any( $this->_capabilities_settings_page ) ) {
			add_filter( 'plugin_action_links', array( $this, 'plugin_settings_link' ), 10, 2 );
		}
	}


	//--------------  Minimum Requirements Check  ---------------

	/**
	 * Override this function to provide a list of requirements needed to use Add-On.
	 *
	 * Custom requirements can be defined by adding a callback to the minimum requirements array.
	 * A custom requirement receives and should return an array with two parameters:
	 *   bool  $meets_requirements If the custom requirements check passed.
	 *   array $errors             An array of error messages to present to the user.
	 *
	 * Following is an example of the array that is expected to be returned by this function:
	 * @example https://gist.github.com/JeffMatson/a8d23e16e333e5116060906c6f091aa7
	 *
	 * @since  2.2
	 * @access public
	 *
	 * @return array
	 */
	public function minimum_requirements() {

		return array();

	}

	/**
	 * Performs a check to see if WordPress environment meets minimum requirements need to use Add-On.
	 *
	 * @since  2.2
	 * @access public
	 *
	 * @uses GFAddOn::minimum_requirements()
	 * @uses GFAddOn::get_slug()
	 *
	 * @return bool|array
	 */
	public function meets_minimum_requirements() {

		// Get minimum requirements.
		$requirements = $this->minimum_requirements();

		// Initialize response.
		$meets_requirements = array( 'meets_requirements' => true, 'errors' => array() );

		// Set an error if the minimum version of Gravity Forms is defined and the requirement is not met.
		if ( ! empty( $this->_min_gravityforms_version ) && ! $this->is_gravityforms_supported( $this->_min_gravityforms_version ) ) {
			$meets_requirements = array(
				'meets_requirements' => false,
				'errors'             => array(
					esc_html__(
						sprintf(
							'%s requires Gravity Forms %s or newer. Please upgrade your installation of Gravity Forms or disable this add-on to remove this message.',
							$this->_title,
							$this->_min_gravityforms_version
						),
						'gravityforms'
					),
				),
			);
		}

		// If no minimum requirements are defined, return.
		if ( empty( $requirements ) ) {
			return $meets_requirements;
		}

		// Loop through requirements.
		foreach ( $requirements as $requirement_type => $requirement ) {

			// If requirement is a callback, run it.
			if ( is_callable( $requirement ) ) {
				$meets_requirements = call_user_func( $requirement, $meets_requirements );
				continue;
			}

			// Set requirement type to lowercase.
			$requirement_type = strtolower( $requirement_type );

			// Run base requirement checks.
			switch ( $requirement_type ) {

				case 'add-ons':

					// Initialize active Add-Ons array.
					$active_addons = array();

					// Loop through active Add-Ons.
					foreach ( self::$_registered_addons['active'] as $active_addon ) {

						// Get Add-On instance.
						$active_addon = call_user_func( array( $active_addon, 'get_instance' ) );

						// Add to active Add-Ons array.
						$active_addons[ $active_addon->get_slug() ] = array(
							'slug'    => $active_addon->get_slug(),
							'title'   => $active_addon->_title,
							'version' => $active_addon->_version,
						);

					}

					// Loop through Add-Ons.
					foreach ( $requirement as $addon_slug => $addon_requirements ) {

						// If Add-On requirements is not an array, set Add-On slug to requirements value.
						if ( ! is_array( $addon_requirements ) ) {
							$addon_slug = $addon_requirements;
						}

						// If Add-On is not active, set error.
						if ( ! isset( $active_addons[ $addon_slug ] ) ) {

							// Get Add-On name.
							$addon_name = rgar( $addon_requirements, 'name' ) ? $addon_requirements['name'] : $addon_slug;

							$meets_requirements['meets_requirements'] = false;
							$meets_requirements['errors'][]           = sprintf( esc_html__( 'Required Gravity Forms Add-On is missing: %s.', 'gravityforms' ), $addon_name );
							continue;

						}

						// If Add-On does not meet minimum version, set error.
						if ( rgar( $addon_requirements, 'version' ) && ! version_compare( $active_addons[ $addon_slug ]['version'], $addon_requirements['version'], '>=' ) ) {
							$meets_requirements['meets_requirements'] = false;
							$meets_requirements['errors'][]           = sprintf( esc_html__( 'Required Gravity Forms Add-On "%s" does not meet minimum version requirement: %s.', 'gravityforms' ), $active_addons[ $addon_slug ]['title'], $addon_requirements['version'] );
							continue;
						}
					}

					break;

				case 'plugins':

					// Loop through plugins.
					foreach ( $requirement as $plugin_path => $plugin_config ) {

						// Handle legacy format where plugin_path is numeric index and plugin_name is the value
						if ( is_int( $plugin_path ) ) {
							$plugin_path    = $plugin_config;
							$plugin_name    = $plugin_config;
							$plugin_version = null;
						} else {
							$plugin_name    = is_array( $plugin_config ) ? $plugin_config['name'] : $plugin_config;
							$plugin_version = is_array( $plugin_config ) ? rgar( $plugin_config, 'version' ) : null;
						}

						// If plugin is not active, set error.
						if ( ! is_plugin_active( $plugin_path ) ) {
							$meets_requirements['meets_requirements'] = false;
							if( ! empty( $plugin_version ) ) {
								$meets_requirements['errors'][] = sprintf( esc_html__( 'Required WordPress plugin is missing: %1$s %2$s or newer.', 'gravityforms' ), $plugin_name, $plugin_version );
							} else {
								$meets_requirements['errors'][] = sprintf( esc_html__( 'Required WordPress plugin is missing: %s.', 'gravityforms' ), $plugin_name );
							}
							continue;
						}

						// If version requirement exists, verify it
						if ( ! empty( $plugin_version ) ) {
							if ( ! function_exists( 'get_plugin_data' ) ) {
								require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
							}

							$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_path );
							$installed_version = rgar( $plugin_data, 'Version' );

							if ( ! version_compare( $installed_version, $plugin_version, '>=' ) ) {
								$meets_requirements['meets_requirements'] = false;
								$meets_requirements['errors'][]           = sprintf( esc_html__( 'Required WordPress plugin "%1$s" is installed but does not meet minimum version requirement: %2$s.', 'gravityforms' ), $plugin_name, $plugin_version );
								continue;
							}
						}
					}

				case 'php':

					// Check version.
					if ( rgar( $requirement, 'version' ) && ! version_compare( PHP_VERSION, $requirement['version'], '>=' ) ) {
						$meets_requirements['meets_requirements'] = false;
						$meets_requirements['errors'][]           = sprintf( esc_html__( 'Current PHP version (%s) does not meet minimum PHP version requirement (%s).', 'gravityforms' ), PHP_VERSION, $requirement['version'] );
					}

					// Check extensions.
					if ( rgar( $requirement, 'extensions' ) ) {

						// Loop through extensions.
						foreach ( $requirement['extensions'] as $extension => $extension_requirements ) {

							// If extension requirements is not an array, set extension name to requirements value.
							if ( ! is_array( $extension_requirements ) ) {
								$extension = $extension_requirements;
							}

							// If PHP extension is not loaded, set error.
							if ( ! extension_loaded( $extension ) ) {
								$meets_requirements['meets_requirements'] = false;
								$meets_requirements['errors'][]           = sprintf( esc_html__( 'Required PHP extension missing: %s', 'gravityforms' ), $extension );
								continue;
							}

							// If PHP extension does not meet minimum version, set error.
							if ( rgar( $extension_requirements, 'version' ) && ! version_compare( phpversion( $extension ), $extension_requirements['version'], '>=' ) ) {
								$meets_requirements['meets_requirements'] = false;
								$meets_requirements['errors'][]           = sprintf( esc_html__( 'Required PHP extension "%s" does not meet minimum version requirement: %s.', 'gravityforms' ), $extension, $extension_requirements['version'] );
								continue;
							}

						}

					}

					// Check functions.
					if ( rgar( $requirement, 'functions' ) ) {

						// Loop through functions.
						foreach ( $requirement['functions'] as $function ) {
							if ( ! function_exists( $function ) ) {
								$meets_requirements['meets_requirements'] = false;
								$meets_requirements['errors'][]           = sprintf( esc_html__( 'Required PHP function missing: %s', 'gravityforms' ), $function );
							}
						}

					}

					break;

				case 'wordpress':

					// Check version.
					if ( rgar( $requirement, 'version' ) && ! version_compare( get_bloginfo( 'version' ), $requirement['version'], '>=' ) ) {
						$meets_requirements['meets_requirements'] = false;
						$meets_requirements['errors'][]           = sprintf( esc_html__( 'Current WordPress version (%s) does not meet minimum WordPress version requirement (%s).', 'gravityforms' ), get_bloginfo( 'version' ), $requirement['version'] );
					}

					break;

			}

		}

		return $meets_requirements;

	}

	/**
	 * Register failed requirements page under Gravity Forms settings.
	 *
	 * @since  2.2
	 * @access public
	 *
	 * @uses GFAddOn::current_user_can_any()
	 * @uses GFAddOn::get_short_title()
	 * @uses GFAddOn::plugin_settings_title()
	 * @uses GFCommon::get_base_path()
	 * @uses RGForms::add_settings_page()
	 */
	public function failed_requirements_init() {

		// Get failed requirements.
		$failed_requirements = $this->meets_minimum_requirements();

		// Prepare errors list.
		$errors = '';
		foreach ( $failed_requirements['errors'] as $error ) {
			$errors .= sprintf( '<li>%s</li>', esc_html( $error ) );
		}

		// Prepare error message.
		$error_message = sprintf(
			'%s<br />%s<ol>%s</ol>',
			sprintf( esc_html__( '%s is not able to run because your WordPress environment has not met the minimum requirements.', 'gravityforms' ), $this->_title ),
			sprintf( esc_html__( 'Please resolve the following issues to use %s:', 'gravityforms' ), $this->get_short_title() ),
			$errors
		);

		// Add error message.
		if ( $this->is_form_list() || $this->is_entry_list() || $this->is_form_settings() || $this->is_plugin_settings() || GFForms::get_page() === 'system_status' ) {
			GFCommon::add_error_message( $error_message );
		}

	}

	//--------------  Setup  ---------------

	/**
	 * Performs upgrade tasks when the version of the Add-On changes. To add additional upgrade tasks, override the upgrade() function, which will only get executed when the plugin version has changed.
	 */
	public function setup() {

		//Upgrading add-on
		$installed_version = get_option( 'gravityformsaddon_' . $this->get_slug() . '_version' );

		//Making sure version has really changed. Gets around aggressive caching issue on some sites that cause setup to run multiple times.
		if ( $installed_version != $this->_version ) {
			$installed_version = GFForms::get_wp_option( 'gravityformsaddon_' . $this->get_slug() . '_version' );
		}

		//Upgrade if version has changed
		if ( $installed_version != $this->_version ) {
			$this->install_translations();
			$this->upgrade( $installed_version );
			update_option( 'gravityformsaddon_' . $this->get_slug() . '_version', $this->_version );
		}
	}

	/**
	 * Override this function to add to add database update scripts or any other code to be executed when the Add-On version changes
	 */
	public function upgrade( $previous_version ) {
		return;
	}


	/**
	 * Gets called when Gravity Forms upgrade process is completed. This function is intended to be used internally, override the upgrade() function to execute database update scripts.
	 * @param $db_version - Current Gravity Forms database version
	 * @param $previous_db_version - Previous Gravity Forms database version
	 * @param $force_upgrade - True if this is a request to force an upgrade. False if this is a standard upgrade (due to version change)
	 */
	public function post_gravityforms_upgrade( $db_version, $previous_db_version, $force_upgrade ){

		// Forcing Upgrade
		if( $force_upgrade ){

			$installed_version = get_option( 'gravityformsaddon_' . $this->get_slug() . '_version' );

			$this->upgrade( $installed_version );
			update_option( 'gravityformsaddon_' . $this->get_slug() . '_version', $this->_version );

		}

	}

	//--------------  Script enqueuing  ---------------

	/**
	 * Override this function to provide a list of styles to be enqueued.
	 * When overriding this function, be sure to call parent::styles() to ensure the base class scripts are enqueued.
	 * See scripts() for an example of the format expected to be returned.
	 */
	public function styles() {
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';
		return array(
			array(
				'handle'  => 'gaddon_form_settings_css',
				'src'     => GFAddOn::get_gfaddon_base_url() . "/css/gaddon_settings{$min}.css",
				'version' => GFCommon::$version,
				'enqueue' => array(
					array( 'admin_page' => array( 'form_settings', 'plugin_settings', 'plugin_page', 'app_settings' ) ),
				)
			),
			array(
				'handle'  => 'gaddon_results_css',
				'src'     => GFAddOn::get_gfaddon_base_url() . "/css/gaddon_results{$min}.css",
				'version' => GFCommon::$version,
				'enqueue' => array(
					array( 'admin_page' => array( 'results' ) ),
				)
			),
		);
	}


	/**
	 * Override this function to provide a list of scripts to be enqueued.
	 * When overriding this function, be sure to call parent::scripts() to ensure the base class scripts are enqueued.
	 * Following is an example of the array that is expected to be returned by this function:
	 * <pre>
	 * <code>
	 *
	 *    array(
	 *        array(
	 *            'handle'    => 'maskedinput',
	 *            'src'       => GFCommon::get_base_url() . '/js/jquery.maskedinput-1.3.min.js',
	 *            'version'   => GFCommon::$version,
	 *            'deps'      => array( 'jquery' ),
	 *            'in_footer' => false,
	 *
	 *            // Determines where the script will be enqueued. The script will be enqueued if any of the conditions match.
	 *            'enqueue'   => array(
	 *                // admin_page - Specified one or more pages (known pages) where the script is supposed to be enqueued.
	 *                // To enqueue scripts in the front end (public website), simply don't define this setting.
	 *                array( 'admin_page' => array( 'form_settings', 'plugin_settings' ) ),
	 *
	 *                // tab - Specifies a form settings or plugin settings tab in which the script is supposed to be enqueued.
	 *                // If none are specified, the script will be enqueued in any of the form settings or plugin_settings page
	 *                array( 'tab' => 'signature'),
	 *
	 *                // query - Specifies a set of query string ($_GET) values.
	 *                // If all specified query string values match the current requested page, the script will be enqueued
	 *                array( 'query' => 'page=gf_edit_forms&view=settings&id=_notempty_' )
	 *
	 *                // post - Specifies a set of post ($_POST) values.
	 *                // If all specified posted values match the current request, the script will be enqueued
	 *                array( 'post' => 'posted_field=val' )
	 *
	 *                // If a nested condition is used, it will be considered a "match" if ALL sub-conditions match.
	 *                // In the following example, the condition will match if you are on the plugin settings page AND on the signature tab
	 *                array(
	 *                    'admin_page' => array( 'plugin_settings' )
	 *                    'tab'        => 'signature',
	 *                ),
	 *            )
	 *        ),
	 *        array(
	 *            'handle'   => 'super_signature_script',
	 *            'src'      => $this->get_base_url() . '/super_signature/ss.js',
	 *            'version'  => $this->_version,
	 *            'deps'     => array( 'jquery'),
	 *            'callback' => array( $this, 'localize_scripts' ),
	 *            'strings'  => array(
	 *                // Accessible in JavaScript using the global variable "[script handle]_strings"
	 *                'stringKey1' => __( 'The string', 'gravityforms' ),
	 *                'stringKey2' => __( 'Another string.', 'gravityforms' )
	 *            )
	 *            "enqueue"  => array(
	 *                // field_types - Specifies one or more field types that requires this script.
	 *                // The script will only be enqueued if the current form has a field of any of the specified field types.
	 *                // Only applies when a current form is available (website front end, but also in the form editor, preview, entry details, results, etc...)
	 *                array( 'field_types' => array( 'signature' ) )
	 *            )
	 *        )
	 *    );
	 *
	 * </code>
	 * </pre>
	 */
	public function scripts() {
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';
		return array(
			array(
				'handle'  => 'gform_form_admin',
				'enqueue' => array( array( 'admin_page' => array( 'form_settings' ) ) )
			),
			array(
				'handle'  => 'gform_gravityforms',
				'enqueue' => array( array( 'admin_page' => array( 'form_settings' ) ) )
			),
			array(
				'handle'  => 'google_charts',
				'src'     => 'https://www.google.com/jsapi',
				'version' => GFCommon::$version,
				'enqueue' => array(
					array( 'admin_page' => array( 'results' ) ),
				)
			),
			array(
				'handle'   => 'gaddon_results_js',
				'src'      => GFAddOn::get_gfaddon_base_url() . "/js/gaddon_results{$min}.js",
				'version'  => GFCommon::$version,
				'deps'     => array( 'jquery', 'sack', 'jquery-ui-resizable', 'gform_datepicker_init', 'google_charts', 'gform_field_filter' ),
				'callback' => array( 'GFResults', 'localize_results_scripts' ),
				'enqueue'  => array(
					array( 'admin_page' => array( 'results' ) ),
				)
			),
			array(
				'handle'  => 'gaddon_repeater',
				'src'     => GFAddOn::get_gfaddon_base_url() . "/js/repeater{$min}.js",
				'version' => GFCommon::$version,
				'deps'    => array( 'jquery' ),
				'enqueue' => array(
					array(
						'admin_page' => array( 'form_settings' ),
					),
				),
			),
			array(
				'handle'   => 'gaddon_fieldmap_js',
				'src'      => GFAddOn::get_gfaddon_base_url() . "/js/gaddon_fieldmap{$min}.js",
				'version'  => GFCommon::$version,
				'deps'     => array( 'jquery', 'gaddon_repeater' ),
				'enqueue'  => array(
					array( 'admin_page' => array( 'form_settings' ) ),
				)
			),
			array(
				'handle'   => 'gaddon_genericmap_js',
				'src'      => GFAddOn::get_gfaddon_base_url() . "/js/gaddon_genericmap{$min}.js",
				'version'  => GFCommon::$version,
				'deps'     => array( 'jquery', 'gaddon_repeater' ),
				'enqueue'  => array(
					array( 'admin_page' => array( 'form_settings' ) ),
				)
			),
		);
	}


	/**
	 * Target of admin_enqueue_scripts and gform_enqueue_scripts hooks.
	 * Not intended to be overridden by child classes.
	 * In order to enqueue scripts and styles, override the scripts() and styles() functions
	 *
	 * @ignore
	 */
	public function enqueue_scripts( $form = '', $is_ajax = false ) {

		if ( empty( $form ) ) {
			$form = $this->get_current_form();
		}

		//Enqueueing scripts
		$scripts = $this->scripts();
		foreach ( $scripts as $script ) {
			$src       = isset( $script['src'] ) ? $script['src'] : false;
			$deps      = isset( $script['deps'] ) ? $script['deps'] : array();
			$version   = array_key_exists( 'version', $script ) ? $script['version'] : false;
			$in_footer = isset( $script['in_footer'] ) ? $script['in_footer'] : false;
			wp_register_script( $script['handle'], $src, $deps, $version, $in_footer );
			if ( isset( $script['enqueue'] ) && $this->_can_enqueue_script( $script['enqueue'], $form, $is_ajax ) ) {
				$this->add_no_conflict_scripts( array( $script['handle'] ) );
				wp_enqueue_script( $script['handle'] );
				self::$registered_scripts[] = $script;
				if ( isset( $script['strings'] ) ) {
					wp_localize_script( $script['handle'], $script['handle'] . '_strings', $script['strings'] );
				}
				if ( isset( $script['callback'] ) && is_callable( $script['callback'] ) ) {
					$args = compact( 'form', 'is_ajax' );
					call_user_func_array( $script['callback'], array_values( $args ) );
				}
			}
		}

		//Enqueueing styles
		$styles = $this->styles();
		foreach ( $styles as $style ) {
			$src     = isset( $style['src'] ) ? $style['src'] : false;
			$deps    = isset( $style['deps'] ) ? $style['deps'] : array();
			$version = array_key_exists( 'version', $style ) ? $style['version'] : false;
			$media   = isset( $style['media'] ) ? $style['media'] : 'all';
			wp_register_style( $style['handle'], $src, $deps, $version, $media );
			if ( $this->_can_enqueue_script( $style['enqueue'], $form, $is_ajax ) ) {
				self::$registered_styles[] = $style;
				$this->add_no_conflict_styles( array( $style['handle'] ) );
				if ( $this->is_preview() ) {
					$this->_preview_styles[] = $style['handle'];
				} elseif ( $this->is_print() ) {
					$this->_print_styles[] = $style['handle'];
				} else {
					wp_enqueue_style( $style['handle'] );
				}
			}
		}

	}

	/**
	 * Target of gform_preview_styles. Enqueue styles to the preview page.
	 * Not intended to be overridden by child classes
	 *
	 * @ignore
	 */
	public function enqueue_preview_styles( $preview_styles, $form ) {
		return array_merge( $preview_styles, $this->_preview_styles );
	}


	/**
	 * Target of gform_print_styles. Enqueue styles to the print entry page.
	 * Not intended to be overridden by child classes
	 *
	 * @ignore
	 */
	public function enqueue_print_styles( $print_styles, $form ) {
		if ( false === $print_styles ) {
			$print_styles = array();
		}

		$styles = $this->styles();
		foreach ( $styles as $style ) {
			if ( $this->_can_enqueue_script( $style['enqueue'], $form, false ) ) {
				$this->add_no_conflict_styles( array( $style['handle'] ) );
				$src     = isset( $style['src'] ) ? $style['src'] : false;
				$deps    = isset( $style['deps'] ) ? $style['deps'] : array();
				$version = isset( $style['version'] ) ? $style['version'] : false;
				$media   = isset( $style['media'] ) ? $style['media'] : 'all';
				wp_register_style( $style['handle'], $src, $deps, $version, $media );
				$print_styles[] = $style['handle'];
			}
		}

		return array_merge( $print_styles, $this->_print_styles );
	}

	//--------------  Theme Layers  ---------------

	/**
	 * The title to display for this theme layer - defaults to the addon short title.
	 *
	 * @since 2.7
	 *
	 * @return string
	 */
	public function theme_layer_title() {
		return $this->_short_title;
	}

	/**
	 * The slug to display for this theme layer - defaults to the addon slug.
	 *
	 * @since 2.7
	 *
	 * @return string
	 */
	public function theme_layer_slug() {
		return $this->get_slug();
	}

	/**
	 * The icon to use for displaying on settings pages, etc. Defaults to user icon.
	 *
	 * @since 2.7
	 *
	 * @return string
	 */
	public function theme_layer_icon() {
		return 'gform-icon--user';
	}

	/**
	 * Provides the priority for this theme layer.
	 *
	 * @since 2.7
	 *
	 * @return int
	 */
	public function theme_layer_priority() {
		return 0;
	}

	/**
	 * Defines the various setting fields to display on the Form Settings screen for this theme layer.
	 *
	 * @since 2.7
	 *
	 * @return array[]
	 */
	public function theme_layer_settings_fields() {
		return array();
	}

	/**
	 * The fields/views to override for this theme layer.
	 *
	 * @since 2.7
	 *
	 * @return string[]
	 */
	public function theme_layer_overridden_fields() {
		return array();
	}

	/**
	 * The form CSS properties to output based on settings, block settings, or arbitrary conditions.
	 *
	 * These styles are output as a style block both at the top of every form wrapper, as well as
	 * at the top of the Full Screen template.
	 *
	 * @since 2.7
	 *
	 * @param $form_id
	 * @param $settings
	 * @param $block_settings
	 *
	 * @return array|null[]
	 */
	public function theme_layer_form_css_properties( $form_id, $settings, $block_settings ) {
		return array();
	}

	/**
	 * An array of styles to enqueue.
	 *
	 * @since 2.7
	 *
	 * @param $form
	 * @param $ajax
	 * @param $settings
	 * @param $block_settings
	 *
	 * @return array
	 */
	public function theme_layer_styles( $form, $ajax, $settings, $block_settings = array() ) {
		return array();
	}

	/**
	 * An array of scripts to enqueue.
	 *
	 * @since 2.7
	 *
	 * @param $form
	 * @param $ajax
	 * @param $settings
	 * @param $block_settings
	 *
	 * @return array
	 */
	public function theme_layer_scripts( $form, $ajax, $settings, $block_settings = array() ) {
		return array();
	}

	/**
	 * Provides third party styles to apply for this theme layer.
	 *
	 * @since 2.7
	 *
	 * @return array
	 */
	public function theme_layer_third_party_styles( $form_id, $settings, $block_settings ) {
		return array();
	}

	/**
	 * Outputs third-party styles to pass to JS-powered widgets like payment modals, etc.
	 *
	 * @since 2.7
	 *
	 * @param $markup
	 * @param $form
	 *
	 * @return mixed|string
	 */
	public function output_third_party_styles( $markup, $form ) {
		$settings           = $this->get_current_settings();
		$all_block_settings = apply_filters( 'gform_form_block_attribute_values', array() );
		$page_instance      = isset( $form['page_instance'] ) ? $form['page_instance'] : 0;
		$block_settings     = isset( $all_block_settings[ $form['id'] ][ $page_instance ] ) ? $all_block_settings[ $form['id'] ][ $page_instance ] : array();
		$properties         = call_user_func_array( array( $this, 'theme_layer_third_party_styles' ), array( $form['id'], $settings, $block_settings ) );

		if ( empty( $properties ) ) {
			return $markup;
		}

		$base_identifier = sprintf( 'gform.extensions.styles.%s', $this->get_slug() );
		$form_identifier = sprintf( 'gform.extensions.styles.%s[%s]', $this->get_slug(), $form['id'] );
		$full_identifier = sprintf( 'gform.extensions.styles.%s[%s][%s]', $this->get_slug(), $form['id'], $page_instance );

		ob_start(); ?>

		<script>
			if ( typeof gform !== 'undefined' ) {
				gform.extensions = gform.extensions || {};
				gform.extensions.styles = gform.extensions.styles || {};
				<?php echo $base_identifier; ?> = <?php echo $base_identifier; ?> || {};
				<?php echo $form_identifier; ?> = <?php echo $form_identifier; ?> || {};
				<?php echo $full_identifier; ?> = <?php echo json_encode( $properties ); ?>;
			}
		</script>

		<?php

		$props = ob_get_clean();
		return $markup . $props;
	}


	/**
	 * Adds scripts to the list of white-listed no conflict scripts.
	 *
	 * @param $scripts
	 */
	private function add_no_conflict_scripts( $scripts ) {
		$this->_no_conflict_scripts = array_merge( $scripts, $this->_no_conflict_scripts );

	}

	/**
	 * Adds styles to the list of white-listed no conflict styles.
	 *
	 * @param $styles
	 */
	private function add_no_conflict_styles( $styles ) {
		$this->_no_conflict_styles = array_merge( $styles, $this->_no_conflict_styles );
	}

	private function _can_enqueue_script( $enqueue_conditions, $form = array(), $is_ajax = false ) {
		if ( empty( $enqueue_conditions ) ) {
			return false;
		}

		foreach ( $enqueue_conditions as $condition ) {
			if ( is_callable( $condition ) ) {
				$callback_matches = call_user_func( $condition, $form, $is_ajax );
				if ( $callback_matches ) {
					return true;
				}
			} else {
				$query_matches      = isset( $condition['query'] ) ? $this->_request_condition_matches( $_GET, $condition['query'] ) : true;
				$post_matches       = isset( $condition['post'] ) ? $this->_request_condition_matches( $_POST, $condition['post'] ) : true;
				$admin_page_matches = isset( $condition['admin_page'] ) ? $this->_page_condition_matches( $condition['admin_page'], rgar( $condition, 'tab' ) ) : true;
				$field_type_matches = isset( $condition['field_types'] ) ? $this->_field_condition_matches( $condition['field_types'], $form ) : true;

				if ( $query_matches && $post_matches && $admin_page_matches && $field_type_matches ) {
					return true;
				}
			}
		}

		return false;
	}

	private function _request_condition_matches( $request, $query ) {
		parse_str( $query, $query_array );
		foreach ( $query_array as $key => $value ) {

			switch ( $value ) {
				case '_notempty_' :
					if ( rgempty( $key, $request ) ) {
						return false;
					}
					break;
				case '_empty_' :
					if ( ! rgempty( $key, $request ) ) {
						return false;
					}
					break;
				default :
					if ( rgar( $request, $key ) != $value ) {
						return false;
					}
					break;
			}
		}

		return true;
	}

	private function _page_condition_matches( $pages, $tab ) {
		if ( ! is_array( $pages ) ) {
			$pages = array( $pages );
		}

		foreach ( $pages as $page ) {
			switch ( $page ) {
				case 'form_editor':
					if ( $this->is_form_editor() ) {
						return true;
					}

					break;

				case 'form_list':
					if ( $this->is_form_list() ) {
						return true;
					}

					break;

				case 'form_settings':
					if ( $this->is_form_settings( $tab ) ) {
						return true;
					}

					break;

				case 'plugin_settings':
					if ( $this->is_plugin_settings( $tab ) ) {
						return true;
					}

					break;

				case 'app_settings':
					if ( $this->is_app_settings( $tab ) ) {
						return true;
					}

					break;

				case 'plugin_page':
					if ( $this->is_plugin_page() ) {
						return true;
					}

					break;

				case 'entry_list':
					if ( $this->is_entry_list() ) {
						return true;
					}

					break;

				case 'entry_view':
					if ( $this->is_entry_view() ) {
						return true;
					}

					break;

				case 'entry_edit':
					if ( $this->is_entry_edit() ) {
						return true;
					}

					break;

				case 'results':
					if ( $this->is_results() ) {
						return true;
					}

					break;

				case 'customizer':
					if ( is_customize_preview() ) {
						return true;
					}

					break;

				case 'block_editor':
					if ( $this->is_block_editor() ) {
						return true;
					}

					break;
			}
		}

		return false;

	}

	private function _field_condition_matches( $field_types, $form ) {
		if ( ! is_array( $field_types ) ) {
			$field_types = array( $field_types );
		}

		/* @var GF_Field[] $fields */
		$fields = GFAPI::get_fields_by_type( $form, $field_types );
		if ( count( $fields ) > 0 ) {
			foreach ( $fields as $field ) {
				if ( $field->is_administrative() && ! $field->allowsPrepopulate && ! GFForms::get_page() ) {
					continue;
				}

				return true;
			}
		}

		return false;
	}

	/**
	 * Target for the gform_noconflict_scripts filter. Adds scripts to the list of white-listed no conflict scripts.
	 *
	 * Not intended to be overridden or called directed by Add-Ons.
	 *
	 * @ignore
	 *
	 * @param array $scripts Array of scripts to be white-listed
	 *
	 * @return array
	 */
	public function register_noconflict_scripts( $scripts ) {
		//registering scripts with Gravity Forms so that they get enqueued when running in no-conflict mode
		return array_merge( $scripts, $this->_no_conflict_scripts );
	}

	/**
	 * Target for the gform_noconflict_styles filter. Adds styles to the list of white-listed no conflict scripts.
	 *
	 * Not intended to be overridden or called directed by Add-Ons.
	 *
	 * @ignore
	 *
	 * @param array $styles Array of styles to be white-listed
	 *
	 * @return array
	 */
	public function register_noconflict_styles( $styles ) {
		//registering styles with Gravity Forms so that they get enqueued when running in no-conflict mode
		return array_merge( $styles, $this->_no_conflict_styles );
	}



	//--------------  Entry meta  --------------------------------------

	/**
	 * Override this method to activate and configure entry meta.
	 *
	 *
	 * @param array $entry_meta An array of entry meta already registered with the gform_entry_meta filter.
	 * @param int   $form_id    The form id
	 *
	 * @return array The filtered entry meta array.
	 */
	public function get_entry_meta( $entry_meta, $form_id ) {
		return $entry_meta;
	}


	//--------------  Results page  --------------------------------------
	/**
	 * Returns the configuration for the results page. By default this is not activated.
	 * To activate the results page override this function and return an array with the configuration data.
	 *
	 * Example:
	 * public function get_results_page_config() {
	 *      return array(
	 *       "title" => 'Quiz Results',
	 *       "capabilities" => array("gravityforms_quiz_results"),
	 *       "callbacks" => array(
	 *          "fields" => array($this, 'results_fields'),
	 *          "calculation" => array($this, 'results_calculation'),
	 *          "markup" => array($this, 'results_markup'),
	 *              )
	 *       );
	 * }
	 *
	 * @return array|bool
	 */
	public function get_results_page_config() {
		return false;
	}

	/**
	 * Initializes the result page functionality. To activate result page functionality, override the get_results_page_config() function.
	 *
	 * @param $results_page_config - configuration returned by get_results_page_config()
	 */
	public function results_page_init( $results_page_config ) {
		require_once( 'class-gf-results.php' );

		if ( isset( $results_page_config['callbacks']['filters'] ) ) {
			add_filter( 'gform_filters_pre_results', $results_page_config['callbacks']['filters'], 10, 2 );
		}

		if ( isset( $results_page_config['callbacks']['filter_ui'] ) ) {
			add_filter( 'gform_filter_ui', $results_page_config['callbacks']['filter_ui'], 10, 5 );
		}

		$gf_results = new GFResults( $this->get_slug(), $results_page_config );
		$gf_results->init();
	}

	//--------------  Logging integration  --------------------------------------

	public function set_logging_supported( $plugins ) {
		$plugins[ $this->get_slug() ] = $this->_title;

		return $plugins;
	}





	// # PERMISSIONS ---------------------------------------------------------------------------------------------------

	/**
	 * Checks whether the Members plugin is installed and activated.
	 *
	 * Not intended to be overridden or called directly by Add-Ons.
	 *
	 * @ignore
	 *
	 * @return bool
	 */
	public function has_members_plugin() {
		return GFForms::has_members_plugin();
	}

	/**
	 * Register the Gravity Forms Add-Ons capabilities group with the Members plugin.
	 *
	 * @since  2.4
	 * @access public
	 */
	public function members_register_cap_group() {

		members_register_cap_group(
			'gravityforms_addons',
			array(
				'label' => esc_html__( 'GF Add-Ons', 'gravityforms' ),
				'icon'  => 'dashicons-gravityforms',
				'caps'  => array(),
			)
		);

	}

	/**
	 * Register the Add-On capabilities and their human readable labels with the Members plugin.
	 *
	 * @since  2.4
	 * @access public
	 *
	 * @uses   GFAddOn::get_short_title()
	 */
	public function members_register_caps() {

		// Get capabilities.
		$caps = $this->get_members_caps();

		// If no capabilities were found, exit.
		if ( empty( $caps ) ) {
			return;
		}

		// Register capabilities.
		foreach ( $caps as $cap => $label ) {
			members_register_cap(
				$cap,
				array(
					'label' => sprintf( '%s: %s', $this->get_short_title(), $label ),
					'group' => 'gravityforms_addons',
				)
			);
		}

	}

	/**
	 * Get Add-On capabilities and their human readable labels.
	 *
	 * @since  2.4
	 * @access public
	 *
	 * @return array
	 */
	public function get_members_caps() {

		// Initialize capabilities array.
		$caps = array();

		// Add capabilities.
		if ( ! empty( $this->get_form_settings_capabilities() ) && is_string( $this->get_form_settings_capabilities() ) ) {
			$caps[ $this->get_form_settings_capabilities() ] = esc_html__( 'Form Settings', 'gravityforms' );
		}
		if ( ! empty( $this->_capabilities_uninstall ) && is_string( $this->_capabilities_uninstall ) ) {
			$caps[ $this->_capabilities_uninstall ] = esc_html__( 'Uninstall', 'gravityforms' );
		}
		if ( ! empty( $this->_capabilities_plugin_page ) && is_string( $this->_capabilities_plugin_page ) ) {
			$caps[ $this->_capabilities_plugin_page ] = esc_html__( 'Add-On Page', 'gravityforms' );
		}
		if ( ! empty( $this->_capabilities_settings_page ) && is_string( $this->_capabilities_settings_page ) ) {
			$caps[ $this->_capabilities_settings_page ] = esc_html__( 'Add-On Settings', 'gravityforms' );
		}

		$results_cap = rgars( $this->get_results_page_config(), 'capabilities/0' );
		if ( ! empty( $results_cap ) && $results_cap !== 'gravityforms_view_entries' && ! isset( $caps[ $results_cap ] ) ) {
			$caps[ $results_cap ] = esc_html__( 'Results Page', 'gravityforms' );
		}

		return $caps;

	}

	/**
	 * Register Gravity Forms Add-Ons capabilities group with User Role Editor plugin.
	 *
	 * @since  2.4
	 *
	 * @param array $groups Existing capabilities groups.
	 *
	 * @return array
	 */
	public static function filter_ure_capabilities_groups_tree( $groups = array() ) {

		$groups['gravityforms_addons'] = array(
			'caption' => esc_html__( 'Gravity Forms Add-Ons', 'gravityforms' ),
			'parent'  => 'gravityforms',
			'level'   => 3,
		);

		return $groups;

	}

	/**
	 * Register Gravity Forms capabilities with Gravity Forms group in User Role Editor plugin.
	 *
	 * @since  2.4
	 *
	 * @param array  $groups Current capability groups.
	 * @param string $cap_id Capability identifier.
	 *
	 * @return array
	 */
	public function filter_ure_custom_capability_groups( $groups = array(), $cap_id = '' ) {

		// Get Add-On capabilities.
		$caps = $this->_capabilities;

		// If capability belongs to Add-On, register it to group.
		if ( in_array( $cap_id, $caps, true ) ) {
			$groups[] = 'gravityforms_addons';
		}

		return $groups;

	}

	/**
	 * Checks whether the current user is assigned to a capability or role.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param string|array $caps An string or array of capabilities to check
	 *
	 * @return bool Returns true if the current user is assigned to any of the capabilities.
	 */
	public function current_user_can_any( $caps ) {
		return GFCommon::current_user_can_any( $caps );
	}




	// # SETTINGS RENDERER ---------------------------------------------------------------------------------------------

	/**
	 * Gets the current instance of Settings handling settings rendering.
	 *
	 * @since 2.5
	 *
	 * @return false|\Gravity_Forms\Gravity_Forms\Settings
	 */
	public function get_settings_renderer() {

		return $this->_settings_renderer;

	}

	/**
	 * Sets the current instance of Settings handling settings rendering.
	 *
	 * @since 2.5
	 *
	 * @param \Gravity_Forms\Gravity_Forms\Settings\Settings $renderer Settings renderer.
	 *
	 * @return bool|WP_Error
	 */
	public function set_settings_renderer( $renderer ) {

		// Ensure renderer is an instance of Gravity_Forms\Gravity_Forms\Settings\Settings.
		if ( ! is_a( $renderer, 'Gravity_Forms\Gravity_Forms\Settings\Settings' ) ) {
			return new WP_Error( 'Renderer must be an instance of Gravity_Forms\Gravity_Forms\Settings\Settings.' );
		}

		$this->_settings_renderer = $renderer;

		return true;

	}

	/**
	 * Prepare legacy settings sections for Settings renderer.
	 *
	 * @since 2.5
	 *
	 * @param array  $sections Array of settings fields.
	 * @param string $type     Settings type: plugin_settings, form_settings, feed_settings, app_settings
	 *
	 * @return array
	 */
	public function prepare_settings_sections( $sections = array(), $type = 'plugin_settings' ) {

		// Get first section key.
		$first_section = array_keys( $sections );
		$first_section = array_shift( $first_section );

		foreach ( $sections as $s => &$section ) {
			if ( array_key_exists( 'sections', $section ) ) {
				foreach ( $section['sections'] as &$sub_section ) {
					if ( isset( $sub_section['fields'] ) ) {
						$sub_section['fields'] = $this->prepare_settings_fields( $sub_section['fields'] );
					}
				}
			} else {
				// If this is the first section, set title.
				if ( $s === $first_section && in_array( $type, array( 'plugin_settings' ) ) && ! rgar( $section, 'title', false ) ) {
					$section['title'] = sprintf( esc_html__( '%s Settings', 'gravityforms' ), $this->get_short_title() );
				}

				$this->prepare_settings_fields( $section['fields'] );
			}
		}

		return $sections;

	}

	/**
	 * Prepare legacy settings fields for Settings renderer.
	 *
	 * @since 2.5
	 *
	 * @param array $fields Array of fields.
	 *
	 * @return array
	 */
	public function prepare_settings_fields( &$fields = array() ) {

		// Set callback.
		foreach ( $fields as &$field ) {

			// Handle conditional logic.
			if ( $field['type'] === 'feed_condition' ) {
				$field['type']        = 'conditional_logic';
				$field['object_type'] = 'feed_condition';
			}

			// Get method names.
			$callback_name   = sprintf( 'settings_%s', rgar( $field, 'type' ) );
			$validation_name = sprintf( 'validate_%s_settings', rgar( $field, 'type' ) );

			if ( $this->method_is_overridden( $callback_name ) ) {
				$field['callback'] = array( $this, $callback_name );
			} elseif ( method_exists( $this, $callback_name ) && ! method_exists( get_parent_class( $this ), $callback_name ) ) {
				$field['callback'] = array( $this, $callback_name );
			}

			if ( $this->method_is_overridden( $validation_name ) ) {
				$field['legacy_validation_callback'] = array( $this, $validation_name );
			} elseif ( method_exists( $this, $validation_name ) && ! method_exists( get_parent_class( $this ), $validation_name ) ) {
				$field['legacy_validation_callback'] = array( $this, $validation_name );
			}

			if ( rgar( $field, 'fields' ) ) {
				$this->prepare_settings_fields( $field['fields'] );
			}

		}

		return $fields;

	}





	//------- Settings Helper Methods (Common to all settings pages) -------------------

	/***
	 * Renders the UI of all settings page based on the specified configuration array $sections
	 *
	 * @param array $sections - Configuration array containing all fields to be rendered grouped into sections
	 */
	public function render_settings( $sections ) {

		if ( ! $this->has_setting_field_type( 'save', $sections ) ) {
			$sections = $this->add_default_save_button( $sections );
		}

		?>

		<form id="gform-settings" action="" method="post">
			<?php wp_nonce_field( $this->get_slug() . '_save_settings', '_' . $this->get_slug() . '_save_settings_nonce' ) ?>
			<?php $this->settings( $sections ); ?>

		</form>

	<?php
	}

	/***
	 * Renders settings fields based on the specified configuration array $sections
	 *
	 * @param array $sections - Configuration array containing all fields to be rendered grouped into sections
	 */
	public function settings( $sections ) {
		$is_first = true;
		foreach ( $sections as $section ) {
			if ( $this->setting_dependency_met( rgar( $section, 'dependency' ) ) ) {
				$this->single_section( $section, $is_first );
			}

			$is_first = false;
		}
	}

	/***
	 * Displays the UI for a field section
	 *
	 * @param array $section  - The section to be displayed
	 * @param bool  $is_first - true for the first section in the list, false for all others
	 */
	public function single_section( $section, $is_first = false ) {

		/**
		 * @var bool|string $title
		 * @var bool|string $description
		 * @var string      $id
		 * @var bool|string $class
		 * @var string      $style
		 * @var bool|string $tooltip
		 * @var string      $tooltip_class
		 */
		extract(
			wp_parse_args(
				$section, array(
					'title'       => false,
					'description' => false,
					'id'          => '',
					'class'       => false,
					'style'       => '',
					'tooltip'     => false,
					'tooltip_class' => ''
				)
			)
		);

		$section_fields = $this->prepare_settings_fields( $section['fields'] );

		$classes = array( 'gaddon-section' );

		if ( $is_first ) {
			$classes[] = 'gaddon-first-section';
		}

		if ( $class )
			$classes[] = $class;

		?>

		<div
			id="<?php echo $id; ?>"
			class="<?php echo implode( ' ', $classes ); ?>"
			style="<?php echo $style; ?>"
			>

			<?php if ( $title ): ?>
				<h4 class="gaddon-section-title gf_settings_subgroup_title">
					<?php echo $title; ?>
					<?php if( $tooltip ): ?>
						<?php gform_tooltip( $tooltip, $tooltip_class ); ?>
					<?php endif; ?>
				</h4>
			<?php endif; ?>

			<?php if ( $description ): ?>
				<div class="gaddon-section-description"><?php echo $description; ?></div>
			<?php endif; ?>

			<table class="form-table gforms_form_settings">

				<?php
				foreach ( $section_fields as $field ) {

					if ( ! $this->setting_dependency_met( rgar( $field, 'dependency' ) ) )
						continue;

					if ( is_callable( array( $this, "single_setting_row_{$field['type']}" ) ) ) {
						call_user_func( array( $this, "single_setting_row_{$field['type']}" ), $field );
					} else {
						$this->single_setting_row( $field );
					}
				}
				?>

			</table>

		</div>

	<?php
	}

	/***
	 * Displays the UI for the field container row
	 *
	 * @param array $field - The field to be displayed
	 */
	public function single_setting_row( $field ) {

		if ( $this->get_settings_renderer() ) {

			// Initialize field.
			$field = \Gravity_Forms\Gravity_Forms\Settings\Fields::create( $field, $this->get_settings_renderer() );

			if ( is_wp_error( $field ) ) {
				esc_html_e( 'Field could not be rendered.', 'gravityforms' );
				return;
			}

			// Render field.
			$this->get_settings_renderer()->render_field( $field );
			return;

		}

		$display = rgar( $field, 'hidden' ) || rgar( $field, 'type' ) == 'hidden' ? 'style="display:none;"' : '';

		// Prepare setting description.
		$description = rgar( $field, 'description' ) ? '<span class="gf_settings_description">' . $field['description'] . '</span>' : null;

		?>

		<tr id="gaddon-setting-row-<?php echo $field['name'] ?>" <?php echo $display; ?>>
			<th>
				<?php $this->single_setting_label( $field ); ?>
			</th>
			<td>
				<?php
					$this->single_setting( $field );
					echo $description;
				?>
			</td>
		</tr>

	<?php
	}

	/**
	 * Displays the label for a field, including the tooltip and requirement indicator.
	 */
	public function single_setting_label( $field ) {

		echo rgar( $field, 'label' );

		if ( isset( $field['tooltip'] ) ) {
			echo $this->maybe_get_tooltip( $field );
		}

		if ( rgar( $field, 'required' ) ) {
			echo ' ' . $this->get_required_indicator( $field );
		}

	}

	public function single_setting_row_save( $field ) {
		?>

		<tr>
			<td colspan="2">
				<?php $this->single_setting( $field ); ?>
			</td>
		</tr>

	<?php
	}

	/***
	 * Calls the appropriate field function to handle rendering of each specific field type
	 *
	 * @param array $field - The field to be rendered
	 */
	public function single_setting( $field ) {

		if ( $this->get_settings_renderer() ) {

			// Initialize field.
			$field = \Gravity_Forms\Gravity_Forms\Settings\Fields::create( $field, $this->get_settings_renderer() );

			if ( is_wp_error( $field ) ) {
				esc_html_e( 'Field could not be rendered.', 'gravityforms' );
				return;
			}

			// Render field.
			echo $field->prepare_markup();
			return;

		}

		if ( is_callable( rgar( $field, 'callback' ) ) ) {
			call_user_func( $field['callback'], $field );
		} elseif ( is_callable( array( $this, "settings_{$field['type']}" ) ) ) {
			call_user_func( array( $this, "settings_{$field['type']}" ), $field );
		} else {
			printf( esc_html__( "Field type '%s' has not been implemented", 'gravityforms' ), esc_html( $field['type'] ) );
		}
	}

	/***
	 * Sets the current saved settings to a class variable so that it can be accessed by lower level functions in order to initialize inputs with the appropriate values
	 *
	 * @param array $settings : Settings to be saved
	 */
	public function set_settings( $settings ) {
		$this->_saved_settings = $settings;
	}

	/***
	 * Sets the previous settings to a class variable so that it can be accessed by lower level functions providing support for
	 * verifying whether a value was changed before executing an action
	 *
	 * @param array $settings : Settings to be stored
	 */
	public function set_previous_settings( $settings ) {
		$this->_previous_settings = $settings;
	}

	public function get_previous_settings() {
		if ( $this->get_settings_renderer() ) {
			return $this->get_settings_renderer()->get_previous_values();
		}
		return $this->_previous_settings;
	}


	/***
	 * Gets settings from $_POST variable, returning a name/value collection of setting name and setting value
	 */
	public function get_posted_settings() {

		if ( $this->get_settings_renderer() ) {
			return $this->get_settings_renderer()->get_posted_values();
		}

		global $_gaddon_posted_settings;

		if ( isset( $_gaddon_posted_settings ) ) {
			return $_gaddon_posted_settings;
		}

		$_gaddon_posted_settings = array();
		if ( count( $_POST ) > 0 ) {
			foreach ( $_POST as $key => $value ) {
				if ( preg_match( '|_gaddon_setting_(.*)|', $key, $matches ) ) {
					$_gaddon_posted_settings[ $matches[1] ] = self::maybe_decode_json( stripslashes_deep( $value ) );
				}
			}
		}

		return $_gaddon_posted_settings;
	}

	public static function maybe_decode_json( $value ) {
		return GFCommon::maybe_decode_json( $value );
	}

	public static function is_json( $value ) {
		return GFCommon::is_json( $value );
	}

	/***
	 * Gets the "current" settings, which are settings from $_POST variables if this is a postback request, or the current saved settings for a get request.
	 */
	public function get_current_settings() {

		// Get renderer.
		$renderer = $this->get_settings_renderer();

		// If renderer is initialized, get value from it.
		if ( $renderer ) {
			return $renderer->get_current_values();
		}

		return array();

	}

	/***
	 * Retrieves the setting for a specific field/input
	 *
	 * @param string $setting_name  The field or input name
	 * @param string $default_value Optional. The default value
	 * @param bool|array $settings Optional. THe settings array
	 *
	 * @return string|array
	 */
	public function get_setting( $setting_name, $default_value = '', $settings = false ) {

		// Get renderer.
		$renderer = $this->get_settings_renderer();

		// If renderer is initialized, get value from it.
		if ( $renderer ) {
			return $renderer->get_value( $setting_name, $default_value, $settings );
		}

		if ( ! $settings ) {
			$settings = $this->get_current_settings();
		}

		if ( false === $settings ) {
			return $default_value;
		}

		if ( strpos( $setting_name, '[' ) !== false ) {
			$path_parts = explode( '[', $setting_name );
			foreach ( $path_parts as $part ) {
				$part = trim( $part, ']' );
				if ( $part != '0'){
					if ( empty( $part ) ) {
						return $settings;
					}
				}
				if ( false === isset( $settings[ $part ] ) ) {
					return $default_value;
				}

				$settings = rgar( $settings, $part );
			}
			$setting = $settings;
		} else {
			if ( false === isset( $settings[ $setting_name ] ) ) {
				return $default_value;
			}
			$setting = $settings[ $setting_name ];
		}

		return $setting;

	}

	/***
	 * Determines if a dependent field has been populated.
	 *
	 * @param string $dependency - Field or input name of the "parent" field.
	 *
	 * @return bool - true if the "parent" field has been filled out and false if it has not.
	 *
	 */
	public function setting_dependency_met( $dependency ) {

		// if no dependency, always return true
		if ( ! $dependency ) {
			return true;
		}

		//use a callback if one is specified in the configuration
		if ( is_callable( $dependency ) ) {
			return call_user_func( $dependency );
		}

		if ( is_array( $dependency ) ) {
			//supports: 'dependency' => array("field" => 'myfield', 'values' => array("val1", 'val2'))
			$dependency_field = $dependency['field'];
			$dependency_value = $dependency['values'];
		} else {
			//supports: 'dependency' => 'myfield'
			$dependency_field = $dependency;
			$dependency_value = '_notempty_';
		}

		if ( ! is_array( $dependency_value ) ) {
			$dependency_value = array( $dependency_value );
		}

		$current_value = $this->get_setting( $dependency_field );

		foreach ( $dependency_value as $val ) {
			if ( $current_value == $val ) {
				return true;
			}

			if ( $val == '_notempty_' && ! rgblank( $current_value ) ) {
				return true;
			}
		}

		return false;
	}

	public function has_setting_field_type( $type, $fields ) {
		if ( ! empty( $fields ) ) {
			foreach ( $fields as &$section ) {
				foreach ( $section['fields'] as $field ) {
					if ( rgar( $field, 'type' ) == $type ) {
						return true;
					}
				}
			}
		}
		return false;
	}

	public function add_default_save_button( $sections ) {
		$sections[ count( $sections ) - 1 ]['fields'][] = array( 'type' => 'save' );

		return $sections;
	}

	public function get_save_success_message( $sections ) {
		$save_button = $this->get_save_button( $sections );

		return isset( $save_button['messages']['success'] ) ? $save_button['messages']['success'] : sprintf( esc_html__( '%s settings updated.', 'gravityforms' ), $this->get_short_title() );
	}

	public function get_save_error_message( $sections ) {
		$save_button = $this->get_save_button( $sections );

		return isset( $save_button['messages']['error'] ) ? $save_button['messages']['error'] : esc_html__( 'There was an error while saving your settings.', 'gravityforms' );
	}

	public function get_save_button( $sections ) {
		$sections = array_values( $sections );
		$fields   = $sections[ count( $sections ) - 1 ]['fields'];

		foreach ( $fields as $field ) {
			if ( $field['type'] == 'save' )
				return $field;
		}

		return false;
	}

	/**
	 * Sets the current instance of object that handles settings encryption.
	 *
	 * @since 2.7.17
	 *
	 * @param \Gravity_Forms\Gravity_Forms\Settings\GF_Settings_Encryption $encryptor Settings encryptor.
	 *
	 * @return void
	 */
	public function set_encryptor( $encryptor ) {
		$this->_encryptor = $encryptor;
	}


	/**
	 * Returns the current instance of the settings encryptor.
	 *
	 * @since 2.7.17
	 *
	 * @return GF_Settings_Encryption Returns the current instance of the settings encryptor.
	 */
	public function get_encryptor() {
		if ( ! $this->_encryptor ) {
			require_once( GFCommon::get_base_path() . '/includes/settings/class-gf-settings-encryption.php' );
			$this->_encryptor = new GF_Settings_Encryption();
		}
		return $this->_encryptor;
	}

	//------------- Field Types ------------------------------------------------------

	/***
	 * Renders and initializes a text field based on the $field array
	 *
	 * @param array $field - Field array containing the configuration options of this field
	 * @param bool  $echo  = true - true to echo the output to the screen, false to simply return the contents as a string
	 *
	 * @return string The HTML for the field
	 */
	public function settings_text( $field, $echo = true ) {

		// If Settings Renderer is not initialized, return.
		if ( ! $this->get_settings_renderer() ) {
			return null;
		}

		// Force field type.
		$field['type'] = 'text';

		// Initialize a new field.
		$field = \Gravity_Forms\Gravity_Forms\Settings\Fields::create(
			$field,
			$this->get_settings_renderer()
		);

		// Get markup.
		$html = $field->prepare_markup();

		if ( $echo ) {
			echo $html;
		}

		return $html;

	}

	/***
	 * Renders and initializes a textarea field based on the $field array
	 *
	 * @param array $field - Field array containing the configuration options of this field
	 * @param bool  $echo  = true - true to echo the output to the screen, false to simply return the contents as a string
	 *
	 * @return string The HTML for the field
	 */
	public function settings_textarea( $field, $echo = true ) {

		// If Settings Renderer is not initialized, return.
		if ( ! $this->get_settings_renderer() ) {
			return null;
		}

		// Force field type.
		$field['type'] = 'textarea';

		// Initialize a new field.
		$field = \Gravity_Forms\Gravity_Forms\Settings\Fields::create(
			$field,
			$this->get_settings_renderer()
		);

		// Get markup.
		$html = $field->prepare_markup();

		if ( $echo ) {
			echo $html;
		}

		return $html;

	}


	/***
	 * Renders and initializes a hidden field based on the $field array
	 *
	 * @param array $field - Field array containing the configuration options of this field
	 * @param bool  $echo  = true - true to echo the output to the screen, false to simply return the contents as a string
	 *
	 * @return string The HTML for the field
	 */
	public function settings_hidden( $field, $echo = true ) {

		// If Settings Renderer is not initialized, return.
		if ( ! $this->get_settings_renderer() ) {
			return null;
		}

		// Force field type.
		$field['type'] = 'hidden';

		// Initialize a new field.
		$field = \Gravity_Forms\Gravity_Forms\Settings\Fields::create(
			$field,
			$this->get_settings_renderer()
		);

		// Get markup.
		$html = $field->prepare_markup();

		if ( $echo ) {
			echo $html;
		}

		return $html;

	}

	/***
	 * Renders and initializes a checkbox field or a collection of checkbox fields based on the $field array
	 *
	 * @param array $field - Field array containing the configuration options of this field
	 * @param bool  $echo  = true - true to echo the output to the screen, false to simply return the contents as a string
	 *
	 * @return string The HTML for the field
	 */
	public function settings_checkbox( $field, $echo = true ) {

		// If Settings Renderer is not initialized, return.
		if ( ! $this->get_settings_renderer() ) {
			return null;
		}

		// Force field type.
		$field['type'] = 'checkbox';

		// Initialize a new field.
		$field = \Gravity_Forms\Gravity_Forms\Settings\Fields::create(
			$field,
			$this->get_settings_renderer()
		);

		// Get markup.
		$html = $field->prepare_markup();

		if ( $echo ) {
			echo $html;
		}

		return $html;

	}


	/**
	 * Returns the markup for an individual checkbox item give the parameters
	 *
	 * @param $choice           - Choice array with all configured properties
	 * @param $horizontal_class - CSS class to style checkbox items horizontally
	 * @param $attributes       - String containing all the attributes for the input tag.
	 * @param $value            - Currently selection (1 if field has been checked. 0 or null otherwise)
	 * @param $tooltip          - String containing a tooltip for this checkbox item.
	 *
	 * @return string - The markup of an individual checkbox item
	 */
	public function checkbox_item( $choice, $horizontal_class, $attributes, $value, $tooltip, $error_icon = '' ) {

		$hidden_field_value = $value == '1' ? '1' : '0';
		$icon_class         = rgar( $choice, 'icon' ) ? ' gaddon-setting-choice-visual' : '';

		$checkbox_item  = '<div id="gaddon-setting-checkbox-choice-' . $choice['id'] . '" class="gaddon-setting-checkbox' . $horizontal_class . $icon_class . '">';
		$checkbox_item .= '<input type=hidden name="_gaddon_setting_' . esc_attr( $choice['name'] ) . '" value="' . $hidden_field_value . '" />';

		if ( is_callable( array( $this, "checkbox_input_{$choice['name']}" ) ) ) {
			$markup = call_user_func( array( $this, "checkbox_input_{$choice['name']}" ), $choice, $attributes, $value, $tooltip );
		} else {
			$markup = $this->checkbox_input( $choice, $attributes, $value, $tooltip );
		}

		$checkbox_item .= $markup . $error_icon . '</div>';

		return $checkbox_item;
	}

	/**
	 * Returns the markup for an individual checkbox input and its associated label
	 *
	 * @param $choice     - Choice array with all configured properties
	 * @param $attributes - String containing all the attributes for the input tag.
	 * @param $value      - Currently selection (1 if field has been checked. 0 or null otherwise)
	 * @param $tooltip    - String containing a tooltip for this checkbox item.
	 *
	 * @return string - The markup of an individual checkbox input and its associated label
	 */
	public function checkbox_input( $choice, $attributes, $value, $tooltip ) {
		return \Gravity_Forms\Gravity_Forms\Settings\Fields\Checkbox::render_input( $choice, $attributes, $value, $tooltip );
	}


	/***
	 * Renders and initializes a radio field or a collection of radio fields based on the $field array
	 *
	 * @param array $field - Field array containing the configuration options of this field
	 * @param bool  $echo  = true - true to echo the output to the screen, false to simply return the contents as a string
	 *
	 * @return string Returns the markup for the radio buttons
	 *
	 */
	public function settings_radio( $field, $echo = true ) {

		// If Settings Renderer is not initialized, return.
		if ( ! $this->get_settings_renderer() ) {
			return null;
		}

		// Force field type.
		$field['type'] = 'radio';

		// Initialize a new field.
		$field = \Gravity_Forms\Gravity_Forms\Settings\Fields::create(
			$field,
			$this->get_settings_renderer()
		);

		// Get markup.
		$html = $field->prepare_markup();

		if ( $echo ) {
			echo $html;
		}

		return $html;

	}

	/**
	 * Determines if any of the available settings choices have an icon.
	 *
	 * @access public
	 * @param array $choices (default: array())
	 * @return bool
	 */
	public function choices_have_icon( $choices = array() ) {

		return \Gravity_Forms\Gravity_Forms\Settings\Fields\Base::has_icons( $choices );

	}

	/***
	 * Renders and initializes a drop down field based on the $field array
	 *
	 * @param array $field - Field array containing the configuration options of this field
	 * @param bool  $echo  = true - true to echo the output to the screen, false to simply return the contents as a string
	 *
	 * @return string The HTML for the field
	 */
	public function settings_select( $field, $echo = true ) {

		// If Settings Renderer is not initialized, return.
		if ( ! $this->get_settings_renderer() ) {
			return null;
		}

		// Force field type.
		$field['type'] = 'select';

		// Initialize a new field.
		$field = \Gravity_Forms\Gravity_Forms\Settings\Fields::create(
			$field,
			$this->get_settings_renderer()
		);

		// Get markup.
		$html = $field->prepare_markup();

		if ( $echo ) {
			echo $html;
		}

		return $html;

	}

	/**
	 * Renders and initializes a drop down field with a input field for custom input based on the $field array.
	 *
	 * @param array $field - Field array containing the configuration options of this field
	 * @param bool  $echo  = true - true to echo the output to the screen, false to simply return the contents as a string
	 *
	 * @return string The HTML for the field
	 */
	public function settings_select_custom( $field, $echo = true ) {

		// If Settings Renderer is not initialized, return.
		if ( ! $this->get_settings_renderer() ) {
			return null;
		}

		// Force field type.
		$field['type'] = 'select_custom';

		// Initialize a new field.
		$field = \Gravity_Forms\Gravity_Forms\Settings\Fields::create(
			$field,
			$this->get_settings_renderer()
		);

		// Get markup.
		$html = $field->prepare_markup();

		if ( $echo ) {
			echo $html;
		}

		return $html;

	}

	/**
	 * Prepares an HTML string of options for a drop down field.
	 *
	 * @param array  $choices - Array containing all the options for the drop down field
	 * @param string $selected_value - The value currently selected for the field
	 *
	 * @return string The HTML for the select options
	 */
	public function get_select_options( $choices, $selected_value ) {
		return \Gravity_Forms\Gravity_Forms\Settings\Fields\Select::get_options( $choices, $selected_value );
	}

	/**
	 * Prepares an HTML string for a single drop down field option.
	 *
	 * @access protected
	 * @param array  $choice - Array containing the settings for the drop down option
	 * @param string $selected_value - The value currently selected for the field
	 *
	 * @return string The HTML for the select choice
	 */
	public function get_select_option( $choice, $selected_value ) {
		if ( is_array( $selected_value ) ) {
			$selected = in_array( $choice['value'], $selected_value ) ? "selected='selected'" : '';
		} else {
			$selected = selected( $selected_value, $choice['value'], false );
		}

		return sprintf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $choice['value'] ), $selected, $choice['label'] );
	}





	//------------- Field Map Field Type --------------------------

	/**
	 * Renders and initializes a generic map field based on the $field array whose choices are populated by the fields to be mapped.
	 *
	 * @since  2.2
	 * @access public
	 *
	 * @uses GFAddOn::field_failed_validation()
	 * @uses GFCommon::get_base_url()
	 * @uses GFAddOn::get_current_forn()
	 * @uses GFAddOn::get_error_icon()
	 * @uses GFAddOn::get_mapping_field()
	 * @uses GFAddOn::settings_hidden()
	 *
	 * @param array $field Field array containing the configuration options of this field.
	 * @param bool  $echo  Determines if field contents should automatically be displayed. Defaults to true.
	 *
	 * @return string The HTML for the field
	 */
	public function settings_generic_map( $field, $echo = true ) {

		// If Settings Renderer is not initialized, return.
		if ( ! $this->get_settings_renderer() ) {
			return null;
		}

		// Force field type.
		$field['type'] = 'generic_map';

		// Initialize a new field.
		$field = \Gravity_Forms\Gravity_Forms\Settings\Fields::create(
			$field,
			$this->get_settings_renderer()
		);

		// Get markup.
		$html = $field->prepare_markup();

		if ( $echo ) {
			echo $html;
		}

		return $html;

	}

	/**
	 * Renders and initializes a field map field based on the $field array whose choices are populated by the fields to be mapped.
	 *
	 * @since  Unknown
	 *
	 * @param array $field Field array containing the configuration options of this field.
	 * @param bool  $echo  Determines if field contents should automatically be displayed. Defaults to true.
	 *
	 * @return string The HTML for the field
	 */
	public function settings_field_map( $field, $echo = true ) {

		// If Settings Renderer is not initialized, return.
		if ( ! $this->get_settings_renderer() ) {
			return null;
		}

		// Force field type.
		$field['type'] = 'field_map';

		// Initialize a new field.
		$field = \Gravity_Forms\Gravity_Forms\Settings\Fields::create(
			$field,
			$this->get_settings_renderer()
		);

		// Get markup.
		$html = $field->prepare_markup();

		if ( $echo ) {
			echo $html;
		}

		return $html;

	}

	/**
	 * Renders and initializes a dynamic field map field based on the $field array whose choices are populated by the fields to be mapped.
	 *
	 * @since  1.9.5.13
	 *
	 * @param array $field Field array containing the configuration options of this field.
	 * @param bool  $echo  Determines if field contents should automatically be displayed. Defaults to true.
	 *
	 * @return string The HTML for the field
	 */
	public function settings_dynamic_field_map( $field, $echo = true ) {

		// If Settings Renderer is not initialized, return.
		if ( ! $this->get_settings_renderer() ) {
			return null;
		}

		// Force field type.
		$field['type'] = 'dynamic_field_map';

		// Initialize a new field.
		$field = \Gravity_Forms\Gravity_Forms\Settings\Fields::create(
			$field,
			$this->get_settings_renderer()
		);

		// Get markup.
		$html = $field->prepare_markup();

		if ( $echo ) {
			echo $html;
		}

		return $html;

	}

	/**
	 * Renders a field select field for field maps.
	 *
	 * @since  unknown
	 * @access public
	 *
	 * @uses GFAddOn::get_field_map_choices()
	 * @uses GF_Field::get_form_editor_field_title()
	 *
	 * @param array $field    Field array containing the configuration options of this field.
	 * @param int   $form_id  Form ID to retrieve fields from.
	 *
	 * @return string The HTML for the field
	 */
	public function settings_field_map_select( $field, $form_id ) {

		// Get field types to only display.
		$field_type = rgempty( 'field_type', $field ) ? null : $field['field_type'];

		// Get field types to exclude.
		$exclude_field_types = rgempty( 'exclude_field_types', $field ) ? null : $field['exclude_field_types'];

		// Get form field choices based on field type inclusions/exclusions.
		$field['choices'] = $this->get_field_map_choices( $form_id, $field_type, $exclude_field_types );

		// If no choices were found, return error.
		if ( empty( $field['choices'] ) || ( count( $field['choices'] ) == 1 && rgblank( $field['choices'][0]['value'] ) ) ) {

			if ( ( ! is_array( $field_type ) && ! rgblank( $field_type ) ) || ( is_array( $field_type ) && count( $field_type ) == 1 ) ) {

				$type = is_array( $field_type ) ? $field_type[0] : $field_type;
				$type = ucfirst( GF_Fields::get( $type )->get_form_editor_field_title() );

				return sprintf( __( 'Please add a %s field to your form.', 'gravityforms' ), $type );

			}

		}

		// Set default value.
		$field['default_value'] = $this->get_default_field_select_field( $field );

		return $this->settings_select( $field, false );

	}

	/**
	 * Prepares the markup for mapping field key and value fields.
	 *
	 * @since  2.2
	 * @access public
	 *
	 * @uses GFAddOn::get_current_form()
	 * @uses GFAddOn::get_field_map_choices()
	 *
	 * @param string $type The field type being prepared; key or value.
	 * @param array  $select_field The drop down field properties.
	 * @param array  $text_field   The text field properties.
	 *
	 * @return string
	 */
	public function get_mapping_field( $type, $select_field, $text_field ) {

		// If use form fields as choices flag is set, add as choices.
		if ( isset( $select_field['choices'] ) && ! is_array( $select_field['choices'] ) && 'form_fields' === strtolower( $select_field['choices'] ) ) {

			// Set choices to form fields.
			$select_field['choices'] = $this->get_field_map_choices( rgget( 'id' ) );

		}

		// If field has no choices, display custom field only.
		if ( empty( $select_field['choices'] ) ) {

			// Set field value to custom key.
			$select_field['value'] = 'gf_custom';

			// Display field row.
			return sprintf(
				'<td>%s<div class="custom-%s-container">%s</div></td>',
				$this->settings_hidden( $select_field, false ),
				$type,
				$this->settings_text( $text_field, false )
			);

		} else {

			// Set initial additional classes.
			$additional_classes = array();

			// Set has custom key flag.
			$has_gf_custom = false;

			// Loop through key field choices.
			foreach ( $select_field['choices'] as $choice ) {

				// If choice name or value is the custom key, set custom key flag to true and exit loop.
				if ( rgar( $choice, 'name' ) == 'gf_custom' || rgar( $choice, 'value' ) == 'gf_custom' ) {
					$has_gf_custom = true;
					break;
				}

				// If choice has sub-choices, check for custom key option.
				if ( rgar( $choice, 'choices' ) ) {

					// Loop through sub-choices.
					foreach ( $choice['choices'] as $subchoice ) {

						// If sub-choice name or value is the custom key, set custom key flag to true and exit loop.
						if ( rgar( $subchoice, 'name' ) == 'gf_custom' || rgar( $subchoice, 'value' ) == 'gf_custom' ) {
							$has_gf_custom = true;
							break;
						}
					}

				}

			}

			// If custom key option is not found and we're allowed to add it, add it.
			if ( ! $has_gf_custom ) {

				if ( $type == 'key' ) {

					$enable_custom = rgars( $select_field, 'key_field/custom_value' ) ? (bool) $select_field['key_field']['custom_value'] : ! (bool) rgar( $select_field, 'disable_custom' );
					$enable_custom = isset( $select_field['enable_custom_key'] ) ? (bool) $select_field['enable_custom_key'] : $enable_custom;
					$label         = esc_html__( 'Add Custom Key', 'gravityforms' );

				} else {

					// Add merge tag class.
					if ( rgars( $select_field, 'value_field/merge_tags' ) ) {
						$additional_classes[] = 'supports-merge-tags';
					}

					$enable_custom = rgars( $select_field, 'value_field/custom_value' ) ? (bool) $select_field['value_field']['custom_value'] : (bool) rgars( $select_field, 'enable_custom_value' );
					$label         = esc_html__( 'Add Custom Value', 'gravityforms' );

				}

				if ( $enable_custom ) {
					$select_field['choices'][] = array(
						'label' => $label,
						'value' => 'gf_custom'
					);
				}

			}

			// Display field row.
			return sprintf(
				'<th>%s<div class="custom-%s-container %s">%s<a href="#" class="custom-%s-reset">%s</a></div></th>',
				$this->settings_select( $select_field, false ),
				$type,
				implode( ' ', $additional_classes ),
				$this->settings_text( $text_field, false ),
				$type,
				esc_html__( 'Reset', 'gravityforms' )
			);

		}

	}

	/**
	 * Heading row for field map table.
	 *
	 * @since  2.2
	 * @access public
	 *
	 * @uses GFAddOn::field_map_title()
	 *
	 * @return string
	 */
	public function field_map_table_header() {

		return '<thead>
					<tr>
						<th>' . $this->field_map_title() . '</th>
						<th>' . esc_html__( 'Form Field', 'gravityforms' ) . '</th>
					</tr>
				</thead>';

	}

	/**
	 * Heading for field map field column.
	 *
	 * @since  2.2
	 * @access public
	 *
	 * @used-by GFAddOn::field_map_table_header()
	 *
	 * @return string
	 */
	public function field_map_title() {

		return esc_html__( 'Field', 'gravityforms' );

	}

	/**
	 * Get field map choices for specific form.
	 *
	 * @since  unknown
	 * @access public
	 *
	 * @uses GFCommon::get_label()
	 * @uses GFFormsModel::get_entry_meta()
	 * @uses GFFormsModel::get_form_meta()
	 * @uses GF_Field::get_entry_inputs()
	 * @uses GF_Field::get_form_editor_field_title()
	 * @uses GF_Field::get_input_type()
	 *
	 * @param int          $form_id             Form ID to display fields for.
	 * @param array|string $field_type          Field types to only include as choices. Defaults to null.
	 * @param array|string $exclude_field_types Field types to exclude from choices. Defaults to null.
	 *
	 * @return array
	 */
	public static function get_field_map_choices( $form_id, $field_type = null, $exclude_field_types = null ) {

		$form = GFFormsModel::get_form_meta( $form_id );

		$fields = array();

		// Setup first choice
		if ( rgblank( $field_type ) || ( is_array( $field_type ) && count( $field_type ) > 1 ) ) {

			$first_choice_label = __( 'Select a Field', 'gravityforms' );

		} else {

			$type = is_array( $field_type ) ? $field_type[0] : $field_type;
			$type = ucfirst( GF_Fields::get( $type )->get_form_editor_field_title() );

			$first_choice_label = sprintf( __( 'Select a %s Field', 'gravityforms' ), $type );

		}

		$fields[] = array( 'value' => '', 'label' => $first_choice_label );

		// if field types not restricted add the default fields and entry meta
		if ( is_null( $field_type ) ) {
			$fields[] = array( 'value' => 'id', 'label' => esc_html__( 'Entry ID', 'gravityforms' ) );
			$fields[] = array( 'value' => 'date_created', 'label' => esc_html__( 'Entry Date', 'gravityforms' ) );
			$fields[] = array( 'value' => 'ip', 'label' => esc_html__( 'User IP', 'gravityforms' ) );
			$fields[] = array( 'value' => 'source_url', 'label' => esc_html__( 'Source Url', 'gravityforms' ) );
			$fields[] = array( 'value' => 'form_title', 'label' => esc_html__( 'Form Title', 'gravityforms' ) );

			$entry_meta = GFFormsModel::get_entry_meta( $form['id'] );
			foreach ( $entry_meta as $meta_key => $meta ) {
				$fields[] = array( 'value' => $meta_key, 'label' => rgars( $entry_meta, "{$meta_key}/label" ) );
			}
		}

		// Populate form fields
		if ( is_array( $form['fields'] ) ) {
			foreach ( $form['fields'] as $field ) {
				$input_type = $field->get_input_type();
				$inputs     = $field->get_entry_inputs();
				$field_is_valid_type = ( empty( $field_type ) || ( is_array( $field_type ) && in_array( $input_type, $field_type ) ) || ( ! empty( $field_type ) && $input_type == $field_type ) );

				if ( is_null( $exclude_field_types ) ) {
					$exclude_field = false;
				} elseif ( is_array( $exclude_field_types ) ) {
					if ( in_array( $input_type, $exclude_field_types ) ) {
						$exclude_field = true;
					} else {
						$exclude_field = false;
					}
				} else {
					//not array, so should be single string
					if ( $input_type == $exclude_field_types ) {
						$exclude_field = true;
					} else {
						$exclude_field = false;
					}
				}

				if ( is_array( $inputs ) && $field_is_valid_type && ! $exclude_field ) {
					//If this is an address field, add full name to the list
					if ( $input_type == 'address' ) {
						$fields[] = array(
							'value' => $field->id,
							'label' => strip_tags( GFCommon::get_label( $field ) . ' (' . esc_html__( 'Full', 'gravityforms' ) . ')' )
						);
					}
					//If this is a name field, add full name to the list
					if ( $input_type == 'name' ) {
						$fields[] = array(
							'value' => $field->id,
							'label' => strip_tags( GFCommon::get_label( $field ) . ' (' . esc_html__( 'Full', 'gravityforms' ) . ')' )
						);
					}
					//If this is a checkbox field, add to the list
					if ( $input_type == 'checkbox' ) {
						$fields[] = array(
							'value' => $field->id,
							'label' => strip_tags( GFCommon::get_label( $field ) . ' (' . esc_html__( 'Selected', 'gravityforms' ) . ')' )
						);
					}

					foreach ( $inputs as $input ) {
						$fields[] = array(
							'value' => $input['id'],
							'label' => strip_tags( GFCommon::get_label( $field, $input['id'] ) )
						);
					}
				} elseif ( $input_type == 'list' && $field->enableColumns && $field_is_valid_type && ! $exclude_field ) {
					$fields[] = array(
						'value' => $field->id,
						'label' => strip_tags( GFCommon::get_label( $field ) . ' (' . esc_html__( 'Full', 'gravityforms' ) . ')' )
					);
					$col_index = 0;
					foreach ( $field->choices as $column ) {
						$fields[] = array(
							'value' => $field->id . '.' . $col_index,
							'label' => strip_tags( GFCommon::get_label( $field ) . ' (' . esc_html( rgar( $column, 'text' ) ) . ')' ),
						);
						$col_index ++;
					}
				} elseif ( ! $field->displayOnly && $field_is_valid_type && ! $exclude_field ) {
					$fields[] = array( 'value' => $field->id, 'label' => strip_tags( GFCommon::get_label( $field ) ) );
				}
			}
		}

		/**
		 * Filter the choices available in the field map drop down.
		 *
		 * @since 2.0.7.11
		 *
		 * @param array             $fields              The value and label properties for each choice.
		 * @param int               $form_id             The ID of the form currently being configured.
		 * @param null|array        $field_type          Null or the field types to be included in the drop down.
		 * @param null|array|string $exclude_field_types Null or the field type(s) to be excluded from the drop down.
		 */
		$fields = apply_filters( 'gform_addon_field_map_choices', $fields, $form_id, $field_type, $exclude_field_types );

		if ( function_exists( 'get_called_class' ) ) {
			$callable = array( get_called_class(), 'get_instance' );
			if ( is_callable( $callable ) ) {
				$add_on = call_user_func( $callable );
				$slug   = $add_on->get_slug();

				$fields = apply_filters( "gform_{$slug}_field_map_choices", $fields, $form_id, $field_type, $exclude_field_types );
			}
		}

		return $fields;
	}

	/**
	 * Get input name for field map field.
	 *
	 * @since  unknown
	 * @access public
	 *
	 * @used-by GFAddOn::settings_field_map()
	 * @used-by GFAddOn::validate_field_map_settings()
	 *
	 * @param array  $parent_field Field map field.
	 * @param string $field_name   Child field.
	 *
	 * @return string
	 */
	public function get_mapped_field_name( $parent_field, $field_name ) {

		return "{$parent_field['name']}_{$field_name}";

	}

	/**
	 * Get mapped key/value pairs for standard field map.
	 *
	 * @since  unknown
	 * @access public
	 *
	 * @param array  $feed       Feed object.
	 * @param string $field_name Field map field name.
	 *
	 * @return array
	 */
	public static function get_field_map_fields( $feed, $field_name ) {

		// Initialize return fields array.
		$fields = array();

		// Get prefix for mapped field map keys.
		$prefix = "{$field_name}_";

		// Loop through feed meta.
		foreach ( $feed['meta'] as $name => $value ) {

			// If field name matches prefix, add value to return array.
			if ( strpos( $name, $prefix ) === 0 ) {
				$name            = str_replace( $prefix, '', $name );
				$fields[ $name ] = $value;
			}

		}

		return $fields;

	}

	/**
	 * Get mapped key/value pairs for dynamic field map.
	 *
	 * @since  1.9.9.9
	 * @access public
	 *
	 * @param array  $feed       Feed object.
	 * @param string $field_name Dynamic field map field name.
	 *
	 * @return array
	 */
	public static function get_dynamic_field_map_fields( $feed, $field_name ) {

		// Initialize return fields array.
		$fields = array();

		// Get dynamic field map field.
		$dynamic_fields = rgars( $feed, 'meta/' . $field_name );

		// If dynamic field map field is found, loop through mapped fields and add to array.
		if ( ! empty( $dynamic_fields ) ) {

			// Loop through mapped fields.
			foreach ( $dynamic_fields as $dynamic_field ) {

				// Get mapped key or replace with custom value.
				$field_key = 'gf_custom' === $dynamic_field['key'] ? $dynamic_field['custom_key'] : $dynamic_field['key'];

				// Add mapped field to return array.
				$fields[ $field_key ] = $dynamic_field['value'];

			}

		}

		return $fields;

	}

	/**
	 * Get mapped key/value pairs for generic map.
	 *
	 * @since  2.2
	 * @access public
	 *
	 * @param array  $feed       Feed object or settings array.
	 * @param string $field_name Generic map field name.
	 * @param array  $form       Form object. Defaults to empty array.
	 * @param array  $entry      Entry object. Defaults to empty array.
	 *
	 * @uses GFCommon::replace_variables()
	 *
	 * @return array
	 */
	public function get_generic_map_fields( $feed, $field_name, $form = array(), $entry = array() ) {

		// Initialize return fields array.
		$fields = array();

		// Get generic map field.
		$generic_fields = rgar( $feed, 'meta' ) ? rgars( $feed, 'meta/' . $field_name ) : rgar( $feed, $field_name );

		// If generic map field is found, loop through mapped fields and add to array.
		if ( ! empty( $generic_fields ) ) {

			// Loop through mapped fields.
			foreach ( $generic_fields as $generic_field ) {

				// Get mapped key or replace with custom value.
				$field_key = 'gf_custom' === $generic_field['key'] ? $generic_field['custom_key'] : $generic_field['key'];

				// Get mapped field choice or replace with custom value.
				if ( 'gf_custom' === $generic_field['value'] ) {

					// If form isn't set, use custom value. Otherwise, replace merge tags.
					$field_value = empty( $form ) ? $generic_field['custom_value'] : GFCommon::replace_variables( $generic_field['custom_value'], $form, $entry, false, false, false, 'text' );

				} else {

					// If form isn't set, use value. Otherwise, get field value.
					$field_value = empty( $form ) ? $generic_field['value'] : $this->get_field_value( $form, $entry, $generic_field['value'] );

				}

				// Add mapped field to return array.
				$fields[ $field_key ] = $field_value;

			}

		}

		return $fields;

	}





	//------------ Field Select Field Type ------------------------

	/**
	 * Renders and initializes a drop down field based on the $field array whose choices are populated by the form's fields.
	 *
	 * @param array $field - Field array containing the configuration options of this field
	 * @param bool  $echo  = true - true to echo the output to the screen, false to simply return the contents as a string
	 *
	 * @return string The HTML for the field
	 */
	public function settings_field_select( $field, $echo = true ) {

		// If Settings Renderer is not initialized, return.
		if ( ! $this->get_settings_renderer() ) {
			return null;
		}

		// Force field type.
		$field['type'] = 'field_select';

		// Initialize a new field.
		$field = \Gravity_Forms\Gravity_Forms\Settings\Fields::create(
			$field,
			$this->get_settings_renderer()
		);

		// Get markup.
		$html = $field->prepare_markup();

		if ( $echo ) {
			echo $html;
		}

		return $html;

	}

	/**
	 * Returns the field to be selected by default for field select fields based on matching labels.
	 *
	 * @access public
	 * @param  array $field - Field array containing the configuration options of this field
	 *
	 * @return string|null
	 */
	public function get_default_field_select_field( $field ) {

		if ( ! is_a( $field, 'Gravity_Forms\Gravity_Forms\Settings\Field\Field_Select' ) ) {
			$field['type'] = 'field_select';
			$field         = \Gravity_Forms\Gravity_Forms\Settings\Fields::create( $field, $this->get_settings_renderer() );
		}

		return is_wp_error( $field ) ? null : $field->get_default_choice();

	}

	/**
	 * Retrieve an array of form fields formatted for select, radio and checkbox settings fields.
	 *
	 * @access public
	 * @param array $form - The form object
	 * @param array $args - Additional settings to check for (field and input types to include, callback for applicable input type)
	 *
	 * @return array The array of formatted form fields
	 */
	public function get_form_fields_as_choices( $form, $args = array() ) {

		/**
		 * Initialize new Field Select field
		 *
		 * @var \Gravity_Forms\Gravity_Forms\Settings\Fields\Field_Select|WP_Error $field
		 */
		$field = \Gravity_Forms\Gravity_Forms\Settings\Fields::create(
			array(
				'type' => 'field_select',
				'args' => $args,
			),
			$this->get_settings_renderer()
		);

		return is_wp_error( $field ) ? array() : $field->get_form_fields_as_choices( $form );
	}

	/**
	 * Renders and initializes a checkbox field that displays a select field when checked based on the $field array.
	 *
	 * @access public
	 * @param array $field - Field array containing the configuration options of this field
	 * @param bool  $echo  = true - true to echo the output to the screen, false to simply return the contents as a string
	 *
	 * @return string The HTML for the field
	 */
	public function settings_checkbox_and_select( $field, $echo = true ) {

		// If Settings Renderer is not initialized, return.
		if ( ! $this->get_settings_renderer() ) {
			return null;
		}

		// Force field type.
		$field['type'] = 'checkbox_and_select';

		// Initialize a new field.
		$field = \Gravity_Forms\Gravity_Forms\Settings\Fields::create(
			$field,
			$this->get_settings_renderer()
		);

		// Get markup.
		$html = $field->prepare_markup();

		if ( $echo ) {
			echo $html;
		}

		return $html;

	}

	public function prepare_settings_checkbox_and_select( $field ) {
		return $field;
	}

	/***
	 * Renders the save button for settings pages
	 *
	 * @deprecated 2.5 Use \Gravity_Forms\Gravity_Forms\Settings\Fields\Button to add a Save button.
	 *
	 * @param array $field - Field array containing the configuration options of this field
	 * @param bool  $echo  = true - true to echo the output to the screen, false to simply return the contents as a string
	 *
	 * @return string The HTML
	 */
	public function settings_save( $field, $echo = true ) {

		_deprecated_function( __METHOD__, '2.5', 'the \Gravity_Forms\Gravity_Forms\Settings\Fields\Button class to add a save button to your form' );

		$field['type']  = 'submit';
		$field['name']  = 'gform-settings-save';
		$field['class'] = 'button-primary gfbutton';

		if ( ! rgar( $field, 'value' ) ) {
			$field['value'] = esc_html__( 'Update Settings', 'gravityforms' );
		}

		$attributes = $this->get_field_attributes( $field );

		$html = '<input
			type="' . esc_attr( $field['type'] ) . '"
			name="' . esc_attr( $field['name'] ) . '"
			value="' . esc_attr( $field['value'] ) . '" ' . implode( ' ', $attributes ) . ' />';

		if ( $echo ) {
			echo $html;
		}

		return $html;
	}

	/**
	 * Parses the properties of the $field meta array and returns a set of HTML attributes to be added to the HTML element.
	 *
	 * @param array $field   - current field meta to be parsed.
	 * @param array $default - default set of properties. Will be appended to the properties specified in the $field array
	 *
	 * @return array - resulting HTML attributes ready to be included in the HTML element.
	 */
	public function get_field_attributes( $field, $default = array() ) {

		if ( ! $field instanceof Gravity_Forms\Gravity_Forms\Settings\Fields\Base ) {

			// If Settings Renderer is not initialized, return.
			if ( ! $this->get_settings_renderer() ) {
				return array();
			}

			// Initialize a new field.
			$field = \Gravity_Forms\Gravity_Forms\Settings\Fields::create(
				$field,
				$this->get_settings_renderer()
			);

		}

		return is_wp_error( $field ) ? array() : $field->get_attributes( $default );

	}

	/**
	 * Parses the properties of the $choice meta array and returns a set of HTML attributes to be added to the HTML element.
	 *
	 * @param array $choice           - current choice meta to be parsed.
	 * @param array $field_attributes - current field's attributes.
	 *
	 * @return array - resulting HTML attributes ready to be included in the HTML element.
	 */
	public function get_choice_attributes( $choice, $field_attributes, $default_choice_attributes = array() ) {

		return \Gravity_Forms\Gravity_Forms\Settings\Fields\Base::get_choice_attributes( $choice, $field_attributes, $default_choice_attributes );

	}

	/***
	 * @param $name - The name of the attribute to be added
	 * @param $attribute - The attribute value to be added
	 * @param $current_attribute - The full string containing the current attribute value
	 * @return mixed - The new attribute string with the new value added to the beginning of the list
	 */
	public function prepend_attribute( $name, $attribute, $current_attribute ) {
		return str_replace( "{$name}='", "{$name}='{$attribute}", $current_attribute );
	}

	/**
	 * Validates settings fields.
	 * Validates that all fields are valid. Fields can be invalid when they are blank and marked as required or if it fails a custom validation check.
	 * To specify a custom validation, use the 'validation_callback' field meta property and implement the validation function with the custom logic.
	 *
	 * @param $fields   - A list of all fields from the field meta configuration
	 * @param $settings - A list of submitted settings values
	 *
	 * @return bool - Returns true if all fields have passed validation, and false otherwise.
	 */
	public function validate_settings( $fields, $settings ) {

		foreach ( $fields as $section ) {

			if ( ! $this->setting_dependency_met( rgar( $section, 'dependency' ) ) ) {
				continue;
			}

			foreach ( $section['fields'] as $field ) {

				if ( ! $this->setting_dependency_met( rgar( $field, 'dependency' ) ) ) {
					continue;
				}

				$field_setting = rgar( $settings, rgar( $field, 'name' ) );

				if ( is_callable( rgar( $field, 'validation_callback' ) ) ) {
					call_user_func( rgar( $field, 'validation_callback' ), $field, $field_setting );
					continue;
				}

				if ( is_callable( array( $this, 'validate_' . $field['type'] . '_settings' ) ) ) {
					call_user_func( array( $this, 'validate_' . $field['type'] . '_settings' ), $field, $settings );
					continue;
				}

				if ( rgar( $field, 'required' ) && rgblank( $field_setting ) ) {
					$this->set_field_error( $field, rgar( $field, 'error_message' ) );
				}
			}
		}

		$field_errors = $this->get_field_errors();
		$is_valid     = empty( $field_errors );

		return $is_valid;
	}

	/**
	 * Get a Settings Field object from a legacy Settings field array.
	 *
	 * @since 2.5
	 *
	 * @param array $field An array representing the legacy field item.
	 *
	 * @return \Gravity_Forms\Gravity_Forms\Settings\Fields\Base
	 */
	private function get_settings_field_object_from_legacy_field( $field ) {
		$renderer = $this->get_settings_renderer();

		foreach ( $renderer->get_fields() as $group ) {
			$nested_key = GFCommon::get_nested_key( $group );

			foreach ( rgar( $group, $nested_key, array() ) as $field_obj ) {
				if ( $field_obj->name === $field['name'] ) {
					return $field_obj;
				}
			}
		}

		return null;
	}

	/**
	 * Log an error indicating we could not find a matching Field Object for a legacy field.
	 *
	 * @param string $method The method name that called the error.
	 */
	private function log_matching_field_error( $method ) {
		$this->log_error( $method . '(): Failed to find a matching Field Object for Legacy Field array.' );
	}

	/**
	 * Perform legacy validation checks by calling the appropriate `validate()` methods for
	 * the field type. Exists to provide backwards-compatibility while the validate_*_settings
	 * methods move towards deprecation.
	 *
	 * @since 2.5
	 *
	 * @param array|object $field    The array or object representing the current field.
	 * @param array        $settings An array representing the currently-passed settings.
	 */
	private function perform_legacy_validation_check( $field, $settings ) {
		// Legacy field array - get the Field Object from our current fields list.
		if ( is_array( $field ) ) {
			$field_object = $this->get_settings_field_object_from_legacy_field( $field );
		} else {
			$field_object = $field;
		}

		// Could not get the correct field object, send error and bail.
		if ( ! is_a( $field_object, '\Gravity_Forms\Gravity_Forms\Settings\Fields\Base' ) ) {
			$this->log_matching_field_error( __METHOD__ );

			return;
		}

		$value = rgar( $settings, rgar( $field, 'name' ) );

		$field_object->handle_validation( $value );
	}

	/**
	 * @param array $field An array representing the field to validate.
	 * @param array $settings The current settings to validate against.
	 *
	 * @return void
	 */
	public function validate_text_settings( $field, $settings ) {

		_deprecated_function( __METHOD__, '2.5', '\Gravity_Forms\Gravity_Forms\Settings\Fields\Text::is_valid()' );

		$this->perform_legacy_validation_check( $field, $settings );

	}

	/**
	 * @param array $field An array representing the field to validate.
	 * @param array $settings The current settings to validate against.
	 *
	 * @return void
	 */
	public function validate_textarea_settings( $field, $settings ) {

		_deprecated_function( __METHOD__, '2.5', '\Gravity_Forms\Gravity_Forms\Settings\Fields\Textarea::is_valid()' );

		$this->perform_legacy_validation_check( $field, $settings );

	}

	/**
	 * @param array $field An array representing the field to validate.
	 * @param array $settings The current settings to validate against.
	 *
	 * @return void
	 */
	public function validate_radio_settings( $field, $settings ) {

		_deprecated_function( __METHOD__, '2.5', '\Gravity_Forms\Gravity_Forms\Settings\Fields\Radio::is_valid()' );

		$this->perform_legacy_validation_check( $field, $settings );

	}

	/**
	 * @param array $field An array representing the field to validate.
	 * @param array $settings The current settings to validate against.
	 *
	 * @return void
	 */
	public function validate_select_settings( $field, $settings ) {

		_deprecated_function( __METHOD__, '2.5', '\Gravity_Forms\Gravity_Forms\Settings\Fields\Select::is_valid()' );

		$this->perform_legacy_validation_check( $field, $settings );

	}

	/**
	 * @param array $field An array representing the field to validate.
	 * @param array $settings The current settings to validate against.
	 *
	 * @return void
	 */
	public function validate_checkbox_settings( $field, $settings ) {

		_deprecated_function( __METHOD__, '2.5', '\Gravity_Forms\Gravity_Forms\Settings\Fields\Checkbox::is_valid()' );

		$this->perform_legacy_validation_check( $field, $settings );

	}

	/**
	 * @param array $field An array representing the field to validate.
	 * @param array $settings The current settings to validate against.
	 *
	 * @return void
	 */
	public function validate_select_custom_settings( $field, $settings ) {

		_deprecated_function( __METHOD__, '2.5', '\Gravity_Forms\Gravity_Forms\Settings\Fields\Select_Custom::is_valid()' );

		$this->perform_legacy_validation_check( $field, $settings );

	}

	/**
	 * @param array $field An array representing the field to validate.
	 * @param array $settings The current settings to validate against.
	 *
	 * @return void
	 */
	public function validate_field_select_settings( $field, $settings ) {

		_deprecated_function( __METHOD__, '2.5', '\Gravity_Forms\Gravity_Forms\Settings\Fields\Field_Select::is_valid()' );

		$this->perform_legacy_validation_check( $field, $settings );

	}

	/**
	 * @param array $field An array representing the field to validate.
	 * @param array $settings The current settings to validate against.
	 *
	 * @return void
	 */
	public function validate_field_map_settings( $field, $settings ) {

		_deprecated_function( __METHOD__, '2.5', '\Gravity_Forms\Gravity_Forms\Settings\Fields\Generic_Map::is_valid()' );

		$this->perform_legacy_validation_check( $field, $settings );

	}

	/**
	 * @param array $field An array representing the field to validate.
	 * @param array $settings The current settings to validate against.
	 *
	 * @return void
	 */
	public function validate_checkbox_and_select_settings( $field, $settings ) {

		_deprecated_function( __METHOD__, '2.5', '\Gravity_Forms\Gravity_Forms\Settings\Fields\Checkbox_And_Select::is_valid()' );

		$this->perform_legacy_validation_check( $field, $settings );

	}

	/**
	 * Helper to determine if the current choice is a match for the submitted field value.
	 *
	 * @param array $choice The choice properties.
	 * @param string|array $value The submitted field value.
	 *
	 * @return bool
	 */
	public function is_choice_valid( $choice, $value ) {
		$choice_value = isset( $choice['value'] ) ? $choice['value'] : $choice['label'];

		return is_array( $value ) ? in_array( $choice_value, $value ) : $choice_value == $value;
	}

	/**
	 * Sets the validation error message
	 * Sets the error message to be displayed when a field fails validation.
	 * When implementing a custom validation callback function, use this function to specify the error message to be displayed.
	 *
	 * @since Unknown
	 *
	 * @param \Gravity_Forms\Gravity_Forms\Settings\Fields\Base|array $field         Field object.
	 * @param string                                                 $error_message Error message to be displayed.
	 */
	public function set_field_error( &$field, $error_message = '' ) {

		// set default error message if none passed
		if ( ! $error_message ) {
			$error_message = esc_html__( 'This field is required.', 'gravityforms' );
		}

		if ( is_a( $field, 'Gravity_Forms\Gravity_Forms\Settings\Fields\Base' ) ) {
			$field->set_error( $error_message );
		}

	}

	/**
	 * Gets the validation errors for a field.
	 * Returns validation errors associated with the specified field or a list of all validation messages (if a field isn't specified)
	 *
	 * @since Unknown
	 *
	 * @param \Gravity_Forms\Gravity_Forms\Settings\Fields\Base|array|boolean $field - Optional. The field meta. When specified, errors for this field will be returned
	 *
	 * @return string|array - If a field is specified, a string containing the error message will be returned. Otherwise, an array of all errors will be returned
	 */
	public function get_field_errors( $field = false ) {

		if ( ! $field ) {
			return $this->get_settings_renderer() ? $this->get_settings_renderer()->get_field_errors() : array();
		} elseif ( is_a( $field, 'Gravity_Forms\Gravity_Forms\Settings\Fields\Base' ) ) {
			return $field->get_error();
		}

		return array();

	}

	/**
	 * Gets the invalid field icon
	 * Returns the markup for an alert icon to indicate and highlight invalid fields.
	 *
	 * @param array $field - The field meta.
	 *
	 * @return string - The full markup for the icon
	 */
	public function get_error_icon( $field ) {

		$error = $this->get_field_errors( $field );

		return '<span
			class="gf_tooltip tooltip"
			title="<h6>' . esc_html__( 'Validation Error', 'gravityforms' ) . '</h6>' . $error . '"
			style="display:inline-block;position:relative;right:-3px;top:1px;font-size:14px;">
				<i class="fa fa-exclamation-circle icon-exclamation-sign gf_invalid"></i>
			</span>';
	}

	/**
	 * Returns the tooltip markup if a tooltip is configured for the supplied item (field/child field/choice).
	 *
	 * @since Unknown
	 *
	 * @param array $item The item properties.
	 *
	 * @return string
	 */
	public function maybe_get_tooltip( $item ) {

		return Gravity_Forms\Gravity_Forms\Settings\Settings::maybe_get_tooltip( $item );

	}

	/**
	 * Gets the required indicator
	 * Gets the markup of the required indicator symbol to highlight fields that are required
	 *
	 * @param $field - The field meta.
	 *
	 * @return string - Returns markup of the required indicator symbol
	 */
	public function get_required_indicator( $field ) {
		return '<span class="required">*</span>';
	}

	/**
	 * Checks if the specified field failed validation
	 *
	 * @since Unknown
	 *
	 * @param array|\Gravity_Forms\Gravity_Forms\Settings\Fields\Base $field - The field meta to be checked
	 *
	 * @return bool|mixed - Returns a validation error string if the field has failed validation. Otherwise returns false
	 */
	public function field_failed_validation( $field ) {

		$field_error = is_a( $field, 'Gravity_Forms\Gravity_Forms\Settings\Fields\Base' ) ? $field->get_error() : null;

		return ! empty( $field_error ) ? $field_error : false;

	}

	/**
	 * Filter settings fields.
	 * Runs through each field and applies the 'save_callback', if set, before saving the settings.
	 * To specify a custom save filter, use the 'save_callback' field meta property and implement the save filter function with the custom logic.
	 *
	 * @since      Unknown
	 * @deprecated 2.5 No longer used by internal code and not recommended.
	 *
	 * @param $fields   A list of all fields from the field meta configuration
	 * @param $settings A list of submitted settings values
	 *
	 * @return     $settings - The updated settings values.
	 */
	public function filter_settings( $fields, $settings ) {

		return $settings;

	}

	public function add_field_before( $name, $fields, $settings ) {
		return $this->add_field( $name, $fields, $settings, 'before' );
	}

	public function add_field_after( $name, $fields, $settings ) {
		return $this->add_field( $name, $fields, $settings, 'after' );
	}

	/**
	 * Add a field to existing defined fields.
	 *
	 * @since Unknown
	 * @since 2.5 Uses Settings renderer, $settings parameter deprecated.
	 *
	 * @param string                                                  $name     Name of field to insert before/after.
	 * @param array|Gravity_Forms\Gravity_Forms\Settings\Fields\Base[] $fields   Field(s) to add.
	 * @param array                                                   $settings Existing fields.
	 * @param string                                                  $pos      Insert field "before" or "after" existing field.
	 *
	 * @return array
	 */
	public function add_field( $name, $fields, $settings, $pos ) {

		if ( $this->get_settings_renderer() ) {
			return $this->get_settings_renderer()->add_field( $name, $fields, $pos, $settings );
		}

		if ( rgar( $fields, 'name' ) ) {
			$fields = array( $fields );
		}

		$pos_mod = $pos == 'before' ? 0 : 1;

		foreach ( $settings as &$section ) {
			for ( $i = 0; $i < count( $section['fields'] ); $i ++ ) {
				if ( $section['fields'][ $i ]['name'] == $name ) {
					array_splice( $section['fields'], $i + $pos_mod, 0, $fields );
					break 2;
				}
			}
		}

		return $settings;
	}

	/**
	 * Remove a field from existing defined fields.
	 *
	 * @since Unknown
	 * @since 2.5 Uses Settings renderer, $settings parameter deprecated.
	 *
	 * @param string $name     Name of field to insert before/after.
	 * @param array  $settings Existing fields.
	 *
	 * @return array
	 */
	public function remove_field( $name, $settings ) {

		if ( $this->get_settings_renderer() ) {
			return $this->get_settings_renderer()->remove_field( $name, $settings );
		}

		foreach ( $settings as &$section ) {
			for ( $i = 0; $i < count( $section['fields'] ); $i ++ ) {
				if ( $section['fields'][ $i ]['name'] == $name ) {
					array_splice( $section['fields'], $i, 1 );
					break 2;
				}
			}
		}

		return $settings;
	}

	/**
	 * Replace a field in existing defined fields.
	 *
	 * @since Unknown
	 * @since 2.5 Uses Settings renderer, $settings parameter deprecated.
	 *
	 * @param string                                                  $name     Name of field to insert before/after.
	 * @param array|Gravity_Forms\Gravity_Forms\Settings\Fields\Base[] $fields   Field(s) to add.
	 * @param array                                                   $settings Existing fields.
	 *
	 * @return array
	 */
	public function replace_field( $name, $fields, $settings ) {

		if ( $this->get_settings_renderer() ) {
			return $this->get_settings_renderer()->replace_field( $name, $fields, $settings );
		}

		if ( rgar( $fields, 'name' ) ) {
			$fields = array( $fields );
		}

		foreach ( $settings as &$section ) {
			for ( $i = 0; $i < count( $section['fields'] ); $i ++ ) {
				if ( $section['fields'][ $i ]['name'] == $name ) {
					array_splice( $section['fields'], $i, 1, $fields );
					break 2;
				}
			}
		}

		return $settings;

	}

	/**
	 * Get a specific settings field.
	 *
	 * @since 2.5
	 *
	 * @param string     $name     Name of field to retrieve.
	 * @param array|bool $settings Array of tabs or sections to search through. Defaults to defined fields.
	 *
	 * @return \Gravity_Forms\Gravity_Forms\Settings\Fields\Base|array|bool
	 */
	public function get_field( $name, $settings ) {

		if ( $this->get_settings_renderer() ) {
			return $this->get_settings_renderer()->get_field( $name, $settings );
		}

		foreach ( $settings as $section ) {
			for ( $i = 0; $i < count( $section['fields'] ); $i++ ) {
				if ( rgar( $section['fields'][ $i ], 'name' ) == $name ) {
					return $section['fields'][ $i ];
				}
			}
		}

		return false;

	}

	public function build_choices( $key_value_pairs ) {

		$choices = array();

		if ( ! is_array( $key_value_pairs ) ) {
			return $choices;
		}

		$first_key  = key( $key_value_pairs );
		$is_numeric = is_int( $first_key ) && $first_key === 0;

		foreach ( $key_value_pairs as $value => $label ) {
			if ( $is_numeric ) {
				$value = $label;
			}
			$choices[] = array( 'value' => $value, 'label' => $label );
		}

		return $choices;
	}

	//--------------  Simple Condition  ------------------------------------------------

	/**
	 * Helper to create a simple conditional logic set of fields. It creates one row of conditional logic with Field/Operator/Value inputs.
	 *
	 * @param mixed $setting_name_root - The root name to be used for inputs. It will be used as a prefix to the inputs that make up the conditional logic fields.
	 *
	 * @return string The HTML
	 */
	public function simple_condition( $setting_name_root ) {

		$conditional_fields = $this->get_conditional_logic_fields();

		$value_input = esc_js( '_gform_setting_' . esc_attr( $setting_name_root ) . '_value' );
		$object_type = esc_js( "simple_condition_{$setting_name_root}" );

		$str = $this->settings_select( array(
			'name'     => "{$setting_name_root}_field_id",
			'type'     => 'select',
			'choices'  => $conditional_fields,
			'class'    => 'optin_select',
			'onchange' => "jQuery('#" . esc_js( $setting_name_root ) . "_container').html(GetRuleValues('{$object_type}', 0, jQuery(this).val(), '', '{$value_input}'));"
		), false );

		$str .= $this->settings_select( array(
			'name'     => "{$setting_name_root}_operator",
			'type'     => 'select',
			'onchange' => "SetRuleProperty('{$object_type}', 0, 'operator', jQuery(this).val()); jQuery('#" . esc_js( $setting_name_root ) . "_container').html(GetRuleValues('{$object_type}', 0, jQuery('#{$setting_name_root}_field_id').val(), '', '{$value_input}'));",
			'choices'  => array(
				array(
					'value' => 'is',
					'label' => esc_html__( 'is', 'gravityforms' ),
				),
				array(
					'value' => 'isnot',
					'label' => esc_html__( 'is not', 'gravityforms' ),
				),
				array(
					'value' => '>',
					'label' => esc_html__( 'greater than', 'gravityforms' ),
				),
				array(
					'value' => '<',
					'label' => esc_html__( 'less than', 'gravityforms' ),
				),
				array(
					'value' => 'contains',
					'label' => esc_html__( 'contains', 'gravityforms' ),
				),
				array(
					'value' => 'starts_with',
					'label' => esc_html__( 'starts with', 'gravityforms' ),
				),
				array(
					'value' => 'ends_with',
					'label' => esc_html__( 'ends with', 'gravityforms' ),
				),
			),

		), false );

		$str .= sprintf( "<span id='%s_container'></span>", esc_attr( $setting_name_root ) );

		$field_id = $this->get_setting( "{$setting_name_root}_field_id" );

		$value    = $this->get_setting( "{$setting_name_root}_value" );
		$operator = $this->get_setting( "{$setting_name_root}_operator" );
		if ( empty( $operator ) ) {
			$operator = 'is';
		}

		$field_id_attribute = ! empty( $field_id ) ? $field_id : 'jQuery("#' . esc_attr( $setting_name_root ) . '_field_id").val()';

		$str .= "<script type='text/javascript'>
			var " . esc_attr( $setting_name_root ) . "_object = {'conditionalLogic':{'rules':[{'fieldId':'{$field_id}','operator':'{$operator}','value':'" . esc_attr( $value ) . "'}]}};

			jQuery(document).ready(
				function(){
					gform.addFilter( 'gform_conditional_object', 'SimpleConditionObject' );

					jQuery('#" . esc_attr( $setting_name_root ) . "_container').html(
											GetRuleValues('{$object_type}', 0, {$field_id_attribute}, '" . esc_attr( $value ) . "', '_gform_setting_" . esc_attr( $setting_name_root ) . "_value'));

					}
			);
			</script>";

		return $str;
	}

	/**
	 * Override this to define the array of choices which should be used to populate the Simple Condition fields drop down.
	 *
	 * Each choice should have 'label' and 'value' properties.
	 *
	 * @return array
	 */
	public function get_conditional_logic_fields() {
		return array();
	}

	/**
	 * Evaluate the rules defined for the Simple Condition field.
	 *
	 * @param string $setting_name_root The root name used as the prefix to the inputs that make up the Simple Condition field.
	 * @param array $form The form currently being processed.
	 * @param array $entry The entry currently being processed.
	 * @param array $feed The feed currently being processed or an empty array when the field is stored in the form settings.
	 *
	 * @return bool
	 */
	public function is_simple_condition_met( $setting_name_root, $form, $entry, $feed = array() ) {

		$settings = empty( $feed ) ? $this->get_form_settings( $form ) : rgar( $feed, 'meta', array() );

		$is_enabled = rgar( $settings, $setting_name_root . '_enabled' );

		if ( ! $is_enabled ) {
			// The setting is not enabled so we handle it as if the rules are met.

			return true;
		}

		// Build the logic array to be used by Gravity Forms when evaluating the rules.
		$logic = array(
			'logicType' => 'all',
			'rules'     => array(
				array(
					'fieldId'  => rgar( $settings, $setting_name_root . '_field_id' ),
					'operator' => rgar( $settings, $setting_name_root . '_operator' ),
					'value'    => rgar( $settings, $setting_name_root . '_value' ),
				),
			)
		);

		return GFCommon::evaluate_conditional_logic( $logic, $form, $entry );
	}


	//--------------  Form settings  ---------------------------------------------------

	/**
	 * Get the capabilities required to access the form settings page.
	 *
	 * @return array
	 */
	public function get_form_settings_capabilities() {
		return $this->_capabilities_form_settings;
	}

	/**
	 * Initializes form settings page
	 * Hooks up the required scripts and actions for the Form Settings page
	 */
	public function form_settings_init() {
		$view    = rgget( 'view' );
		$subview = rgget( 'subview' );
		add_filter( 'gform_form_settings_menu', array( $this, 'add_form_settings_menu' ), 10, 2 );

		if ( GFForms::get_page_query_arg() == 'gf_edit_forms' && $view == 'settings' && $subview == $this->get_slug() && $this->current_user_can_any( $this->get_form_settings_capabilities() ) ) {
			require_once( GFCommon::get_base_path() . '/tooltips.php' );
			add_action( 'gform_form_settings_page_' . $this->get_slug(), array( $this, 'form_settings_page' ) );

			// Let feed add-ons handle initializing their settings.
			if ( $this->method_is_overridden( 'form_settings_fields' ) ) {

				// Get current form.
				$form = GFCommon::gform_admin_pre_render( $this->get_current_form() );

				// Get fields.
				$sections = array_values( $this->form_settings_fields( $form ) );

				/**
				 * Allows code to modify the settings fields displayed on a given form settings page.
				 *
				 * @since 2.7
				 *
				 * @param array  $sections The current sections and fields.
				 * @parem string $form     The current form.
				 *
				 * @return array
				 */
				$sections = gf_apply_filters( array( 'gform_addon_form_settings_fields', rgar( $form, 'id' ), $this->get_slug() ), $sections, $form );


				$sections = $this->prepare_settings_sections( $sections, 'form_settings' );

				// Initialize new settings renderer.
				$renderer = new Settings(
					array(
						'capability'     => $this->get_form_settings_capabilities(),
						'fields'         => $sections,
						'initial_values' => $this->get_form_settings( $form ),
						'save_callback'  => function( $values ) use ( $form ) {
							$this->save_form_settings( $form, $values );
						},
						'after_fields'   => function() use ( $form ) {
							printf(
								'<script type="text/javascript">var form = %s;</script>',
								wp_json_encode( $form )
							);
						},
					)
				);

				// Save renderer to instance.
				$this->set_settings_renderer( $renderer );

			}
		}
	}

	/**
	 * Initializes plugin settings page
	 * Hooks up the required scripts and actions for the Plugin Settings page
	 */
	public function plugin_page_init() {

		if ( $this->current_user_can_any( $this->_capabilities_plugin_page ) ) {
			//creates the subnav left menu
			add_filter( 'gform_addon_navigation', array( $this, 'create_plugin_page_menu' ) );
		}

	}

	/**
	 * Creates plugin page menu item
	 * Target of gform_addon_navigation filter. Creates a menu item in the left nav, linking to the plugin page
	 *
	 * @param $menus - Current list of menu items
	 *
	 * @return array - Returns a new list of menu items
	 */
	public function create_plugin_page_menu( $menus ) {

		$menus[] = array( 'name' => $this->get_slug(), 'label' => $this->get_short_title(), 'callback' => array( $this, 'plugin_page_container' ), 'permission' => $this->_capabilities_plugin_page );

		return $menus;
	}

	/**
	 * Renders the form settings page.
	 * Sets up the form settings page.
	 *
	 * @since Unknown
	 */
	public function form_settings_page() {

		// Display page header.
		GFFormSettings::page_header( $this->_title );

		if ( $this->method_is_overridden( 'form_settings' ) ) {

			$form = $this->get_current_form();
			$form = GFCommon::gform_admin_pre_render( $form );

			// Enables plugins to override settings page by implementing a form_settings() function.
			$this->form_settings( $form );

		} else {

			// Make sure settings renderer is initialized before rendering.

			if ( ! $this->get_settings_renderer() ) {
				$this->form_settings_init();
			}

			$renderer = $this->get_settings_renderer();
			if ( $renderer ) {
				$renderer->render();
			} else {
				printf( '<p>%s</p>', esc_html__( 'Unable to render form settings.', 'gravityforms' ) );
			}

		}

		// Display page footer.
		GFFormSettings::page_footer();

	}

	/***
	 * Saves form settings if the submit button was pressed
	 *
	 * @since      Unknown
	 * @deprecated 2.5 No longer used by internal code and not recommended.
	 *
	 * @param array $form The form object
	 *
	 * @return null|true|false True on success, false on error, null on no action
	 */
	public function maybe_save_form_settings( $form ) {

		return null;

	}

	/***
	 * Saves form settings to form object
	 *
	 * @param array $form
	 * @param array $settings
	 *
	 * @return true|false True on success or false on error
	 */
	public function save_form_settings( $form, $settings ) {
		$existing_meta     = GFFormsModel::get_form_meta( $form['id'] );
		$existing_settings = rgar( $existing_meta, $this->get_slug() );

		if ( is_array( $existing_settings ) ) {
			$settings = array_merge( $existing_settings, $settings );
		}

		$form[ $this->get_slug() ] = $settings;
		$result               = GFFormsModel::update_form_meta( $form['id'], $form );

		return ! ( false === $result );
	}

	/**
	 * Checks whether the current Add-On has a form settings page.
	 *
	 * @return bool
	 */
	private function has_form_settings_page() {
		return $this->method_is_overridden( 'form_settings_fields' ) || $this->method_is_overridden( 'form_settings' );
	}

	/**
	 * Custom form settings page
	 * Override this function to implement a complete custom form settings page.
	 * Before overriding this function, consider using the form_settings_fields() and specifying your field meta.
	 */
	public function form_settings( $form ) {
	}

	/**
	 * Custom form settings title
	 * Override this function to display a custom title on the Form Settings Page.
	 * By default, the first section in the configuration done in form_settings_fields() will be used as the page title.
	 * Use this function to override that behavior and add a custom page title.
	 */
	public function form_settings_page_title() {
		return '';
	}

	/**
	 * Override this function to customize the form settings icon
	 */
	public function form_settings_icon() {
		return '';
	}

	/**
	 * Checks whether the current Add-On has a plugin page.
	 *
	 * @return bool
	 */
	private function has_plugin_page() {
		return $this->method_is_overridden( 'plugin_page' );
	}

	/**
	 * Override this function to create a custom plugin page
	 */
	public function plugin_page() {
	}

	/**
	 * Override this function to customize the plugin page icon
	 */
	public function plugin_page_icon() {
		return '';
	}

	/**
	 * Override this function to customize the plugin page title
	 */
	public function plugin_page_title() {
		return $this->_title;
	}

	/**
	 * Plugin page container
	 * Target of the plugin menu left nav icon. Displays the outer plugin page markup and calls plugin_page() to render the actual page.
	 * Override plugin_page() in order to provide a custom plugin page
	 */
	public function plugin_page_container() {
		?>
		<div class="wrap">
			<?php
			$icon = $this->plugin_page_icon();
			if ( ! empty( $icon ) ) {
				?>
				<img alt="<?php echo $this->get_short_title() ?>" style="margin: 15px 7px 0pt 0pt; float: left;" src="<?php echo $icon ?>" />
			<?php
			}
			?>

			<h2 class="gf_admin_page_title"><?php echo $this->plugin_page_title() ?></h2>
			<?php

			$this->plugin_page();
			?>
		</div>
	<?php
	}

	/**
	 * Checks whether the current Add-On has a top level app menu.
	 *
	 * @return bool
	 */
	public function has_app_menu() {
		return $this->has_app_settings() || $this->method_is_overridden( 'get_app_menu_items' );
	}

	/**
	 * Creates a top level app menu. Adds the app settings page automatically if it's configured.
	 * Target of the WordPress admin_menu action.
	 * Not intended to be overridden or called directly by add-ons.
	 */
	public function create_app_menu() {

		$has_full_access = current_user_can( 'gform_full_access' );
		$min_cap         = GFCommon::current_user_can_which( $this->_capabilities_app_menu );
		if ( empty( $min_cap ) ) {
			$min_cap = 'gform_full_access';
		}

		$menu_items = $this->get_app_menu_items();

		$addon_menus = array();

		/**
		 * Filters through addon menus (filter by addon slugs)
		 *
		 * @param array $addon_menus A modifiable array of admin addon menus
		 */
		$addon_menus = apply_filters( 'gform_addon_app_navigation_' . $this->get_slug(), $addon_menus );

		$parent_menu = self::get_parent_menu( $menu_items, $addon_menus );

		if ( empty( $parent_menu ) ) {
			return;
		}

		// Add a top-level left nav
		$callback = isset( $parent_menu['callback'] ) ? $parent_menu['callback'] : array( $this, 'app_tab_page' );

		global $menu;
		$number = 10;
		$menu_position = '16.' . $number;
		while ( isset( $menu[$menu_position] ) ) {
			$number += 10;
			$menu_position = '16.' . $number;
		}

		/**
		 * Modify the menu position of an add-on menu
		 *
		 * @param int $menu_position The Menu position of the add-on menu
		 */
		$menu_position = apply_filters( 'gform_app_menu_position_' . $this->get_slug(), $menu_position );
		$this->app_hook_suffix = add_menu_page( $this->get_short_title(), $this->get_short_title(), $has_full_access ? 'gform_full_access' : $min_cap, $parent_menu['name'], $callback, $this->get_app_menu_icon(), $menu_position );

		if ( method_exists( $this, 'load_screen_options' ) ) {
			add_action( "load-$this->app_hook_suffix", array( $this, 'load_screen_options' ) );
		}

		// Adding submenu pages
		foreach ( $menu_items as $menu_item ) {
			$callback = isset( $menu_item['callback'] ) ? $menu_item['callback'] : array( $this, 'app_tab_page' );
			add_submenu_page( $parent_menu['name'], $menu_item['label'], $menu_item['label'], $has_full_access || empty( $menu_item['permission'] ) ? 'gform_full_access' : $menu_item['permission'], $menu_item['name'], $callback );
		}

		if ( is_array( $addon_menus ) ) {
			foreach ( $addon_menus as $addon_menu ) {
				add_submenu_page( $parent_menu['name'], $addon_menu['label'], $addon_menu['label'], $has_full_access ? 'gform_full_access' : $addon_menu['permission'], $addon_menu['name'], $addon_menu['callback'] );
			}
		}

		if ( $this->has_app_settings() ) {
			add_submenu_page( $parent_menu['name'], esc_html__( 'Settings', 'gravityforms' ), esc_html__( 'Settings', 'gravityforms' ), $has_full_access ? 'gform_full_access' : $this->_capabilities_app_settings, $this->get_slug() . '_settings', array( $this, 'app_tab_page' ) );
		}

	}

	/**
	 * Returns the parent menu item
	 *
	 * @param $menu_items
	 * @param $addon_menus
	 *
	 * @return array|bool The parent menu araray or false if none
	 */
	private function get_parent_menu( $menu_items, $addon_menus ) {
		$parent = false;
		if ( GFCommon::current_user_can_any( $this->_capabilities_app_menu ) ) {
			foreach ( $menu_items as $menu_item ) {
				if ( $this->current_user_can_any( $menu_item['permission'] ) ) {
					$parent = $menu_item;
					break;
				}
			}
		} elseif ( is_array( $addon_menus ) && sizeof( $addon_menus ) > 0 ) {
			foreach ( $addon_menus as $addon_menu ) {
				if ( $this->current_user_can_any( $addon_menu['permission'] ) ) {
					$parent = array( 'name' => $addon_menu['name'], 'callback' => $addon_menu['callback'] );
					break;
				}
			}
		} elseif ( $this->has_app_settings() && $this->current_user_can_any( $this->_capabilities_app_settings ) ) {
			$parent = array( 'name' => $this->get_slug() . '_settings', 'callback' => array( $this, 'app_settings' ) );
		}

		return $parent;
	}

	/**
	 * Override this function to create a top level app menu.
	 *
	 * e.g.
	 * $menu_item['name'] = 'gravitycontacts';
	 * $menu_item['label'] = __("Contacts", 'gravitycontacts');
	 * $menu_item['permission'] = 'gravitycontacts_view_contacts';
	 * $menu_item['callback'] = array($this, 'app_menu');
	 *
	 * @return array The array of menu items
	 */
	public function get_app_menu_items() {
		return array();
	}

	/**
	 * Override this function to specify a custom icon for the top level app menu.
	 * Accepts a dashicon class or a URL.
	 *
	 * @return string
	 */
	public function get_app_menu_icon() {
		return '';
	}

	/**
	 * Override this function to load custom screen options.
	 *
	 * e.g.
	 * $screen = get_current_screen();
	 * if(!is_object($screen) || $screen->id != $this->app_hook_suffix)
	 *     return;
	 *
	 * if($this->is_contact_list_page()){
	 *     $args = array(
	 *         'label' => __('Contacts per page', 'gravitycontacts'),
	 *         'default' => 20,
	 *         'option' => 'gcontacts_per_page'
	 *     );
	 * add_screen_option( 'per_page', $args );
	 */
	public function load_screen_options() {
	}

	/**
	 * Handles the rendering of app menu items that implement the tabs UI.
	 *
	 * Not intended to be overridden or called directly by add-ons.
	 */
	public function app_tab_page() {
		$page        = sanitize_text_field( GFForms::get_page_query_arg() );
		$current_tab = sanitize_text_field( (string) rgget( 'view' ) );

		if ( $page == $this->get_slug() . '_settings' ) {

			$tabs = $this->get_app_settings_tabs();

		} else {

			$menu_items = $this->get_app_menu_items();

			$current_menu_item = false;
			foreach ( $menu_items as $menu_item ) {
				if ( $menu_item['name'] == $page ) {
					$current_menu_item = $menu_item;
					break;
				}
			}

			if ( empty( $current_menu_item ) ) {
				return;
			}

			if ( empty( $current_menu_item['tabs'] ) ) {
				return;
			}

			$tabs = $current_menu_item['tabs'];
		}

		if ( empty( $current_tab ) ) {
			foreach ( $tabs as $tab ) {
				if ( ! isset( $tab['permission'] ) || $this->current_user_can_any( $tab['permission'] ) ) {
					$current_tab = $tab['name'];
					break;
				}
			}
		}

		if ( empty( $current_tab ) ) {
			wp_die( esc_html__( "You don't have adequate permission to view this page", 'gravityforms' ) );
		}

		foreach ( $tabs as $tab ) {
			if ( $tab['name'] == $current_tab && isset( $tab['callback'] ) && is_callable( $tab['callback'] ) ) {
				if ( isset( $tab['permission'] ) && ! $this->current_user_can_any( $tab['permission'] ) ) {
					wp_die( esc_html__( "You don't have adequate permission to view this page", 'gravityforms' ) );
				}

				$title = rgar( $tab,'title' );

				if ( empty( $title ) ) {
					$title = isset( $tab['label'] ) ? $tab['label'] : $tab['name'];
				}

				$this->app_tab_page_header( $tabs, $current_tab, $title, '' );
				call_user_func( $tab['callback'] );
				$this->app_tab_page_footer();

				return;
			}
		}

		$this->app_tab_page_header( $tabs, $current_tab, $current_tab, '' );
		/**
		 * Fires when an addon page and tab is accessed.
		 *
		 * Typically used to render settings tab content.
		 */
		$action_hook = 'gform_addon_app_' . $page . '_' . str_replace( ' ', '_', $current_tab );
		do_action( $action_hook );
		$this->app_tab_page_footer();

	}

	/**
	 * Returns the form settings for the Add-On
	 *
	 * @param $form
	 *
	 * @return array
	 */
	public function get_form_settings( $form ) {
		return rgar( $form, $this->get_slug() );
	}

	/**
	 * Add the form settings tab.
	 *
	 * Override this function to add the tab conditionally.
	 *
	 *
	 * @param $tabs
	 * @param $form_id
	 *
	 * @return array
	 */
	public function add_form_settings_menu( $tabs, $form_id ) {

		$tabs[] = array(
			'name'           => $this->get_slug(),
			'label'          => $this->get_short_title(),
			'query'          => array( 'fid' => null ),
			'capabilities'   => $this->get_form_settings_capabilities(),
			'icon'           => $this->get_menu_icon(),
			'icon_namespace' => $this->get_icon_namespace(),
		);

		return $tabs;
	}

	/**
	 * Override this function to specify the settings fields to be rendered on the form settings page
	 */
	public function form_settings_fields( $form ) {
		// should return an array of sections, each section contains a title, description and an array of fields
		return array();
	}




	// # PLUGIN SETTINGS -----------------------------------------------------------------------------------------------

	/**
	 * Initialize Plugin Settings page.
	 *
	 * @since Unknown
	 */
	public function plugin_settings_init() {

		// Get current subview.
		$subview = rgget( 'subview' );

		// Register settings page.
		GFForms::add_settings_page(
			array(
				'name'           => $this->get_slug(),
				'tab_label'      => $this->get_short_title(),
				'icon'           => $this->get_menu_icon(),
				'icon_namespace' => $this->get_icon_namespace(),
				'title'          => $this->plugin_settings_title(),
				'handler'        => array( $this, 'plugin_settings_page' ),
			)
		);

		// Load Tooltips functions.
		if ( GFForms::get_page_query_arg() == 'gf_settings' && $subview == $this->get_slug() && $this->current_user_can_any( $this->_capabilities_settings_page ) ) {
			require_once( GFCommon::get_base_path() . '/tooltips.php' );
		}

		// Add link to Plugin Settings page on Plugins page.
		add_filter( 'plugin_action_links', array( $this, 'plugin_settings_link' ), 10, 2 );

		if ( $this->is_plugin_settings( $this->get_slug() ) ) {

			// Get fields.
			$sections = $this->plugin_settings_fields();
			$sections = $this->prepare_settings_sections( $sections, 'plugin_settings' );

			// Initialize new settings renderer.
			$renderer = new Settings(
				array(
					'capability'     => $this->_capabilities_settings_page,
					'fields'         => $sections,
					'initial_values' => $this->get_plugin_settings(),
					'save_callback'  => array( $this, 'update_plugin_settings' ),
					'field_encryption_disabled' => true,
				)
			);

			// Save renderer to instance.
			$this->set_settings_renderer( $renderer );

		}

	}

	/**
	 * Add link to Plugin Settings page on Plugins page.
	 *
	 * @since Unknown
	 *
	 * @param string[] $links An array of plugin action links.
	 * @param string   $file  Path to the plugin file relative to the plugins directory.
	 *
	 * @return string[]
	 */
	public function plugin_settings_link( $links, $file ) {
		if ( $file != $this->get_path() ) {
			return $links;
		}

		array_unshift( $links, '<a href="' . admin_url( 'admin.php' ) . '?page=gf_settings&subview=' . $this->get_slug() . '">' . esc_html__( 'Settings', 'gravityforms' ) . '</a>' );

		return $links;

	}

	/**
	 * Plugin Settings page.
	 *
	 * @since Unknown
	 */
	public function plugin_settings_page() {

		if ( $this->has_deprecated_elements() ) {
			printf(
				'<div class="push-alert-red" style="border-left: 1px solid #E6DB55; border-right: 1px solid #E6DB55;">%s</div>',
				esc_html__( 'This add-on needs to be updated. Please contact the developer.', 'gravityforms' )
			);
		}

		// Display overridden settings page.
		if ( $this->method_is_overridden( 'plugin_settings' ) ) {

			$this->plugin_settings();

		} else if ( $this->maybe_uninstall() ) {

			printf(
				'<div class="push-alert-gold" style="border-left: 1px solid #E6DB55; border-right: 1px solid #E6DB55;">%s</div>',
				sprintf(
					esc_html__( '%s has been successfully uninstalled. It can be re-activated from the %splugins page%s.', 'gravityforms' ),
					$this->_title,
					'<a href="plugins.php">',
					'</a>'
				)
			);

		} else {

			if ( ! $this->get_settings_renderer() ) {
				$this->plugin_settings_init();
			}

			$this->get_settings_renderer()->render();

			// If the render_uninstall method is overridden by the child class, display it on the settings page.
			if ( $this->method_is_overridden( 'render_uninstall' ) ) {
				$this->render_uninstall();
			}
		}

	}

	/**
	 * Returns title for Plugin Settings page header.
	 *
	 * @since Unknown
	 *
	 * @return string
	 */
	public function plugin_settings_title() {
		return sprintf( esc_html__( "%s Settings", "gravityforms" ), $this->get_short_title() );
	}

	/**
	 * Returns icon for Plugin Settings page header.
	 *
	 * @since Unknown
	 *
	 * @return string
	 */
	public function plugin_settings_icon() {
		return '';
	}

	/**
	 * Override this function to add a custom settings page.
	 *
	 * @since Unknown
	 */
	public function plugin_settings() {
	}

	/**
	 * Checks whether the current Add-On has a settings page.
	 *
	 * @since Unknown
	 *
	 * @return bool
	 */
	public function has_plugin_settings_page() {
		return $this->method_is_overridden( 'plugin_settings_fields' ) || $this->method_is_overridden( 'plugin_settings_page' ) || $this->method_is_overridden( 'plugin_settings' );
	}

	/**
	 * @var array Holds the cached plugin settings.
	 *
	 * @since 2.7.17
	 */
	private static $_plugin_settings = array();

	/**
	 * Returns the currently saved plugin settings
	 *
	 * @since Unknown
	 *
	 * @since 2.7.17 Added caching of plugin settings and encrypting of settings.
	 *
	 * @return array|false Returns the plugin settings or false if the settings haven't been saved yet.
	 */
	public function get_plugin_settings() {
		if ( isset( self::$_plugin_settings[ $this->get_slug() ] ) ) {
			return self::$_plugin_settings[$this->get_slug() ];
		}

		self::$_plugin_settings[ $this->get_slug() ] = $this->get_encryptor()->decrypt( get_option( 'gravityformsaddon_' . $this->get_slug() . '_settings' ) );

		return self::$_plugin_settings[ $this->get_slug() ];
	}

	/**
	 * Get plugin setting.
	 * Returns the plugin setting specified by the $setting_name parameter.
	 *
	 * @since Unknown
	 *
	 * @param string $setting_name Plugin setting to be returned
	 *
	 * @return string|array|int|bool|null  Returns the specified plugin setting or null if the setting doesn't exist
	 */
	public function get_plugin_setting( $setting_name ) {

		$settings = $this->get_plugin_settings();
		return isset( $settings[ $setting_name ] ) ? $settings[ $setting_name ] : null;
	}

	/**
	 * Updates plugin settings with the provided settings
	 *
	 * @since Unknown
	 *
	 * @since 2.7.17 Added caching of plugin settings and encrypting of settings.
	 *
	 * @param array $settings Plugin settings to be saved.
	 */
	public function update_plugin_settings( $settings ) {

		self::$_plugin_settings[$this->get_slug() ] = $settings;
		update_option( 'gravityformsaddon_' . $this->get_slug() . '_settings', $this->get_encryptor()->encrypt( $settings ) );
	}

	/**
	 * Saves the plugin settings if the submit button was pressed
	 *
	 * @since      Unknown
	 * @deprecated 2.5 No longer used by internal code and not recommended.
	 */
	public function maybe_save_plugin_settings() {

		return null;

	}

	/**
	 * Override this function to specify the settings fields to be rendered on the plugin settings page.
	 *
	 * @since Unknown
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {
		return array();
	}





	//--------------  App Settings  ---------------------------------------------------

	/**
	 * Returns the tabs for the settings app menu item
	 *
	 * Not intended to be overridden or called directly by add-ons.
	 *
	 * @return array|mixed|void
	 */
	public function get_app_settings_tabs() {

		// Build left side options, always have app Settings first and Uninstall last, put add-ons in the middle

		$setting_tabs = array( array( 'name' => 'settings', 'label' => esc_html__( 'Settings', 'gravityforms' ), 'callback' => array( $this, 'app_settings_tab' ) ) );

		/**
		 * Filters the tabs within the settings menu.
		 *
		 * This filter is appended by the page slug.  Ex: gform_addon_app_settings_menu_SLUG
		 *
		 * @param array $setting_tabs Contains the information on the settings tabs.
		 */
		$setting_tabs = apply_filters( 'gform_addon_app_settings_menu_' . $this->get_slug(), $setting_tabs );

		if ( $this->current_user_can_uninstall() ) {
			$setting_tabs[] = array( 'name' => 'uninstall', 'label' => esc_html__( 'Uninstall', 'gravityforms' ), 'callback' => array( $this, 'app_settings_uninstall_tab' ) );
		}

		ksort( $setting_tabs, SORT_NUMERIC );

		return $setting_tabs;
	}

	/**
	 * Renders the app settings uninstall tab.
	 *
	 * Not intended to be overridden or called directly by add-ons.
	 */
	public function app_settings_uninstall_tab() {

		if ( $this->maybe_uninstall() ) {
			?>
			<div class="push-alert-gold" style="border-left: 1px solid #E6DB55; border-right: 1px solid #E6DB55;">
				<?php printf( esc_html__( '%s has been successfully uninstalled. It can be re-activated from the %splugins page%s.', 'gravityforms' ), esc_html( $this->_title ), "<a href='plugins.php'>", '</a>' ); ?>
			</div>
		<?php

		} else {
			if ( $this->current_user_can_uninstall() ) {
			?>
			<form action="" method="post">
				<?php wp_nonce_field( 'uninstall', 'gf_addon_uninstall' ) ?>
				<?php  ?>
					<h3>
						<span><i class="fa fa-times"></i> <?php printf( esc_html__( 'Uninstall %s', 'gravityforms' ), $this->get_short_title() ); ?></span>
					</h3>

					<div class="delete-alert alert_red">

						<h3>
							<i class="fa fa-exclamation-triangle gf_invalid"></i> <?php esc_html_e( 'Warning', 'gravityforms' ); ?>
						</h3>

						<div class="gf_delete_notice">
							<?php echo $this->uninstall_warning_message() ?>
						</div>

						<?php
						$uninstall_button = '<input type="submit" name="uninstall" value="' . sprintf( esc_attr__( 'Uninstall %s', 'gravityforms' ), $this->get_short_title() ) . '" class="button" onclick="return confirm(\'' . esc_js( $this->uninstall_confirm_message() ) . '\');" onkeypress="return confirm(\'' . esc_js( $this->uninstall_confirm_message() ) . '\');"/>';
						echo $uninstall_button;
						?>

					</div>
			</form>
			<?php
			}
		}
	}

	/**
	 * Renders the header for the tabs UI.
	 *
	 * @param        $tabs
	 * @param        $current_tab
	 * @param        $title
	 * @param string $message
	 */
	public function app_tab_page_header( $tabs, $current_tab, $title, $message = '' ) {

		// Print admin styles
		wp_print_styles( array( 'jquery-ui-styles', 'gform_admin', 'gform_settings' ) );

		?>

		<div class="wrap <?php echo GFCommon::get_browser_class(); ?>">

			<?php GFCommon::gf_header(); ?>

			<?php if ( $message ) { ?>
				<div id="message" class="updated"><p><?php echo $message; ?></p></div>
			<?php } ?>

			<div class="gform-settings__wrapper">

				<nav class="gform-settings__navigation">
				<?php
				foreach ( $tabs as $tab ) {

					// Check for capabilities.
					if ( isset( $tab['permission'] ) && ! $this->current_user_can_any( $tab['permission'] ) ) {
						continue;
					}

					// Prepare tab label, URL.
					$label = isset( $tab['label'] ) ? $tab['label'] : $tab['name'];
					$url  = add_query_arg( array( 'view' => $tab['name'] ) );

					// Get tab icon.
					$icon_markup = GFCommon::get_icon_markup( $tab );

					printf(
						'<a href="%s"%s><span class="icon">%s</span> <span class="label">%s</span></a>',
						esc_url( $url ),
						$current_tab === $tab['name'] ? ' class="active"' : '',
						is_null( $icon_markup ) ? '<i class="gform-icon gform-icon--cog"></i>' : $icon_markup,
						esc_html( $label )
					);
				}
				?>
			</nav>

			<div class="gform-settings__content" id="tab_<?php echo esc_attr( $current_tab ); ?>">

	<?php
	}

	/**
	 * Renders the footer for the tabs UI.
	 *
	 */
	public function app_tab_page_footer() {
					?>
				</div>
				<!-- / gform-settings__content -->
			</div>
			<!-- / gform-settings__wrapper -->

		</div> <!-- / wrap -->

	<?php
	}

	public function app_settings_tab() {

		// Display overridden settings page.
		if ( $this->method_is_overridden( 'app_settings' ) ) {

			$this->app_settings();

		} else if ( $this->maybe_uninstall() ) {

			printf(
				'<div class="alert success">%s</div>',
				sprintf(
					esc_html__( '%s has been successfully uninstalled. It can be re-activated from the %splugins page%s.', 'gravityforms' ),
					$this->_title,
					'<a href="plugins.php">',
					'</a>'
				)
			);

		} else {

			// Get fields.
			$sections = $this->app_settings_fields();
			$sections = $this->prepare_settings_sections( $sections, 'app_settings' );

			// Initialize new settings renderer.
			$renderer = new Settings(
				array(
					'capability'     => $this->_capabilities_app_settings,
					'fields'         => $sections,
					'header'         => array(
						'icon'  => $this->app_settings_icon(),
						'title' => $this->app_settings_title(),
					),
					'initial_values' => $this->get_app_settings(),
					'save_callback'  => array( $this, 'update_app_settings' ),
				)
			);

			// Save renderer to instance.
			$this->set_settings_renderer( $renderer );

			$this->get_settings_renderer()->render();

		}

	}

	/**
	 * Override this function to specific a custom app settings title
	 *
	 * @return string
	 */
	public function app_settings_title() {
		return sprintf( esc_html__( '%s Settings', 'gravityforms' ), $this->get_short_title() );
	}

	/**
	 * Override this function to specific a custom app settings icon
	 *
	 * @return string
	 */
	public function app_settings_icon() {
		return '';
	}

	/**
	 * Checks whether the current Add-On has a settings page.
	 *
	 * @return bool
	 */
	public function has_app_settings() {
		return $this->method_is_overridden( 'app_settings_fields' ) || $this->method_is_overridden( 'app_settings' );
	}

	/**
	 * Override this function to add a custom app settings page.
	 */
	public function app_settings() {
	}

	/**
	 * Returns the currently saved plugin settings
	 * @return mixed
	 */
	public function get_app_settings() {
		return get_option( 'gravityformsaddon_' . $this->get_slug() . '_app_settings' );
	}

	/**
	 * Get app setting
	 * Returns the app setting specified by the $setting_name parameter
	 *
	 * @param string $setting_name - Plugin setting to be returned
	 *
	 * @return mixed  - Returns the specified plugin setting or null if the setting doesn't exist
	 */
	public function get_app_setting( $setting_name ) {
		$settings = $this->get_app_settings();

		return isset( $settings[ $setting_name ] ) ? $settings[ $setting_name ] : null;
	}

	/**
	 * Updates app settings with the provided settings
	 *
	 * @param array $settings - App settings to be saved
	 */
	public function update_app_settings( $settings ) {
		update_option( 'gravityformsaddon_' . $this->get_slug() . '_app_settings', $settings );
	}

	/**
	 * Saves the plugin settings if the submit button was pressed
	 *
	 */
	public function maybe_save_app_settings() {

		if ( $this->is_save_postback() ) {

			check_admin_referer( $this->get_slug() . '_save_settings', '_' . $this->get_slug() . '_save_settings_nonce' );

			if ( ! $this->current_user_can_any( $this->_capabilities_app_settings ) ) {
				GFCommon::add_error_message( esc_html__( "You don't have sufficient permissions to update the settings.", 'gravityforms' ) );
				return false;
			}

			// store a copy of the previous settings for cases where action would only happen if value has changed
			$this->set_previous_settings( $this->get_app_settings() );

			$settings = $this->get_posted_settings();
			$sections = $this->app_settings_fields();
			$is_valid = $this->validate_settings( $sections, $settings );

			if ( $is_valid ) {
				$settings = $this->filter_settings( $sections, $settings );
				$this->update_app_settings( $settings );
				GFCommon::add_message( $this->get_save_success_message( $sections ) );
			} else {
				GFCommon::add_error_message( $this->get_save_error_message( $sections ) );
			}
		}

	}

	/**
	 * Override this function to specify the settings fields to be rendered on the plugin settings page
	 * @return array
	 */
	public function app_settings_fields() {
		// should return an array of sections, each section contains a title, description and an array of fields
		return array();
	}

	/**
	 * Returns an flattened array of field settings for the specified settings type ignoring sections.
	 *
	 * @param string $settings_type The settings type. e.g. 'plugin'
	 *
	 * @return array
	 */
	public function settings_fields_only( $settings_type = 'plugin' ) {

		$fields = array();

		if ( ! is_callable( array( $this, "{$settings_type}_settings_fields" ) ) ) {
			return $fields;
		}

		$sections = call_user_func( array( $this, "{$settings_type}_settings_fields" ) );

		foreach ( $sections as $section ) {
			foreach ( $section['fields'] as $field ) {
				$fields[] = $field;
			}
		}

		return $fields;
	}

	//--------------  Uninstall  ---------------

	/**
	 * Override this function to customize the uninstall message displayed on the uninstall page.
	 *
	 * @since 2.5.9.4
	 *
	 * @return string
	 */
	public function uninstall_message() {
		return sprintf(
				__( 'This operation deletes ALL %s settings.', 'gravityforms' ),
				$this->get_short_title()
		);
	}

	/**
	 * Override this function to customize the markup for the uninstall section on the plugin settings page.
	 *
	 * @since Unknown
	 */
	public function render_uninstall() {

		// If user cannot uninstall, exit.
		if ( ! $this->current_user_can_uninstall() ) {
			return;
		}
		$icon        = array(
			'icon'           => $this->get_menu_icon(),
			'icon_namespace' => $this->get_icon_namespace(),
		);
		$icon_markup = GFCommon::get_icon_markup( $icon, 'dashicon-admin-generic' );

		// Show different panel styles for the uninstall page and the individual settings pages.
		if ( rgget( 'subview' ) == 'uninstall' ) {
			?>
			<form action="" method="post" class="gform-settings-panel gform-settings-panel__addon-uninstall">
				<?php wp_nonce_field( 'uninstall', 'gf_addon_uninstall' ); ?>
				<div class="gform-settings-panel__content">
					<div class="addon-logo dashicons"><?php echo $icon_markup; ?></div>
					<div class="addon-uninstall-text">
						<h4 class="gform-settings-panel__title"><?php printf( esc_html__( '%s', 'gravityforms' ), $this->get_short_title() ) ?></h4>
						<div><?php echo esc_html( $this->uninstall_message() ); ?></div>
					</div>
					<div class="addon-uninstall-button">
						<input id="addon" name="addon" type="hidden" value="<?php echo $this->get_short_title(); ?>">
						<button type="submit" aria-label="<?php printf( esc_html__( 'Uninstall %s', 'gravityforms'), $this->get_short_title() ); ?>" name="uninstall_addon" value="uninstall" class="button uninstall-addon red" onclick="return confirm('<?php echo esc_js( $this->uninstall_confirm_message() ); ?>');" onkeypress="return confirm('<?php echo esc_js( $this->uninstall_confirm_message() ); ?>');">
							<i class="dashicons dashicons-trash"></i>
							<?php esc_attr_e( 'Uninstall', 'gravityforms' ); ?>
						</button>
					</div>
				</div>
			</form>
			<?php
		} else {
			?>
			<form action="" method="post" class="gform-settings-panel gform-settings-panel--collapsible gform-settings-panel--collapsed gform-settings-panel__uninstall">
				<?php wp_nonce_field( 'uninstall', 'gf_addon_uninstall' ); ?>
				<header class="gform-settings-panel__header">
					<h4 class="gform-settings-panel__title"><?php printf( esc_html__( 'Uninstall %s Add-On', 'gravityforms' ), $this->get_short_title() ) ?></h4>
					<span class="gform-settings-panel__collapsible-control">
						<input
							type="checkbox"
							name="gform_settings_section_collapsed_uninstall"
							id="gform_settings_section_collapsed_uninstall"
							value="1"
							onclick="this.checked ? this.closest( '.gform-settings-panel' ).classList.add( 'gform-settings-panel--collapsed' ) : this.closest( '.gform-settings-panel' ).classList.remove( 'gform-settings-panel--collapsed' )"
							checked
						/>
						<label class="gform-settings-panel__collapsible-toggle" for="gform_settings_section_collapsed_uninstall"><span class="screen-reader-text"><?php esc_html_e( 'Toggle Uninstall Section' ); ?></span></label>
					</span>
				</header>
				<div class="gform-settings-panel__content">

					<div class="alert error">
						<?php echo $this->uninstall_warning_message(); ?>
					</div>

					<button type="submit" name="uninstall" value="uninstall" class="button red" onclick="return confirm('<?php echo esc_js( $this->uninstall_confirm_message() ); ?>');" onkeypress="return confirm('<?php echo esc_js( $this->uninstall_confirm_message() ); ?>');"><?php esc_attr_e( 'Uninstall Add-On', 'gravityforms' ); ?></button>
				</div>
			</form>
			<?php
		}

	}

	/**
	 * Render a settings button for addons that have overridden the render_uninstall field. Not intended to be called directly or overridden by addons.
	 *
	 * @since 2.5
	 */
	public function render_settings_button() {

		if ( ! $this->current_user_can_uninstall() ) {
			return;
		}
		$icon        = array(
			'icon'           => $this->get_menu_icon(),
			'icon_namespace' => $this->get_icon_namespace(),
		);
		$icon_markup = GFCommon::get_icon_markup( $icon, 'dashicon-admin-generic' );
		$url         = add_query_arg( array( 'subview' => $this->get_slug() ), admin_url( 'admin.php?page=gf_settings' ) );
		?>
		<form action="" method="post" class="gform-settings-panel gform-settings-panel__addon-uninstall">
			<?php wp_nonce_field( 'uninstall', 'gf_addon_uninstall' ); ?>
			<div class="gform-settings-panel__content">
				<div class="addon-logo dashicons"><?php echo $icon_markup; ?></div>
				<div class="addon-uninstall-text">
					<h4 class="gform-settings-panel__title"><?php printf( esc_html__( '%s', 'gravityforms' ), $this->get_short_title() ) ?></h4>
					<div><?php esc_attr_e( 'To continue uninstalling this add-on click the settings button.', 'gravityforms' ) ?></div>
				</div>
				<div class="addon-uninstall-button">
					<a href="<?php echo esc_url( $url ); ?>" aria-label="<?php echo 'Visit ' . $this->get_short_title() . ' Settings page'; ?>" class="button addon-settings">
						<i class="gform-icon gform-icon--cog"></i>
						<?php esc_attr_e( 'Settings', 'gravityforms' ); ?>
					</a>
				</div>
			</div>
		</form>
		<?php
	}

	public function uninstall_warning_message() {
		return sprintf( esc_html__( '%sThis operation deletes ALL %s settings%s. If you continue, you will NOT be able to retrieve these settings.', 'gravityforms' ), '<strong>', esc_html( $this->get_short_title() ), '</strong>' );
	}

	public function uninstall_confirm_message() {
		return sprintf( __( "Warning! ALL %s settings will be deleted. This cannot be undone. 'OK' to delete, 'Cancel' to stop", 'gravityforms' ), __( $this->get_short_title() ) );
	}
	/**
	 * Not intended to be overridden or called directly by Add-Ons.
	 *
	 * @ignore
	 */
	public function maybe_uninstall() {
		if ( rgpost( 'uninstall' ) ) {
			check_admin_referer( 'uninstall', 'gf_addon_uninstall' );

			return $this->uninstall_addon();
		}

		return false;
	}

	/**
	 * Removes all settings and deactivates the Add-On.
	 * Not intended to be overridden or called directly by Add-Ons.
	 *
	 * @ignore
	 */
	public function uninstall_addon() {

		if ( ! $this->current_user_can_uninstall() ) {
			die( esc_html__( "You don't have adequate permission to uninstall this add-on: " . $this->_title, 'gravityforms' ) );
		}

		$continue = $this->uninstall();
		if ( false === $continue ) {
			return false;
		}

		global $wpdb;

		$forms        = GFFormsModel::get_forms();
		$all_form_ids = array();

		// remove entry meta
		$meta_table = version_compare( GFFormsModel::get_database_version(), '2.3-dev-1', '<' ) ? GFFormsModel::get_lead_meta_table_name() : GFFormsModel::get_entry_meta_table_name();
		foreach ( $forms as $form ) {
			$all_form_ids[] = $form->id;
			$entry_meta     = $this->get_entry_meta( array(), $form->id );
			if ( is_array( $entry_meta ) ) {
				foreach ( array_keys( $entry_meta ) as $meta_key ) {
					$sql = $wpdb->prepare( "DELETE from $meta_table WHERE meta_key=%s", $meta_key );
					$wpdb->query( $sql );
				}
			}
		}

		//remove form settings
		if ( ! empty( $all_form_ids ) ) {
			$form_metas = GFFormsModel::get_form_meta_by_id( $all_form_ids );
			require_once( GFCommon::get_base_path() . '/form_detail.php' );
			foreach ( $form_metas as $form_meta ) {
				if ( isset( $form_meta[ $this->get_slug() ] ) ) {
					unset( $form_meta[ $this->get_slug() ] );
					$form_json = json_encode( $form_meta );
					GFFormDetail::save_form_info( $form_meta['id'], addslashes( $form_json ) );
				}
			}
		}

		//removing options
		delete_option( 'gravityformsaddon_' . $this->get_slug() . '_settings' );
		delete_option( 'gravityformsaddon_' . $this->get_slug() . '_app_settings' );
		delete_option( 'gravityformsaddon_' . $this->get_slug() . '_version' );


		//Deactivating plugin
		deactivate_plugins( $this->get_path() );
		update_option( 'recently_activated', array( $this->get_path() => time() ) + (array) get_option( 'recently_activated' ) );

		return true;

	}

	/**
	 * Called when the user chooses to uninstall the Add-On  - after permissions have been checked and before removing
	 * all Add-On settings and Form settings.
	 *
	 * Override this method to perform additional functions such as dropping database tables.
	 *
	 *
	 * Return false to cancel the uninstall request.
	 */
	public function uninstall() {
		return true;
	}

	//--------------  Enforce minimum GF version  ---------------------------------------------------

	/**
	 * Target for the after_plugin_row action hook. Checks whether the current version of Gravity Forms
	 * is supported and outputs a message just below the plugin info on the plugins page.
	 *
	 * Not intended to be overridden or called directly by Add-Ons.
	 *
	 * @since Unknown
	 * @since 2.4.15  Update to improve multisite updates.
	 *
	 * @param string $plugin_name The plugin filename.  Immediately overwritten.
	 * @param array  $plugin_data An array of plugin data.
	 */
	public function plugin_row( $plugin_name, $plugin_data ) {
		if ( false === $this->_enable_rg_autoupgrade && ! self::is_gravityforms_supported( $this->_min_gravityforms_version ) ) {
			$message = $this->plugin_message();
			self::display_plugin_message( $message, true );
		}

		if ( self::is_gravityforms_supported( $this->_min_gravityforms_version ) && ! self::is_gravityforms_compatible() ) {
			$message = $this->compatibility_message();
			self::display_plugin_message( $message, true );
		}

		if ( ! $this->_enable_rg_autoupgrade ) {
			return;
		}

		GFForms::maybe_display_update_notification( $plugin_name, $plugin_data, $this->get_slug(), $this->_version );
	}

	/**
	 * Returns the message that will be displayed if the current version of Gravity Forms is not supported.
	 *
	 * Override this method to display a custom message.
	 */
	public function plugin_message() {
		$message = sprintf( esc_html__( 'Gravity Forms %s is required. Activate it now or %spurchase it today!%s', 'gravityforms' ), $this->_min_gravityforms_version, "<a href='https://www.gravityforms.com'>", '</a>' );

		return $message;
	}

	/**
	 * Returns the message that will be displayed if the current version of Gravity Forms is not compatible with the add-on.
	 *
	 * Override this method to display a custom message.
	 *
	 * @since 2.7.12
	 */
	public function compatibility_message() {
		$message = esc_html__( 'Some features of the add-on are not available on the current version of Gravity Forms. Please update to the latest Gravity Forms version for full compatibility.', 'gravityforms' );

		return $message;
	}

	/**
	 * Formats and outs a message for the plugin row.
	 *
	 * Not intended to be overridden or called directly by Add-Ons.
	 *
	 * @ignore
	 *
	 * @param      $message
	 * @param bool $is_error
	 */
	public static function display_plugin_message( $message, $is_error = false ) {
		$style = $is_error ? 'style="background-color: #ffebe8;"' : '';
		echo '</tr><tr class="plugin-update-tr"><td colspan="5" class="plugin-update"><div class="update-message" ' . $style . '>' . $message . '</div></td>';
	}

	//--------------- Logging -------------------------------------------------------------

	/**
	 * Writes an error message to the Gravity Forms log. Requires the Gravity Forms logging Add-On.
	 *
	 * Not intended to be overridden by Add-Ons.
	 *
	 * @ignore
	 */
	public function log_error( $message ) {
		if ( class_exists( 'GFLogging' ) ) {
			GFLogging::include_logger();
			GFLogging::log_message( $this->get_slug(), $message, KLogger::ERROR );
		}
	}

	/**
	 * Writes an error message to the Gravity Forms log. Requires the Gravity Forms logging Add-On.
	 *
	 * Not intended to be overridden by Add-Ons.
	 *
	 * @ignore
	 */
	public function log_debug( $message ) {
		if ( class_exists( 'GFLogging' ) ) {
			GFLogging::include_logger();
			GFLogging::log_message( $this->get_slug(), $message, KLogger::DEBUG );
		}
	}

	//--------------- Locking ------------------------------------------------------------

	/**
	 * Returns the configuration for locking
	 *
	 * e.g.
	 *
	 *  array(
	 *     "object_type" => 'contact',
	 *     "capabilities" => array("gravityforms_contacts_edit_contacts"),
	 *     "redirect_url" => admin_url("admin.php?page=gf_contacts"),
	 *     "edit_url" => admin_url(sprintf("admin.php?page=gf_contacts&id=%d", $contact_id)),
	 *     "strings" => $strings
	 *     );
	 *
	 * Override this method to implement locking
	 */
	public function get_locking_config() {
		return array();
	}


	/**
	 * Returns TRUE if the current page is the edit page. Otherwise, returns FALSE
	 *
	 * Override this method to implement locking on the edit page.
	 */
	public function is_locking_edit_page() {
		return false;
	}

	/**
	 * Returns TRUE if the current page is the list page. Otherwise, returns FALSE
	 *
	 * Override this method to display locking info on the list page.
	 */
	public function is_locking_list_page() {
		return false;
	}

	/**
	 * Returns TRUE if the current page is the view page. Otherwise, returns FALSE
	 *
	 * Override this method to display locking info on the view page.
	 */
	public function is_locking_view_page() {
		return false;
	}

	/**
	 * Returns the ID of the object to be locked. E.g. Form ID
	 *
	 * Override this method to implement locking
	 */
	public function get_locking_object_id() {
		return 0;
	}

	/**
	 * Outputs information about the user currently editing the specified object
	 *
	 * @param int  $object_id The Object ID
	 * @param bool $echo      Whether to echo
	 *
	 * @return string The markup for the lock info
	 */
	public function lock_info( $object_id, $echo = true ) {
		$gf_locking = new GFAddonLocking( $this->get_locking_config(), $this );
		$lock_info  = $gf_locking->lock_info( $object_id, false );
		if ( $echo ) {
			echo $lock_info;
		}

		return $lock_info;
	}

	/**
	 * Outputs class for the row for the specified Object ID on the list page.
	 *
	 * @param int  $object_id The object ID
	 * @param bool $echo      Whether to echo
	 *
	 * @return string The markup for the class
	 */
	public function list_row_class( $object_id, $echo = true ) {
		$gf_locking = new GFAddonLocking( $this->get_locking_config(), $this );
		$class      = $gf_locking->list_row_class( $object_id, false );
		if ( $echo ) {
			echo $class;
		}

		return $class;
	}

	/**
	 * Checked whether an object is locked
	 *
	 * @param int|mixed $object_id The object ID
	 *
	 * @return bool
	 */
	public function is_object_locked( $object_id ) {
		$gf_locking = new GFAddonLocking( $this->get_locking_config(), $this );

		return $gf_locking->is_locked( $object_id );
	}

	//------------- Field Value Retrieval -------------------------------------------------

	/**
	 * Returns the value of the mapped field.
	 *
	 * @param string $setting_name
	 * @param array $form
	 * @param array $entry
	 * @param mixed $settings
	 *
	 * @return string
	 */
	public function get_mapped_field_value( $setting_name, $form, $entry, $settings = false ) {

		$field_id = $this->get_setting( $setting_name, '', $settings );

		return $this->get_field_value( $form, $entry, $field_id );
	}

	/**
	 * Returns the value of the selected field.
	 *
	 * @access private
	 *
	 * @param array $form
	 * @param array $entry
	 * @param string $field_id
	 *
	 * @return string field value
	 */
	public function get_field_value( $form, $entry, $field_id ) {

		$field_value = '';

		switch ( strtolower( $field_id ) ) {

			case 'form_title':
				$field_value = rgar( $form, 'title' );
				break;

			case 'date_created':
				$date_created = rgar( $entry, strtolower( $field_id ) );
				if ( empty( $date_created ) ) {
					//the date created may not yet be populated if this function is called during the validation phase and the entry is not yet created
					$field_value = gmdate( 'Y-m-d H:i:s' );
				} else {
					$field_value = $date_created;
				}
				break;

			case 'ip':
			case 'source_url':
			case 'id':
				$field_value = rgar( $entry, strtolower( $field_id ) );
				break;

			default:
				$field = GFFormsModel::get_field( $form, $field_id );

				if ( is_object( $field ) ) {
					$is_integer = $field_id == intval( $field_id );
					$input_type = $field->get_input_type();

					if ( $is_integer && $input_type == 'address' ) {

						$field_value = $this->get_full_address( $entry, $field_id );

					} elseif ( $is_integer && $input_type == 'name' ) {

						$field_value = $this->get_full_name( $entry, $field_id );

					} elseif ( is_callable( array( $this, "get_{$input_type}_field_value" ) ) ) {

						$field_value = call_user_func( array( $this, "get_{$input_type}_field_value" ), $entry, $field_id, $field );

					} else {

						$field_value = $field->get_value_export( $entry, $field_id );

					}
				} else {

					$field_value = rgar( $entry, $field_id );

				}

		}

		/**
		 * A generic filter allowing the field value to be overridden. Form and field id modifiers supported.
		 *
		 * @param string $field_value The value to be overridden.
		 * @param array $form The Form currently being processed.
		 * @param array $entry The Entry currently being processed.
		 * @param string $field_id The ID of the Field currently being processed.
		 * @param string $slug The add-on slug e.g. gravityformsactivecampaign.
		 *
		 * @since 1.9.15.12
		 *
		 * @return string
		 */
		$field_value = gf_apply_filters( array( 'gform_addon_field_value', $form['id'], $field_id ), $field_value, $form, $entry, $field_id, $this->get_slug() );

		return $this->maybe_override_field_value( $field_value, $form, $entry, $field_id );
	}

	/**
	 * Enables use of the gform_SLUG_field_value filter to override the field value. Override this function to prevent the filter being used or to implement a custom filter.
	 *
	 * @param string $field_value
	 * @param array $form
	 * @param array $entry
	 * @param string $field_id
	 *
	 * @return string
	 */
	public function maybe_override_field_value( $field_value, $form, $entry, $field_id ) {
		/* Get Add-On slug */
		$slug = str_replace( 'gravityforms', '', $this->get_slug() );

		return gf_apply_filters( array(
			"gform_{$slug}_field_value",
			$form['id'],
			$field_id
		), $field_value, $form, $entry, $field_id );
	}

	/**
	 * Returns the combined value of the specified Address field.
	 *
	 * @param array $entry
	 * @param string $field_id
	 *
	 * @return string
	 */
	public function get_full_address( $entry, $field_id ) {

		return GF_Fields::get( 'address' )->get_value_export( $entry, $field_id );
	}

	/**
	 * Returns the combined value of the specified Name field.
	 *
	 * @param array $entry
	 * @param string $field_id
	 *
	 * @return string
	 */
	public function get_full_name( $entry, $field_id ) {

		return GF_Fields::get( 'name' )->get_value_export( $entry, $field_id );
	}

	/**
	 * Returns the value of the specified List field.
	 *
	 * @param array $entry
	 * @param string $field_id
	 * @param GF_Field_List $field
	 *
	 * @return string
	 */
	public function get_list_field_value( $entry, $field_id, $field ) {

		return $field->get_value_export( $entry, $field_id );
	}

	/**
	 * Returns the field ID of the first field of the desired type.
	 *
	 * @access public
	 * @param string $field_type
	 * @param int $subfield_id (default: null)
	 * @param int $form_id (default: null)
	 * @return string
	 */
	public function get_first_field_by_type( $field_type, $subfield_id = null, $form_id = null, $return_first_only = true ) {

		/* Get the current form ID. */
		if ( rgblank( $form_id ) ) {

			$form_id = rgget( 'id' );

		}

		/* Get the form. */
		$form = GFAPI::get_form( $form_id );

		/* Get the request field type for the form. */
		$fields = GFAPI::get_fields_by_type( $form, array( $field_type ) );

		if ( count( $fields ) == 0 || ( count( $fields ) > 1 && $return_first_only ) ) {

			return null;

		} else {

			if ( rgblank( $subfield_id ) ) {

				return $fields[0]->id;

			} else {

				return $fields[0]->id . '.' . $subfield_id;

			}

		}

	}

	//--------------- Notes ------------------
	/**
	 * Override this function to specify a custom avatar (i.e. the payment gateway logo) for entry notes created by the Add-On
	 * @return  string - A fully qualified URL for the avatar
	 */
	public function note_avatar() {
		return false;
	}

	public function notes_avatar( $avatar, $note ) {
		if ( $note->user_name == $this->_short_title && empty( $note->user_id ) && $this->method_is_overridden( 'note_avatar', 'GFAddOn' ) ) {
			$new_avatar = $this->note_avatar();
		}

		return empty( $new_avatar ) ? $avatar : "<img alt='{$this->_short_title}' src='{$new_avatar}' class='avatar avatar-48' height='48' width='48' />";
	}

	/**
	 * Adds a note to an entry.
	 *
	 * @since 1.9.12
	 *
	 * @param $entry_id
	 * @param $note
	 * @param null $sub_type
	 *
	 * @return int ID of the new note.
	 */
	public function add_note( $entry_id, $note, $sub_type = null ) {
		$user_id   = 0;
		$user_name = $this->_short_title;
		$note_type = $this->get_slug();

		return GFFormsModel::add_note( $entry_id, $user_id, $user_name, $note, $note_type, $sub_type );
	}

	//--------------  Helper functions  ---------------------------------------------------

	/**
	 * Determine if method is overridden in extended class.
	 *
	 * @since Unknown
	 * @since 2.5 Added exception handling.
	 *
	 * @param string $method_name
	 * @param string $base_class
	 *
	 * @return bool
	 */
	protected final function method_is_overridden( $method_name, $base_class = 'GFAddOn' ) {
		try {
			$reflector = new ReflectionMethod( $this, $method_name );
			$name      = $reflector->getDeclaringClass()->getName();

			return $name !== $base_class;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Returns the url of the root folder of the current Add-On.
	 *
	 * @param string $full_path Optional. The full path the the plugin file.
	 *
	 * @return string
	 */
	public function get_base_url( $full_path = '' ) {
		if ( empty( $full_path ) ) {
			$full_path = $this->_full_path;
		}

		return plugins_url( '', $full_path );
	}

	/**
	 * Returns the url of the Add-On Framework root folder.
	 *
	 * @return string
	 */
	final public static function get_gfaddon_base_url() {
		return plugins_url( '', __FILE__ );
	}

	/**
	 * Returns the physical path of the Add-On Framework root folder.
	 *
	 * @return string
	 */
	final public static function get_gfaddon_base_path() {
		return self::_get_base_path();
	}

	/**
	 * Returns the physical path of the plugins root folder.
	 *
	 * @param string $full_path
	 *
	 * @return string
	 */
	public function get_base_path( $full_path = '' ) {
		if ( empty( $full_path ) ) {
			$full_path = $this->_full_path;
		}
		$folder = basename( dirname( $full_path ) );

		return WP_PLUGIN_DIR . '/' . $folder;
	}

	/**
	 * Returns the physical path of the Add-On Framework root folder
	 *
	 * @return string
	 */
	private static function _get_base_path() {
		$folder = basename( dirname( __FILE__ ) );

		return GFCommon::get_base_path() . '/includes/' . $folder;
	}

	/**
	 * Returns the URL of the Add-On Framework root folder
	 *
	 * @return string
	 */
	private static function _get_base_url() {
		$folder = basename( dirname( __FILE__ ) );

		return GFCommon::get_base_url() . '/includes/' . $folder;
	}

	/**
	 * Checks whether the Gravity Forms is installed.
	 *
	 * @return bool
	 */
	public function is_gravityforms_installed() {
		return class_exists( 'GFForms' );
	}

	public function table_exists( $table_name ) {

		return GFCommon::table_exists( $table_name );

	}

	/**
	 * Checks whether the current version of Gravity Forms is supported
	 *
	 * @param $min_gravityforms_version
	 *
	 * @return bool|mixed
	 */
	public function is_gravityforms_supported( $min_gravityforms_version = '' ) {
		if ( isset( $this->_min_gravityforms_version ) && empty( $min_gravityforms_version ) ) {
			$min_gravityforms_version = $this->_min_gravityforms_version;
		}

		if ( empty( $min_gravityforms_version ) ) {
			return true;
		}

		return version_compare( GFForms::$version, $min_gravityforms_version, '>=' );
	}

	/**
	 * Checks whether the current version of Gravity Forms is compatible with all features of an add-on.
	 *
	 * @since 2.7.12
	 *
	 * @param string $min_compatible_gravityforms_version The version to compare the current version with.
	 *
	 * @return bool|mixed
	 */
	public function is_gravityforms_compatible( $min_compatible_gravityforms_version = '' ) {
		if ( isset( $this->_min_gravityforms_version ) && empty( $min_compatible_gravityforms_version ) ) {
			$min_compatible_gravityforms_version = $this->_min_compatible_gravityforms_version;
		}

		if ( empty( $min_compatible_gravityforms_version ) ) {
			return true;
		}

		static $results = array();

		if ( ! isset( $results[ $min_compatible_gravityforms_version ] ) ) {
			$results[ $min_compatible_gravityforms_version ] = version_compare( GFForms::$version, $min_compatible_gravityforms_version, '>=' );
		}

		return $results[ $min_compatible_gravityforms_version ];
	}

	/**
	 * Display an upgrade notice if the current version of Gravity Forms is not fully supported.
	 *
	 * @since 2.7.12
	 */
	public function maybe_display_upgrade_notice() {
		if ( $this->is_gravityforms_compatible() ) {
			return;
		}

		$message = sprintf(
			/* translators: 1: Add-on title */
			esc_html__(
				'Some features of the %1$s Add-on are not available on this version of Gravity Forms. Please update to the latest version for full compatibility.',
				'gravityforms'
			),
			$this->get_short_title()
		);
		?>

		<div class="gf-notice notice notice-error">
			<p><?php echo wp_kses( $message, array( 'a' => array( 'href' => true ) ) ); ?></p>
		</div>
		<?php
	}


	/**
	 * Returns this plugin's short title. Used to display the plugin title in small areas such as tabs
	 */
	public function get_short_title() {
		return isset( $this->_short_title ) ? $this->_short_title : $this->_title;
	}

	/**
	 * Return the plugin's icon for the plugin/form settings menu.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_menu_icon() {
		return 'gform-icon--cog';
	}

	/**
	 * Return the plugin's icon namespace.
	 * For implementation of a custom font icon kit.
	 * Used by GFCommon::get_icon_markup() and assumes your font icon kit
	 * is setup in a similar fashion to Gravity Forms (`class="gform-icon gform-icon--icon-name"`).
	 * The namespace declared here should not include the `-icon`.
	 *
	 * @return string|null
	 * @since 2.6
	 */
	public function get_icon_namespace() {
		return null;
	}

	/**
	 * Return this plugin's version.
	 *
	 * @since  2.0
	 * @access public
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->_version;
	}

	/**
	 * Returns the unescaped URL for the plugin settings tab associated with this plugin
	 *
	 */
	public function get_plugin_settings_url() {
		return add_query_arg( array( 'page' => 'gf_settings', 'subview' => $this->get_slug() ), admin_url( 'admin.php' ) );
	}

	/**
	 * Returns the current form object based on the id query var. Otherwise returns false
	 *
	 * @return array|null|false If ID is found and is valid form, then the populated Form array is returned.
	 */
	public function get_current_form() {
		return rgempty( 'id', $_GET ) ? false : GFFormsModel::get_form_meta( rgget( 'id' ) );
	}

	/**
	 * Returns TRUE if the current request is a postback, otherwise returns FALSE
	 *
	 */
	public function is_postback() {
		return is_array( $_POST ) && count( $_POST ) > 0;
	}

	/**
	 * Returns TRUE if the settings "Save" button was pressed
	 */
	public function is_save_postback() {
		return ! rgempty( 'gform-settings-save' );
	}

	/**
	 * Returns TRUE if the current page is the form editor page. Otherwise, returns FALSE
	 */
	public function is_form_editor() {
		/**
		* @var Gravity_Forms\Gravity_Forms\Save_Form\GF_Save_Form_Helper $save_form_helper
		*/
		$save_form_helper = GFForms::get_service_container()->get( GF_Save_Form_Service_Provider::GF_SAVE_FROM_HELPER );
		if (
				GFForms::get_page_query_arg() == 'gf_edit_forms' && ! rgempty( 'id', $_GET ) && rgempty( 'view', $_GET )
				|| $save_form_helper->is_ajax_save_action()
		) {
			return true;
		}

		return false;
	}

	/**
	 * Returns TRUE if the current page is the form list page. Otherwise, returns FALSE
	 */
	public function is_form_list() {

		if ( GFForms::get_page_query_arg() == 'gf_edit_forms' && rgempty( 'id', $_GET ) && rgempty( 'view', $_GET ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Returns TRUE if the current page is the form settings page, or a specific form settings tab (specified by the $tab parameter). Otherwise returns FALSE
	 *
	 * @param string $tab - Specifies a specific form setting page/tab
	 *
	 * @return bool
	 */
	public function is_form_settings( $tab = null ) {

		$is_form_settings = GFForms::get_page_query_arg() == 'gf_edit_forms' && rgget( 'view' ) == 'settings';
		$is_tab           = $this->_tab_matches( $tab );

		if ( $is_form_settings && $is_tab ) {
			return true;
		} else {
			return false;
		}
	}

	private function _tab_matches( $tabs ) {
		if ( $tabs == null ) {
			return true;
		}

		if ( ! is_array( $tabs ) ) {
			$tabs = array( $tabs );
		}

		$current_tab = rgempty( 'subview', $_GET ) ? 'settings' : rgget( 'subview' );

		foreach ( $tabs as $tab ) {
			if ( strtolower( $tab ) == strtolower( $current_tab ) ) {
				return true;
			}
		}
	}

	/**
	 * Returns TRUE if the current page is the plugin settings main page, or a specific plugin settings tab (specified by the $tab parameter). Otherwise returns FALSE
	 *
	 * @param string $tab - Specifies a specific plugin setting page/tab.
	 *
	 * @return bool
	 */
	public function is_plugin_settings( $tab = '' ) {

		$is_plugin_settings = GFForms::get_page_query_arg() == 'gf_settings';
		$is_tab             = $this->_tab_matches( $tab );

		if ( $is_plugin_settings && $is_tab ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Returns TRUE if the current page is the app settings main page, or a specific apps settings tab (specified by the $tab parameter). Otherwise returns FALSE
	 *
	 * @param string $tab - Specifies a specific app setting page/tab.
	 *
	 * @return bool
	 */
	public function is_app_settings( $tab = '' ) {

		$is_app_settings = GFForms::get_page_query_arg() == $this->get_slug() . '_settings';
		$is_tab          = $this->_tab_matches( $tab );

		if ( $is_app_settings && $is_tab ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Returns TRUE if the current page is the plugin page. Otherwise returns FALSE
	 * @return bool
	 */
	public function is_plugin_page() {

		return GFForms::get_page_query_arg() == strtolower( $this->get_slug() );
	}

	/**
	 * Returns TRUE if the current page is the entry view page. Otherwise, returns FALSE
	 * @return bool
	 */
	public function is_entry_view() {
		if ( GFForms::get_page_query_arg() == 'gf_entries' && rgget( 'view' ) == 'entry' && ( ! isset( $_POST['screen_mode'] ) || rgpost( 'screen_mode' ) == 'view' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Returns TRUE if the current page is the entry edit page. Otherwise, returns FALSE
	 * @return bool
	 */
	public function is_entry_edit() {
		if ( GFForms::get_page_query_arg() == 'gf_entries' && rgget( 'view' ) == 'entry' && rgpost( 'screen_mode' ) == 'edit' ) {
			return true;
		}

		return false;
	}

	public function is_entry_list() {
		if ( GFForms::get_page_query_arg() == 'gf_entries' && ( rgget( 'view' ) == 'entries' || rgempty( 'view', $_GET ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Returns TRUE if the current page is the results page. Otherwise, returns FALSE
	 */
	public function is_results() {
		if ( GFForms::get_page_query_arg() == 'gf_entries' && rgget( 'view' ) == 'gf_results_' . $this->get_slug() ) {
			return true;
		}

		return false;
	}

	/**
	 * Returns TRUE if the current page is the print page. Otherwise, returns FALSE
	 */
	public function is_print() {
		if ( rgget( 'gf_page' ) == 'print-entry' ) {
			return true;
		}

		return false;
	}

	/**
	 * Returns TRUE if the current page is the preview page. Otherwise, returns FALSE
	 */
	public function is_preview() {
		if ( rgget( 'gf_page' ) == 'preview' ) {
			return true;
		}

		return false;
	}

	/**
	 * Determines if the current page is the block editor.
	 *
	 * @since 2.7
	 *
	 * @return bool Returns true if this is the block editor page. Otherwise, returns false.
	 */
	public function is_block_editor() {
		return GFCommon::is_block_editor_page();
	}

	public function has_deprecated_elements() {
		$deprecated = GFAddOn::get_all_deprecated_protected_methods( get_class( $this ) );
		if ( ! empty( $deprecated ) ) {
			return true;
		}

		return false;
	}

	public static function get_all_deprecated_protected_methods($add_on_class_name = ''){
		$deprecated = array();
		$deprecated = array_merge( $deprecated, self::get_deprecated_protected_methods_for_base_class( 'GFAddOn', $add_on_class_name )) ;
		$deprecated = array_merge( $deprecated, self::get_deprecated_protected_methods_for_base_class( 'GFFeedAddOn', $add_on_class_name ) ) ;
		$deprecated = array_merge( $deprecated, self::get_deprecated_protected_methods_for_base_class( 'GFPaymentAddOn', $add_on_class_name ) ) ;
		return $deprecated;
	}

	public static function get_deprecated_protected_methods_for_base_class( $base_class_name, $add_on_class_name = '' ) {
		$deprecated = array();

		if ( ! class_exists( $base_class_name ) ) {
			return $deprecated;
		}

		$base_class_names = array(
			'GFAddOn',
			'GFFeedAddOn',
			'GFPaymentAddOn'
		);

		$base_class = new ReflectionClass( $base_class_name );

		$classes = empty($add_on_class_name) ? get_declared_classes() : array( $add_on_class_name );

		foreach ( $classes as $class ) {
			if ( ! is_subclass_of( $class, $base_class_name ) || in_array( $class, $base_class_names ) ) {
				continue;
			}

			$add_on_class   = new ReflectionClass( $class );
			$add_on_methods = $add_on_class->getMethods( ReflectionMethod::IS_PROTECTED );
			foreach ( $add_on_methods as $method ) {
				$method_name               = $method->getName();
				$base_has_method           = $base_class->hasMethod( $method_name );
				$is_declared_by_base_class = $base_has_method && $base_class->getMethod( $method_name )->getDeclaringClass()->getName() == $base_class_name;
				$is_overridden             = $method->getDeclaringClass()->getName() == $class;
				if ( $is_declared_by_base_class && $is_overridden ) {
					$deprecated[] = $class . '::' . $method_name;
				}
			}
		}
		return $deprecated;
	}

	public function maybe_wp_kses( $html, $allowed_html = 'post', $allowed_protocols = array() ) {
		return GFCommon::maybe_wp_kses( $html, $allowed_html, $allowed_protocols );
	}

	/**
	 * Returns the slug for the add-on.
	 *
	 * @since 2.0
	 */
	public function get_slug() {
		if ( empty( $this->_slug ) ) {
			$this->_slug = plugin_basename( dirname( (string) $this->_full_path ) );
		}
		return $this->_slug;
	}

	/**
	 * Returns the add-on slug with the gravityforms prefix removed.
	 *
	 * @since 2.4.18
	 *
	 * @return string
	 */
	public function get_short_slug() {
		return str_replace( 'gravityforms', '', $this->get_slug() );
	}

	/**
	 * Returns the path for the add-on.
	 *
	 * @since 2.2
	 */
	public function get_path() {
		return $this->_path;
	}

	/**
	 * Fixes the add-on _path property value, if the directory has been renamed.
	 *
	 * @since 2.4.17
	 */
	public function update_path() {
		if ( ! $this->_path || ! $this->_full_path ) {
			return;
		}

		$path_dirname = dirname( $this->_path );
		if ( $path_dirname !== '.' ) {
			$full_path_dirname = basename( dirname( $this->_full_path ) );
			if ( $path_dirname !== $full_path_dirname ) {
				$this->_path = trailingslashit( $full_path_dirname ) . basename( $this->_path );
			}
		}
	}

	/**
	 * Get all or a specific capability for Add-On.
	 *
	 * @since  2.2.5.27
	 * @access public
	 *
	 * @param string $capability Capability to return.
	 *
	 * @return string|array
	 */
	public function get_capabilities( $capability = '' ) {

		if ( rgblank( $capability ) ) {
			return $this->_capabilities;
		}

		return isset( $this->{'_capabilities_' . $capability} ) ? $this->{'_capabilities_' . $capability} : array();

	}

	/**
	 * Initializing translations.
	 *
	 * @since 2.0.7
	 */
	public function load_text_domain() {
		GFCommon::load_gf_text_domain( $this->get_slug(), plugin_basename( dirname( $this->_full_path ) ) );
	}

	/**
	 * Inits the TranslationsPress integration for official add-ons.
	 *
	 * @since 2.5.6
	 */
	public function init_translations() {
		if ( ! $this->_enable_rg_autoupgrade ) {
			return;
		}

		TranslationsPress_Updater::get_instance( $this->get_slug() );
	}

	/**
	 * Uses TranslationsPress to install translations for the specified locale.
	 *
	 * @since 2.5.6
	 *
	 * @param string $locale The locale the translations are to be installed for.
	 */
	public function install_translations( $locale = '' ) {
		if ( ! $this->_enable_rg_autoupgrade ) {
			return;
		}

		TranslationsPress_Updater::download_package( $this->get_slug(), $locale );
	}

	/**
	 * Returns an array of locales from the mo files found in the WP_LANG_DIR/plugins directory.
	 *
	 * Used to display the installed locales on the system report.
	 *
	 * @since 2.5.6
	 *
	 * @return array
	 */
	public function get_installed_locales() {
		if ( ! $this->_enable_rg_autoupgrade ) {
			return array();
		}

		return GFCommon::get_installed_translations( $this->get_slug() );
	}

	/**
	 * Determines if the current user has the proper capabilities to uninstall this add-on
	 * Add-ons that have been network activated can only be uninstalled by a network admin.
	 *
	 * @since 2.3.1.12
	 * @access public
	 *
	 * @return bool True if current user can uninstall this add-on. False otherwise
	 */
	public function current_user_can_uninstall(){

		return GFCommon::current_user_can_uninstall( $this->_capabilities_uninstall, $this->get_path() );

	}

	/**
	 * Displays all installed addons with their uninstall buttons.
	 *
	 * Add-ons which override this method will display a button with a link instead. The add-on's overridden output
	 * will be displayed on the settings page for that add-on.
	 *
	 * @see GFAddOn::uninstall_addon()
	 *
	 * @since  2.5
	 *
	 * @param array $uninstallable_addons Array of GFAddOn objects.
	 */
	public static function addons_for_uninstall( $uninstallable_addons ) {
		?>
		<div class="gform-addons-uninstall-panel">
			<?php
			/* @var GFAddOn $addon An add-on instance. */
			foreach ( $uninstallable_addons as $addon ) {
				ob_start();
				$addon->render_uninstall();
				$panel_markup = ob_get_clean();

				if ( $addon->method_is_overridden( 'render_uninstall' ) && ! empty( $panel_markup ) ) {
					$addon->render_settings_button();
					continue;
				}

				echo $panel_markup; // @codingStandardsIgnoreLine - markup prepared in render_install.
			}
			?>
		</div>
		<?php
	}
}
