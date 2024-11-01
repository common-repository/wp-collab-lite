<?php
/**
 * WP Collab Lite
 *
 * @package   WP_Collab_Lite
 * @author    Circlewaves Team <support@circlewaves.com>
 * @license   GPL-2.0+
 * @link      http://circlewaves.com
 * @copyright 2014 Circlewaves Team <support@circlewaves.com>
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * If you're interested in introducing administrative or dashboard
 * functionality, then refer to `class-wp-collab-admin.php`
 *
 *
 * @package   WP_Collab_Lite
 * @author    Circlewaves Team <support@circlewaves.com>
 */
class WP_Collab_Lite {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '1.0.0';

	/**
	 *
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'wp-collab-lite';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	
	/**
	 * WP Collab Taxonomy
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const MAIN_TAXONOMY = 'wpcollab_project';	
	
	/**
	 * Plugin Settings, used on Plugin Settings page
	 *
	 * @since    1.0.0
	 *
	 * @var      array
	 */
	public static $pluginSettings=array(
		array(
			'name'=>'wpcollab-projects-permalink',
			'title'=>'Projects Permalink',
			'section'=>'main-section'
		),
		array(
			'name'=>'wpcollab-project-uid-prefix',
			'title'=>'Project UID Prefix',
			'section'=>'main-section'
		)		
	);		
	
	
	
	/**
	 * Plugin Settings Values
	 *
	 * @since    1.0.0
	 *
	 * @var      array
	 */
	public static $pluginDefaultSettings=array(
		'plugin-version'=>array(
			'name'=>'wpcollab-plugin-version',
			'value'=>'1.0.0'
		),				
		'projects-permalink'=>array(
			'name'=>'wpcollab-projects-permalink',
			'value'=>'projects'
		),
		'project-uid-prefix'=>array(
			'name'=>'wpcollab-project-uid-prefix',
			'value'=>'1000'
		)		
	);			
	
	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		/* .
		 *	Actions and filters for 'project' taxonomy and taxonomy metabox
		 */
		add_action( 'init', array( $this, 'create_collab_post_type' ) );
		add_action( 'save_post', array($this,'wpcollab_project_save_post') );
		add_filter('is_protected_meta', array($this, 'wpcollab_protected_meta_filter'), 10, 2);
		add_filter( 'enter_title_here', array( $this, 'backend_change_default_title') );
		// Change Admin Columns for WPCollab project post type
		add_filter( 'manage_edit-wpcollab_project_columns', array( $this, 'wpcollab_project_edit_columns') );
		add_action( 'manage_wpcollab_project_posts_custom_column', array( $this, 'wpcollab_project_columns'), 10, 2 );		
		// Make Admin Columns sortable
		add_filter( 'manage_edit-wpcollab_project_sortable_columns', array( $this, 'wpcollab_project_sortable_columns') );
		add_action( 'pre_get_posts', array( $this, 'wpcollab_columns_orderby') );  
		// Add Filter for WPCollab post type
		add_action( 'restrict_manage_posts', array( $this, 'wpcollab_project_admin_filter') );
		add_filter( 'parse_query', array( $this, 'wpcollab_project_admin_filter_handle_query') );
		// Use custom template for WPCollab project post type
		add_action('template_redirect', array($this,'wpcollab_project_theme_redirect'));

		// Remove "Protected" from post title		
		add_filter('protected_title_format', array($this,'wpcollab_project_protected_title'));
		
		//Create custom password form for WPCollab projects
		add_filter('the_password_form', array($this,'wpcollab_project_password_form'));  		
		
		//Handle form on WPCollab archive projects
		add_action('init', array($this,'wpcollab_project_archive_handle_form'));
	}
	

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.0
	 *
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	private static function single_activate() {	
	
			//Add plugin options (it does nothing if option already exists)
			foreach(self::$pluginDefaultSettings as $k=>$v){
				add_option( self::$pluginDefaultSettings[$k]['name'], self::$pluginDefaultSettings[$k]['value'] );	
			}		
			
			//Always update plugin version
			update_option( self::$pluginDefaultSettings['plugin-version']['name'], self::$pluginDefaultSettings['plugin-version']['value'] );

			flush_rewrite_rules();

	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {
		 flush_rewrite_rules();
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'assets/css/public.css', __FILE__ ), array(), self::VERSION );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'assets/js/public.js', __FILE__ ), array( 'jquery' ), self::VERSION );
	}


	/**
	 * Create collab post type
	 *
	 * @since    1.0.0
	 */
	public function create_collab_post_type() {
	
		$permalink_option_name=self::$pluginDefaultSettings['projects-permalink']['name'];
		$permalink_option_default=self::$pluginDefaultSettings['projects-permalink']['value'];

			$labels = array(
					'name' => 'Projects',
					'singular_name' => 'Project',
					'add_new' => 'Add New',
					'add_new_item' => 'Add New Project',
					'edit_item' => 'Edit Project',
					'new_item' => 'New Project',
					'view_item' => 'View Project',
					'search_items' => 'Search Projects',
					'not_found' =>  'No Projects found',
					'not_found_in_trash' => 'No Projects in the trash',
					'parent_item_colon' => '',
			);
	 
			register_post_type( self::MAIN_TAXONOMY, array(
					'labels' => $labels,
					'public' => true,
					'publicly_queryable' => true,
					'show_ui' => true,
					'exclude_from_search' => true,
					'query_var' => true,
					'rewrite' => array('slug' =>get_option( $permalink_option_name, $permalink_option_default),'with_front' => false),
					'capability_type' => 'post',
					'has_archive' => true,
					'hierarchical' => false,
					'menu_position' => 25,
					'menu_icon' => 'dashicons-portfolio',
					'supports' => array( 'title','editor','thumbnail','comments','custom-fields'),
		      'register_meta_box_cb' => array($this, 'projects_taxonomy_metabox') // Callback function for custom metaboxes
					) 
			);
			
		// refresh rewrite rules to solve 404 error (use soft flush)
			flush_rewrite_rules(false);
	}
	
	
	/**
	 * Add metabox to Projects
	 *
	 * @since    1.0.0
	 */
	public function projects_taxonomy_metabox() {
			add_meta_box( 'wp_collab_project_metabox', __('Project Details','wp-collab'), array($this,'projects_metabox_form'), self::MAIN_TAXONOMY, 'normal', 'high' );
	}	

	
	/**
	 * Render metabox
	 *
	 * @since    1.0.0
	 */
	function projects_metabox_form() {
    $post_id = get_the_ID();
		$the_post = get_post($post_id );
		$current_user = wp_get_current_user();
		
    $project_status = get_post_meta( $post_id, 'wpcollab_project_status', true );
		$project_status = isset( $project_status ) ? esc_attr( $project_status ) : '';  
		
		$project_manager = get_post_meta( $post_id, 'wpcollab_project_manager', true );
		$project_manager = isset( $project_manager ) ? esc_attr( $project_manager ) : '';  
		//project extra data
    $project_extra = get_post_meta( $post_id, 'wpcollab_project_extra', true );
		//project notes
		$project_notes = isset( $project_extra['project_notes'] ) ? esc_attr( $project_extra['project_notes'] ) : '';  
		//client
		$client_name = ( isset( $project_extra['client_name'] ) ) ? $project_extra['client_name'] : '';
		$client_email = ( isset( $project_extra['client_email'] ) ) ? $project_extra['client_email'] : '';
		$client_details = ( isset( $project_extra['client_details'] ) ) ? $project_extra['client_details'] : '';

 
    wp_nonce_field( 'wpcollab_project_save', 'wpcollab_project_nonce' );
    ?>
		<fieldset>
		<legend><?php _e('Help','wp-collab');?></legend>
			<p><?php _e('Shortcode <strong>[wpcollab_admin_only]</strong> is available only in the FULL version.','wp-collab');?></strong> <a href="http://codecanyon.net/item/wp-collab/7055492?ref=circlewaves" target="_blank">Learn More &rarr;</a></p>
			<p><?php _e('You can use shortcode','wp-collab');?> <strong>[wpcollab_admin_only]</strong>Content visible only for website administrators and authors<strong>[/wpcollab_admin_only]</strong></p>
		</fieldset>

			<?php if($the_post->post_password){?>
		<fieldset>
		<legend><?php _e('Access details','wp-collab');?></legend>			
			<p><?php _e('Project UID','wp-collab');?>:  <strong><?php echo $the_post->post_name;	?></strong></p>
			<p><?php _e('Project Password','wp-collab');?>:  <strong><?php echo $the_post->post_password;	?></strong></p>
			<p><strong><?php _e('Access URL is available only in the FULL version.','wp-collab');?></strong> <a href="http://codecanyon.net/item/wp-collab/7055492?ref=circlewaves" target="_blank">Learn More &rarr;</a></p>
		</fieldset>
		<fieldset>
		<legend><?php _e('Sample message','wp-collab');?></legend>		
			<p>
				<?php _e('Hello','wp-collab');?>,
				<br /><br />
				<?php _e('Access details for the project','wp-collab');?> <strong><?php echo $the_post->post_title;	?></strong>	<br />
				<?php _e('Project UID','wp-collab');?>: <strong><?php echo $the_post->post_name;	?></strong> <br />
				<?php _e('Password','wp-collab');?>: 	<strong><?php echo $the_post->post_password;	?></strong>
				<br /><br />
				<?php _e('Kind Regards','wp-collab');?>, <br />
				<?php echo $current_user->display_name;	?>  <br />
				<?php echo home_url('/');	?>		
			<p>				
		</fieldset>			
			<?php }?>
		<fieldset>
		<legend><?php _e('Project Info','wp-collab');?></legend>
    <p>
        <label for="wpcollab_project_manager"><?php _e('Project Manager','wp-collab');?></label><br />			
				<?php wp_dropdown_users(array('name' => 'wpcollab_project_manager','id'=>'wpcollab_project_manager','selected'=>$project_manager,'who'=>'authors')); ?>
		</p>		
    <p>  
        <label for="wpcollab_project_status"><?php _e('Project Status','wp-collab');?></label><br />
        <select name="wpcollab_project_status" id="wpcollab_project_status">  
            <option value="Active" <?php selected( $project_status, 'Active' ); ?>><?php _e('Active','wp-collab');?></option>  
            <option value="Not Active" <?php selected( $project_status, 'Not Active' ); ?>><?php _e('Not Active','wp-collab');?></option>  
        </select>  
    </p> 
		</fieldset>
		<fieldset>
		<legend><?php _e('Project Notes','wp-collab');?></legend>
    <p>
        <label for="wpcollab_project_notes"><?php _e('Admin Notes (optional)','wp-collab');?></label><br />
        <textarea id="wpcollab_project_notes" name="wpcollab_project_extra[project_notes]" placeholder="<?php _e('Your notes for your own needs','wp-collab');?>"><?php echo $project_notes; ?></textarea>
    </p>
		</fieldset>		
		<fieldset>
		<legend><?php _e('Client','wp-collab');?></legend>		
    <p>
        <label for="wpcollab_client_name">* <?php _e('Client Name','wp-collab');?></label><br />
        <input id="wpcollab_client_name" type="text" value="<?php echo $client_name; ?>" name="wpcollab_project_extra[client_name]" size="40" placeholder="<?php _e('John Freeman','wp-collab');?>" />
    </p>
    <p>
        <label for="wpcollab_client_email">* <?php _e('Client Email','wp-collab');?></label><br />
        <input id="wpcollab_client_email" type="text" value="<?php echo $client_email; ?>" name="wpcollab_project_extra[client_email]" size="40" placeholder="<?php _e('john.freeman@example.com','wp-collab');?>"  />
    </p>
    <p>
        <label for="wpcollab_client_details"><?php _e('Client Details (optional, visible only to admin)','wp-collab');?></label><br />
        <textarea id="wpcollab_client_details" name="wpcollab_project_extra[client_details]" placeholder="<?php _e('Client details','wp-collab');?>"><?php echo $client_details; ?></textarea>
    </p>		
		</fieldset>
    <?php
	}	
	
 
	/**
	 * Save project metabox data
	 *
	 * @since    1.0.0
	 */
	function wpcollab_project_save_post( $post_id ) {
				
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        return;
 
    if ( !isset($_POST['wpcollab_project_nonce']) || !wp_verify_nonce( $_POST['wpcollab_project_nonce'], 'wpcollab_project_save' ) )
        return;
 
    if ( ! empty( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
        if ( ! current_user_can( 'edit_page', $post_id ) )
            return;
    } else {
        if ( ! current_user_can( 'edit_post', $post_id ) )
            return;
    }
 
		// Use trimmed hash of Project Title as Post Password
    if ( ! wp_is_post_revision( $post_id ) && get_post_type( $post_id )==self::MAIN_TAXONOMY ) {
        remove_action( 'save_post', array($this, 'wpcollab_project_save_post') );
 
				// Generate password for project
				$project_password=$_POST['post_password']?$_POST['post_password']:substr(sha1(rand().$post_id), 0, 10);
				
				// Default project uid (permalink) - is [PREFIX]+[post_id], if prefix 1000 and post id is 213, permalink will be 100213
				$permalink_prefix=get_option( self::$pluginDefaultSettings['project-uid-prefix']['name']);
				$permalink=$permalink_prefix.$post_id;

        wp_update_post( array(
            'ID' => $post_id,
            'post_password' =>$project_password,
						'post_name' =>$permalink,
						'comment_status' => $_POST['comment_status'] //'open'						
        ) );
							
 
        add_action( 'save_post', array($this,'wpcollab_project_save_post') );
    }
		
		if ( !empty( $_POST['wpcollab_project_status'] ) ) {
			$project_status=(isset($_POST['wpcollab_project_status']))?esc_attr($_POST['wpcollab_project_status']):'';
			update_post_meta( $post_id, 'wpcollab_project_status', $project_status );
		} else {
			delete_post_meta( $post_id, 'wpcollab_project_status' );
		}
		
		if ( !empty( $_POST['wpcollab_project_manager'] ) ) {
			$project_manager=(isset($_POST['wpcollab_project_manager']))?esc_attr($_POST['wpcollab_project_manager']):'';
			update_post_meta( $post_id, 'wpcollab_project_manager', $project_manager );
		} else {
			delete_post_meta( $post_id, 'wpcollab_project_manager' );
		}		
 
    if ( ! empty( $_POST['wpcollab_project_extra'] ) ) {
		
			//project info 
			$project_extra['project_status'] = (isset($_POST['wpcollab_project_extra']['project_status']))?esc_attr($_POST['wpcollab_project_extra']['project_status']):'';
			//project notes
			$project_extra['project_notes'] = (isset($_POST['wpcollab_project_extra']['project_notes']))?esc_attr($_POST['wpcollab_project_extra']['project_notes']):'';
			//client
			$project_extra['client_name'] = (isset($_POST['wpcollab_project_extra']['client_name']))?esc_attr($_POST['wpcollab_project_extra']['client_name']):'';
			$project_extra['client_email'] = (isset($_POST['wpcollab_project_extra']['client_email']))?esc_attr($_POST['wpcollab_project_extra']['client_email']):'';
			$project_extra['client_details'] = (isset($_POST['wpcollab_project_extra']['client_details']))?esc_attr($_POST['wpcollab_project_extra']['client_details']):'';
				
				
			update_post_meta( $post_id, 'wpcollab_project_extra', $project_extra );
    } else {
      delete_post_meta( $post_id, 'wpcollab_project_extra' );
    }
	}	
	

	/**
	 * Protect Project Meta Fields (hide project status and project manager from 'Custom Fields' section)
	 *
	 * @since    1.0.0
	 */
	function wpcollab_protected_meta_filter($protected, $meta_key) {
		if(($meta_key=='wpcollab_project_status') || ($meta_key=='wpcollab_project_manager') || ($meta_key=='wpcollab_project_extra')){
			return true;
		}
		return $protected;
	}	



	/**
	 * Change Post Title placeholder
	 *
	 * @since    1.0.0
	 */
	public function backend_change_default_title( $title ){
			$screen = get_current_screen();
			if ( $screen->post_type==self::MAIN_TAXONOMY ){
					$title = 'Project Title';
			}
			return $title;
	}	
	

	/**
	 * Customize wpcollab project list view (column titles)
	 *
	 * @since    1.0.0
	 */
	function wpcollab_project_edit_columns( $columns ) {
			$columns = array(
					'cb' => '<input type="checkbox" />',
					'title' => 'Title',
					'project-status' => 'Status',
					'project-client' => 'Client',
					'project-manager' => 'Manager',	
					'project-access' => 'Access Details',	
					'date' => 'Date'
			);
	 
			return $columns;
	}	
	
	/**
	 * Customize wpcollab project list view (table content)
	 *
	 * @since    1.0.0
	 */
	function wpcollab_project_columns( $column, $post_id ) {
			$project_status = get_post_meta( $post_id, 'wpcollab_project_status', true );
			$project_manager = get_post_meta( $post_id, 'wpcollab_project_manager', true );
			$project_extra = get_post_meta( $post_id, 'wpcollab_project_extra', true );
			$the_post = get_post($post_id );
			switch ( $column ) {		
					case 'project-status':
							if ( ! empty( $project_status ) ){
								//if project status == not active - wrap it in red color, else - use green color
								switch($project_status){
									case 'Active':
										$project_status_formatted='<strong class="wp-collab-project-status active">'.__($project_status,'wp-collab').'</strong>';
									break;
									case 'Not Active':
										$project_status_formatted='<strong class="wp-collab-project-status not-active">'.__($project_status,'wp-collab').'</strong>';
									break;
									default:
										$project_status_formatted='<strong class="wp-collab-project-status default">'.__($project_status,'wp-collab').'</strong>';
									
								}
								echo $project_status_formatted;
							}					
					break;						
					case 'project-client':
							if ( ! empty( $project_extra['client_name'] ) ){
								echo $project_extra['client_name'];
							}
							if ( ! empty( $project_extra['client_email'] ) ){
								echo '<br />'.'<small><a href="mailto:'.$project_extra['client_email'].'">'.$project_extra['client_email'].'</a></small>';
							}						
					break;
					case 'project-manager':
							if ( ! empty( $project_manager ) ){
								$user_info = get_userdata($project_manager);
								echo $user_info->display_name;
								if ( ! empty( $user_info->user_email ) ){
									echo '<br />'.'<small><a href="mailto:'.$user_info->user_email.'">'.$user_info->user_email.'</a></small>';
								}	
							}
					break;			
					case 'project-access':
					?>
				<?php _e('Project UID','wp-collab');?>: <strong><?php echo $the_post->post_name;	?></strong><br />
				<?php _e('Password','wp-collab');?>: 	<strong><?php echo $the_post->post_password;	?></strong>
				<?php
					break;						
			}
	}
	
	
	
	/**
	 * Define sortable columns
	 *
	 * @since    1.0.0
	 */
	function wpcollab_project_sortable_columns( $columns ) {
		$columns = array(
				'title' => 'title',
				'project-status' => 'project-status',
				'date' => 'date'
		);
		return $columns;
	}
	
	/**
	 * Handle sortable columns
	 *
	 * @since    1.0.0
	 */
 	function wpcollab_columns_orderby( $query ) {  
		if(!is_admin()){
			return;  
		}

		$orderby = $query->get('orderby'); 

		if($orderby=='project-status'){  
				$query->set('meta_key','wpcollab_project_status');  
				$query->set('orderby','meta_value');  
		}  
	}  	
	

	/**
	 * Add filter to WPCollab project list
	 *
	 * @since    1.0.0
	 */
	function wpcollab_project_admin_filter(){
		$type='post';
		if (isset($_GET['post_type'])) {
				$type = $_GET['post_type'];
		}

		//only add filter to WPCollab Taxonomy
		if ($type==self::MAIN_TAXONOMY){
				$selected_pm = isset($_GET['ADMIN_FILTER_FIELD_VALUE'])? $_GET['ADMIN_FILTER_FIELD_VALUE']:'';
				wp_dropdown_users(array('name' => 'ADMIN_FILTER_FIELD_VALUE','id'=>'wpcollab_filterby_project_manager','selected'=>$selected_pm,'who'=>'authors','show_option_none'=>__('Project Manager','wp-collab'))); 
		}
	}



	/**
	 * Handle WPCollab project list filter
	 *
	 * @since    1.0.0
	 */
function wpcollab_project_admin_filter_handle_query( $query ){
    global $pagenow;
		$filter_value=(isset($_GET['ADMIN_FILTER_FIELD_VALUE']))?$_GET['ADMIN_FILTER_FIELD_VALUE']:'';
		$filter_value=($filter_value==-1)?'':$filter_value;
    $type = 'post';
    if (isset($_GET['post_type'])) {
        $type = $_GET['post_type'];
    }
    if ( $type==self::MAIN_TAXONOMY && is_admin() && $pagenow=='edit.php' && isset($filter_value) && $filter_value != '') {
        $query->query_vars['meta_key'] = 'wpcollab_project_manager';
        $query->query_vars['meta_value'] = $filter_value;
    }
}	


	/**
	 * Use plugin-own template for wpcollab project custom post types 
	 * Template fallback
	 *
	 * @since    1.0.0
	 */

	function wpcollab_project_theme_redirect() {
		global $wp;
		$plugindir = plugin_dir_path( dirname( __FILE__ ));
		$templatedir = get_stylesheet_directory();
		

		if (isset($wp->query_vars['post_type'])){
			if($wp->query_vars['post_type'] == self::MAIN_TAXONOMY) {
		
				// define type of template - single or archive		
				if(is_single()){
					$templatefilename = 'single-'.self::MAIN_TAXONOMY.'.php';
				}else{
					$templatefilename = 'archive-'.self::MAIN_TAXONOMY.'.php';
				}
				
				// find user defined templates in current theme, else - use templates from plugin directory
				if(file_exists($templatedir . '/' . $templatefilename)) {
					$return_template = $templatedir . '/' . $templatefilename;
				}else{
					$return_template = $plugindir . '/templates/' . $templatefilename;
				}
				
				$this->wpcollab_project_do_theme_redirect($return_template);
			}
		}

	}

	/**
	 * Include correct template
	 *
	 * @since    1.0.0
	 */	
	public function wpcollab_project_do_theme_redirect($url) {
		global $post, $wp_query;
		if(have_posts()) {
			include($url);
			die();
		}else{
			$wp_query->is_404 = true;
		}
	}
		
	/**
	 * Remove "Protected" from post title
	 *
	 * @since    1.0.0
	 */	
	function wpcollab_project_protected_title($content) {
				 return '%s';
	}
	

	/**
	 * Create custom password form for WPCollab projects
	 *
	 * @since    1.0.0
	 */	
	function wpcollab_project_password_form($content) {  
	global $post; 
		if($post->post_type==self::MAIN_TAXONOMY){
			$label = 'wpcollab-'.self::MAIN_TAXONOMY.'-login-'.( empty( $post->ID ) ? rand() : $post->post_type );  
			$o = '
			<div class="wpcollab-'.self::MAIN_TAXONOMY.'-login-form">
			<form class="protected-post-form" action="' . esc_url( site_url( 'wp-login.php?action=postpass', 'login_post' ) ) . '" method="post"> 
			<div class="wpcollab-'.self::MAIN_TAXONOMY.'-login-form-title">' . __( "Please enter your password to see this project",'wp-collab' ) . '</div>
			<div class="wpcollab-'.self::MAIN_TAXONOMY.'-login-form-fields"><label for="' . $label . '">' . __( "Password:",'wp-collab' ) . ' </label><input name="post_password" id="' . $label . '" type="password" /> <input type="submit" name="Submit" value="' . esc_attr__( "Submit" ) . '" /></div>
			</form>
			</div>
			';  
			return $o;  
		}
	//return default form for all other posts types		
	return $content;

	}



	/**
	 * Handle login form on WPCollab archive projects
	 *
	 * @since    1.0.0
	 */	
	public function wpcollab_project_archive_handle_form(){

		if ( !empty( $_POST['wpcollab_nonce'] ) && wp_verify_nonce( $_POST['wpcollab_nonce'], 'send-wpcollab-uid' ) ){
			$formdata=array(
				'project_uid'=>esc_attr( $_POST['project_uid'] ),
				'project_password'=>esc_attr( $_POST['project_password'] )
			);
			
			if( (!$formdata['project_uid']) || (!$formdata['project_password']) ){
				$_POST['WPCollabFormError']='Please enter project UID and password';
				$_POST['WPCollabFormHasError']=true;	
			}	
			
			if(!isset($_POST['WPCollabFormHasError']) || $_POST['WPCollabFormHasError']!=true ){	
				$project_page = get_page_by_path($formdata['project_uid'],ARRAY_A,self::MAIN_TAXONOMY);
				if($project_page && ($formdata['project_password']==$project_page['post_password'])){
					$next_step_page_url=get_permalink($project_page['ID']);
					$next_step_page_url=add_query_arg(array('access_token' => $formdata['project_password']),$next_step_page_url);
					wp_safe_redirect($next_step_page_url);
					exit;	
				}else{
					$_POST['WPCollabFormError']='Incorrect project UID or password';
					$_POST['WPCollabFormHasError']=true;		
				}	
			}	
		} 
	}	
	


}


