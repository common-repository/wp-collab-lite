<?php
/**
 * WP Collab Lite
 *
 * @package   WP_Collab_Lite_Admin
 * @author    Circlewaves Team <support@circlewaves.com>
 * @license   GPL-2.0+
 * @link      http://circlewaves.com
 * @copyright 2014 Circlewaves Team <support@circlewaves.com>
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * @package   WP_Collab_Lite_Admin
 * @author    Circlewaves Team <support@circlewaves.com>
 */
class WP_Collab_Lite_Admin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		/*
		 * @TODO :
		 *
		 * - Uncomment following lines if the admin class should only be available for super admins
		 */
		/* if( ! is_super_admin() ) {
			return;
		} */

		/*
		 * Call $plugin_slug from public plugin class.
		 */
		$plugin = WP_Collab_Lite::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
		
		add_action( 'admin_init', array( $this, 'admin_options_init' ) );	

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

		

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		/*
		 * @TODO :
		 *
		 * - Uncomment following lines if the admin class should only be available for super admins
		 */
		/* if( ! is_super_admin() ) {
			return;
		} */

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {
/* 
		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		} */

		$screen = get_current_screen();
		//if ( ($screen->id==$this->plugin_screen_hook_suffix)||($screen->post_type==WP_Collab_Lite::MAIN_TAXONOMY) ) {
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), WP_Collab_Lite::VERSION );
	//	}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ($screen->id==$this->plugin_screen_hook_suffix)||($screen->post_type==WP_Collab_Lite::MAIN_TAXONOMY) ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), WP_Collab_Lite::VERSION );
		}

	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		/*
		 * Add a settings page for this plugin to the Settings menu.
		 */
/* 		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'WP Collab Options', $this->plugin_slug ),
			__( 'WP Collab', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);
		 */
		 
		/*
		 * Add a settings page for this plugin as WP Collab Taxonomy sub-page
		 */		 
		$this->plugin_screen_hook_suffix = add_submenu_page(
			'edit.php?post_type='.WP_Collab_Lite::MAIN_TAXONOMY, 
			__( 'WP Collab Settings', $this->plugin_slug ),
			__( 'Settings', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		include_once( 'views/admin.php' );
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'edit.php?post_type='.WP_Collab_Lite::MAIN_TAXONOMY.'&page='.$this->plugin_slug ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			),
			$links
		);

	}
	
	/**
	 * Init plugin options
	 *
	 * @since    1.0.0
	 */	
	public function admin_options_init() {

		// Sections
		add_settings_section( 'main-section', 'Main Settings', array( $this, 'main_section_callback' ), 'wp-collab' );

		// Handle plugin options
		foreach(WP_Collab_Lite::$pluginSettings as $setting){
			// Register Settings
			register_setting( 'wpcollab_settings', $setting['name'] );
			
			// Fields
			add_settings_field( $setting['name'], $setting['title'], array( $this, 'setting_field_callback' ), 'wp-collab', $setting['section'], array('name'=>$setting['name']) );
		}

	 
	}	

	/**
	 * Main section callback
	 *
	 * @since    1.0.0
	 */	
	public function main_section_callback() {
		// some actions here;
	}


 	/**
	 * Generate setting field
	 *
	 * @since    1.0.0
	 */	
	public function setting_field_callback($args) {
		$setting_value = esc_attr( get_option( $args['name'] ) );
		echo '<input class="regular-text" type="text" name="'.$args['name'].'" value="'.$setting_value.'" />';
	}
		



}
