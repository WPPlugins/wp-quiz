<?php
/*
 * Plugin Name: WP Quiz
 * Plugin URI:  https://mythemeshop.com/plugins/wp-quiz/
 * Description: WP Quiz makes it incredibly easy to add professional, multimedia quizzes to any website! Fully feature rich and optimized for social sharing. Make your site more interactive!
 * Version:     1.0.11
 * Author:      MyThemeShop
 * Author URI:  https://mythemeshop.com/
 *
 * Text Domain: wp-quiz
 * Domain Path: /languages/
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'WP_Quiz_Plugin' ) ) :


	/**
	 * Register the plugin.
	 *
	 * Display the administration panel, insert JavaScript etc.
	 */
	class WP_Quiz_Plugin {

		/**
		 * Hold plugin version
	     * @var string
	     */
	    public $version = '1.0.11';

		/**
		 * Hold an instance of WP_Quiz_Plugin class.
		 *
		 * @var WP_Quiz_Plugin
		 */
		protected static $instance = null;

		/**
		 * @var WP Quiz
		 */
		public $quiz = null;

		/**
		 * Main WP_Quiz_Plugin instance.
		 * @return WP_Quiz_Plugin - Main instance.
		 */
		public static function get_instance() {

			if ( is_null( self::$instance ) ) {
				self::$instance = new WP_Quiz_Plugin;
			}

			return self::$instance;
		}

		/**
		 * You cannot clone this class.
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wp-quiz' ), $this->version );
		}

		/**
		 * You cannot unserialize instances of this class.
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wp-quiz' ), $this->version );
		}

		/**
	     * Constructor
	     */
	    public function __construct() {

			$this->define_constants();
			$this->includes();
			$this->hooks();
			$this->setup_shortcode();
		}

		/**
		 * Define WP Quiz constants
		 */
		private function define_constants() {

			define( 'WP_QUIZ_VERSION',    $this->version );
			define( 'WP_QUIZ_BASE_URL',   trailingslashit( plugins_url( 'wp-quiz' ) ) );
			define( 'WP_QUIZ_ASSETS_URL', trailingslashit( WP_QUIZ_BASE_URL . 'assets' ) );
			define( 'WP_QUIZ_PATH',       plugin_dir_path( __FILE__ ) );
		}

		/**
	     * Load required classes
		 */
	    private function includes() {

			//auto loader
			spl_autoload_register( array( $this, 'autoloader' ) );

			new WP_Quiz_Admin;
		}

		/**
	     * Autoload classes
	     */
	    public function autoloader( $class ) {

	        $dir = WP_QUIZ_PATH . 'inc' . DIRECTORY_SEPARATOR;
			$class_file_name = 'class-' . str_replace( array( 'wp_quiz_', '_' ), array( '', '-' ), strtolower( $class ) ) . '.php';

			if ( file_exists( $dir . $class_file_name ) ) {
				require $dir . $class_file_name;
			}
		}

		/**
	     * Register the [wp_quiz] shortcode.
	     */
		private function setup_shortcode() {
			add_shortcode( 'wp_quiz', array( $this, 'register_shortcode' ) );
		}

		/**
		 * Hook WP Quiz into WordPress
		 */
		private function hooks() {

			// Common
			add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
			add_action( 'init', array( $this, 'register_post_type' ) );

			// Frontend
			add_action( 'wp_head', array( $this, 'inline_script' ), 1 );
			add_filter( 'the_content', array( $this, 'create_quiz_page' ) );

			// Ajax
			add_action( 'wp_ajax_check_image_file', array( $this, 'check_image_file' ) );
			add_action( 'wp_ajax_check_video_file', array( $this, 'check_video_file' ) );

			add_action( 'wp_ajax_wpquiz_get_debug_log', array( $this, 'wp_quiz_get_debug_log' ) );

			// FB SDK version 2.9 fix
			if ( isset( $_GET['fbs'] ) && ! empty( $_GET['fbs'] ) ) {
				add_action( 'template_redirect', array( $this, 'fb_share_fix' ) );
			}
		}

		/**
	     * Initialise translations
	     */
		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'wp-quiz', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		/**
	     * Register Quiz post type
		 */
		public static function register_post_type() {

			$labels = array(
				'name'               => __( 'WP Quiz', 'wp-quiz' ),
				'menu_name'          => __( 'WP Quiz', 'wp-quiz' ),
				'singular_name'      => __( 'WP Quiz', 'wp-quiz' ),
				'name_admin_bar'     => _x( 'WP Quiz', 'name admin bar', 'wp-quiz' ),
				'all_items'          => __( 'All Quizzes', 'wp-quiz' ),
				'search_items'       => __( 'Search Quizzes', 'wp-quiz' ),
				'add_new'            => _x( 'Add New', 'quiz', 'wp-quiz' ),
				'add_new_item'       => __( 'Add New WP Quiz', 'wp-quiz' ),
				'new_item'           => __( 'New Quiz', 'wp-quiz' ),
				'view_item'          => __( 'View Quiz', 'wp-quiz' ),
				'edit_item'          => __( 'Edit Quiz', 'wp-quiz' ),
				'not_found'          => __( 'No Quizzes Found.', 'wp-quiz' ),
				'not_found_in_trash' => __( 'WP Quiz not found in Trash.', 'wp-quiz' ),
				'parent_item_colon'  => __( 'Parent Quiz', 'wp-quiz' ),
			);

			$args = array(
				'labels'             => $labels,
				'description'        => __( 'Holds the quizzes and their data.', 'wp-quiz' ),
				'menu_position'      => 5,
				'menu_icon'			 => 'dashicons-editor-help',
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'query_var'          => true,
				'capability_type'    => 'post',
				'has_archive'        => true,
				'hierarchical'       => false,
				'supports'           => array( 'title', 'author', 'thumbnail', 'excerpt' ),
			);

			register_post_type( 'wp_quiz', $args );
		}

		/**
	     * Shortcode used to display quiz
	     *
	     * @return string HTML output of the shortcode
	     */
		public function register_shortcode( $atts ) {

			if ( ! isset( $atts['id'] ) ) {
				return false;
			}

			// we have an ID to work with
			$quiz = get_post( $atts['id'] );

			// check if ID is correct
			if ( ! $quiz || 'wp_quiz' !== $quiz->post_type ) {
				return "<!-- wp_quiz {$atts['id']} not found -->";
			}

			// lets go
			$this->set_quiz( $atts['id'] );
			$this->quiz->enqueue_scripts();

			return $this->quiz->render_public_quiz();
		}

		/**
	     * Set the current quiz
	     */
		public function set_quiz( $id ) {

			$quiz_type = get_post_meta( $id, 'quiz_type', true );
			$quiz_type = str_replace( '_quiz', '', $quiz_type );
			$quiz_type = 'WP_Quiz_' . ucwords( $quiz_type ) . '_Quiz';
			$this->quiz = new $quiz_type( $id );
		}

		/**
		 * [create_quiz_page description]
		 * @param  [type] $content [description]
		 * @return [type]          [description]
		 */
		public function create_quiz_page( $content ) {

			global $post;

			if ( 'wp_quiz' !== $post->post_type ) {
				return $content;
			}

			if ( ! is_single() ) {
				return $content;
			}

			$quiz_html = $this->register_shortcode( array( 'id' => $post->ID ) );

			return $quiz_html . $content;
		}

		public function check_image_file() {

			$output = array( 'status' => 1 );
			$check = false;
			if ( @getimagesize( $_POST['url'] ) ) {
				$check = true;
			}

			$output['check'] = $check;
			wp_send_json( $output );
		}

		public function check_video_file() {

			$output = array( 'status' => 1 );
			$check = false;
			$id = $_POST['video_id'];
			$url = "//www.youtube.com/oembed?url=http://www.youtube.com/watch?v=$id&format=json";
			$headers = get_headers( $url );
			if ( '404' !== substr( $headers[0], 9, 3 ) ) {
				$check = true;
			}

			$output['check'] = $check;
			wp_send_json( $output );
		}

		public function wp_quiz_get_debug_log() {
			$page = new WP_Quiz_Page_Support();
			$page->get_debug_log();
		}

		public static function activate_plugin() {

			// Don't activate on anything less than PHP 5.4.0 or WordPress 3.4
			if ( version_compare( PHP_VERSION, '5.4.0', '<' ) || version_compare( get_bloginfo( 'version' ), '3.4', '<' ) || ! function_exists( 'spl_autoload_register' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
				deactivate_plugins( basename( __FILE__ ) );
				wp_die( __( 'WP Quiz requires PHP version 5.4.0 with spl extension or greater and WordPress 3.4 or greater.', 'wp-quiz' ) );
			}

			// Dont't activate if wp quiz pro is active
			if ( defined( 'WP_QUIZ_PRO_VERSION' ) ) {
				deactivate_plugins( basename( __FILE__ ) );
				wp_die( __( 'Please deactivate WP Quiz Pro plugin', 'wp-quiz' ) );
			}

			include( 'inc/activate-plugin.php' );
		}

		public function fb_share_fix() {

			$data = array_map( 'urldecode', $_GET );
			$result = get_post_meta( $data['id'], 'results', true );
			$result = isset( $result[ $data['rid'] ] ) ? $result[ $data['rid'] ] : array();

			// Picture
			if ( 'r' === $data['pic'] ) {
				$data['source'] = $result['image'];
			} elseif ( 'f' === $data['pic'] ) {
				$data['source'] = wp_get_attachment_url( get_post_thumbnail_id( $data['id'] ) );
			} else {
				$data['source'] = false;
			}

			// Description
			if ( 'r' === $data['desc'] ) {
				$data['description'] = $result['desc'];
			} elseif ( 'e' === $data['desc'] ) {
				$data['description'] = get_post_field( 'post_excerpt', $data['id'] );
			} else {
				$data['description'] = false;
			}

			$settings = get_option( 'wp_quiz_default_settings' );
			$url = ( is_ssl() ? 'https' : 'http' ) . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
			$original_url = get_permalink( $data['id'] );
			?>
			<html>
				<head>
					<title><?php wp_title( '' ) ?></title>
					<meta property="fb:app_id" content="<?php echo $settings['defaults']['fb_app_id'] ?>"/>
					<meta property="og:type" content="website" />
					<meta property="og:url" content="<?php echo esc_url( $url ); ?>" />
					<?php if ( ! empty( $data['text'] ) ) : ?>
					<meta property="og:title" content="<?php echo esc_attr( $data['text'] ); ?>" />
					<?php endif; ?>
					<?php if ( ! empty( $data['source'] ) ) : ?>
					<meta property="og:image" content="<?php echo esc_url( $data['source'] ); ?>" />
						<?php list( $img_width, $img_height ) = getimagesize( $data['source'] ); ?>
						<?php if ( isset( $img_width ) && $img_width ) : ?>
						<meta property="og:image:width" content="<?php echo $img_width ?>" />
						<?php endif; ?>
						<?php if ( isset( $img_height ) && $img_height ) : ?>
						<meta property="og:image:height" content="<?php echo $img_height ?>" />
						<?php endif; ?>
					<?php endif; ?>
					<?php if ( ! empty( $data['description'] ) ) : ?>
					<meta property="og:description" content="<?php echo esc_attr( $data['description'] ); ?>" />
					<?php endif; ?>
					<meta http-equiv="refresh" content="0;url=<?php echo esc_url( $original_url ); ?>" />
				</head>
			<body>
				<?php the_content(); ?>
			</body>
			</html>
			<?php
			exit;
		}

		public function inline_script() {
			$settings = get_option( 'wp_quiz_default_settings' );
			?>
				<script>
				<?php if ( ! empty( $settings['defaults']['fb_app_id'] ) ) { ?>
					window.fbAsyncInit = function() {
						FB.init({
							appId    : '<?php echo $settings['defaults']['fb_app_id'] ?>',
							xfbml    : true,
							version  : 'v2.9'
						});
					};

					(function(d, s, id){
						var js, fjs = d.getElementsByTagName(s)[0];
						if (d.getElementById(id)) {return;}
						js = d.createElement(s); js.id = id;
						js.src = "//connect.facebook.net/en_US/sdk.js";
						fjs.parentNode.insertBefore(js, fjs);
					}(document, 'script', 'facebook-jssdk'));
				<?php } ?>
				</script>
			<?php
			if ( is_singular( array( 'wp_quiz' ) ) && isset( $settings['defaults']['share_meta'] ) && 1 === $settings['defaults']['share_meta'] ) {
				global $post, $wpseo_og;
				$twitter_desc = $og_desc = str_replace( array( "\r", "\n" ), '', strip_tags( $post->post_excerpt ) );
				if ( defined( 'WPSEO_VERSION' ) ) {
					remove_action( 'wpseo_head', array( $wpseo_og, 'opengraph' ), 30 );
					remove_action( 'wpseo_head', array( 'WPSEO_Twitter', 'get_instance' ), 40 );

					// Use description from yoast
					$twitter_desc 	= get_post_meta( $post->ID, '_yoast_wpseo_twitter-description', true );
					$og_desc 		= get_post_meta( $post->ID, '_yoast_wpseo_opengraph-description', true );
				}
				?>
				<meta name="twitter:title" content="<?php echo get_the_title(); ?>">
				<meta name="twitter:description" content="<?php echo $twitter_desc; ?>">
				<meta name="twitter:domain" content="<?php echo esc_url( site_url() ); ?>">
				<meta property="og:url" content="<?php the_permalink(); ?>" />
				<meta property="og:title" content="<?php echo get_the_title(); ?>" />
				<meta property="og:description" content="<?php echo $og_desc; ?>" />
				<?php
				if ( has_post_thumbnail() ) {
					$thumb_id = get_post_thumbnail_id();
					$thumb_url_array = wp_get_attachment_image_src( $thumb_id, 'full', true );
					$thumb_url = $thumb_url_array[0];
					?>
					<meta name="twitter:card" content="summary_large_image">
					<meta name="twitter:image:src" content="<?php echo $thumb_url; ?>">
					<meta property="og:image" content="<?php echo $thumb_url; ?>" />
					<meta itemprop="image" content="<?php echo $thumb_url; ?>">
				<?php
				}
			}
		}
	}

	/**
	 * Main instance of WP_Quiz_Plugin.
	 *
	 * Returns the main instance of WP_Quiz_Plugin to prevent the need to use globals.
	 *
	 * @return WP_Quiz_Plugin
	 */

	function wp_quiz() {
		return WP_Quiz_Plugin::get_instance();
	}

endif;

add_action( 'plugins_loaded', 'wp_quiz', 10 );
register_activation_hook( __FILE__, array( 'WP_Quiz_Plugin', 'activate_plugin' ) );
