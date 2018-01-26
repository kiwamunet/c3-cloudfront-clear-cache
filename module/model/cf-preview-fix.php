<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( class_exists( 'CF_preview_fix' ) ) {
	return;
}
add_action( 'init', function(){
	$cf_fix = CF_preview_fix::get_instance();
	$cf_fix->add_hook();
});

/**
 * Fixture for post preview
 *
 * @class C3_Auth
 * @since 4.0.0
 */
class CF_preview_fix{
	private static $instance;

	private function __construct() {}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			$c = __CLASS__;
			self::$instance = new $c();
		}
		return self::$instance;
	}

	public function add_hook() {
		add_action( 'template_redirect', array( $this, 'template_redirect' ) );
		add_filter( 'post_link', array( $this, 'post_link_fix' ), 10, 3 );
		add_filter( 'preview_post_link', array( $this, 'preview_post_link_fix' ), 10, 2 );
		add_filter( 'the_guid', array( $this,'the_guid' ) );
		add_filter( 'sanitize_file_name', array( $this,'sanitizeFileName' ) );
		add_action( 'plugins_loaded', array( $this,'set_loginuser_cookie_for_preview' ) );
		add_action( 'wp_logout', array( $this,'unset_loginuser_cookie_for_preview' ) );
	}

	/**
	 * Set cookie to avoid CloudFront cache if user sign in
	 *
	 * @since 5.1.0
	 * @access public
	 */
	public function set_loginuser_cookie_for_preview() {
		if ( is_user_logged_in() ) {
				setcookie( 'wordpress_loginuser_last_visit', time() );
		}
	}

	/**
	 * Unet cookie for avoid CloudFront cache when user sign out
	 *
	 * @since 5.1.0
	 * @access public
	 */
	public function unset_loginuser_cookie_for_preview() {
		setcookie('wordpress_loginuser_last_visit', '', time() - 1800);
	}

	public function template_redirect() {
		if ( is_user_logged_in() ) {
			nocache_headers();
		}
	}

	public function post_link_fix( $permalink, $post, $leavename ) {
		if ( !is_user_logged_in() || !is_admin() || is_feed() ) {
			return $permalink;
		}
		$post = get_post( $post );
		$post_time =
			isset( $post->post_modified )
			? date( 'YmdHis', strtotime( $post->post_modified ) )
			: current_time( 'YmdHis' );
		$permalink = add_query_arg( 'post_date', $post_time, $permalink );
		return $permalink;
	}

	public function preview_post_link_fix( $permalink, $post ) {
		if ( is_feed() ) {
			return $permalink;
		}
		$post = get_post( $post );
		$preview_time = current_time( 'YmdHis' );
		$permalink = add_query_arg( 'preview_time', $preview_time, $permalink );
		return $permalink;
	}

	public function the_guid( $guid ) {
		$guid = preg_replace( '#\?post_date=[\d]+#', '', $guid );
		return $guid;
	}

	public function sanitizeFileName( $filename ) {
		$info = pathinfo( $filename );
		$ext	= empty( $info['extension'] ) ? '' : '.' . $info['extension'];
		$name = basename( $filename, $ext );
		$finalFileName = $name. '-'. current_time( 'YmdHis' );

		return $finalFileName.$ext;
	}
}
