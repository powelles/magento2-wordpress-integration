<?php
/**
 *
 * DO NOT EDIT THIS FILE
 * Any changes you make to this file will be lost
 * To customise things, create a file at wp-content/themes/fishpig/local.php
 * This file will not be deleted or overwritten and is automatically included at the end of this file
 *
 */

if (!function_exists('fishpig_setup')) {
	function fishpig_setup() {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		set_post_thumbnail_size(9999, 9999);

		add_theme_support( 'post-formats', array(
			'aside', 'image', 'video', 'quote', 'link', 'gallery', 'status', 'audio', 'chat'
		));
		
		show_admin_bar(false);
	}
}

add_action( 'after_setup_theme', 'fishpig_setup' );

function fishpig_comment_redirect($location)
{
	if (strpos($location, '#') !== false) {
		if (preg_match('/^(.*)(\#comment-([0-9]{1,}))$/', $location, $match)) {
			$commentId = (int)$match[3];
			$hash      = $match[2];
			$query     = array('comment-id' => $commentId);
			
			if ($comment = get_comment($commentId)) {
				$query['comment-status'] = (int)$comment->comment_approved;
			}

			$location = $match[1] . '?' . http_build_query($query) . $match[2];
		}
	}

	return $location;
}

add_filter( 'comment_post_redirect', 'fishpig_comment_redirect' );

function fishpig_widgets_init() {
	register_sidebar(array(
		'name' => __( 'Main Sidebar', 'fishpig' ),
		'id' => 'sidebar-main',
		'description' => 'Add widgets here to appear in your main Magento sidebar.',
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget' => '</aside>',
		'before_title' => '<h2 class="widget-title">',
		'after_title' => '</h2>',
	));
}

add_action( 'widgets_init', 'fishpig_widgets_init' );

/* Remove the Emoji JS */
remove_action( 'wp_head', 'print_emoji_detection_script', 7 ); 
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' ); 
remove_action( 'wp_print_styles', 'print_emoji_styles' ); 
remove_action( 'admin_print_styles', 'print_emoji_styles' );

/* Stop WP guessing URLs */
function fp_remove_404_redirect($redirect_url) {
	if (is_404()) {
		return false;
	}
	
	return $redirect_url;
}

add_filter('redirect_canonical', 'fp_remove_404_redirect');

if (!function_exists('fishpig_comment')) {
	function fishpig_comment( $comment, $args, $depth ) {}
}

function fishpig_invalidate_cache( $post_id ) {
	// If this is just a revision, don't do anything
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	// Make an invalidation call to Magento
	$salt = get_option( 'fishpig_salt' );
	if (!$salt) {
		$salt = wp_generate_password( 64, true, true );
		update_option( 'fishpig_salt', $salt );
	}

	$nonce_tick = ceil(time() / ( 86400 / 2 ));

	$action = 'invalidate_' . $post_id;

	$nonce = substr( hash_hmac( 'sha256', $nonce_tick . '|fishpig|' . $action, $salt ), -12, 10 );

	wp_remote_get( home_url( '/wordpress/post/invalidate?id=' . $post_id . '&nonce=' . $nonce ) );
}

add_action( 'save_post', 'fishpig_invalidate_cache' );

remove_filter('template_redirect', 'redirect_canonical');

add_filter('preview_post_link', 'fishpig_preview_post_link', 10, 2);

function fishpig_preview_post_link($previewLink, $post) {
	return $previewLink . '&fishpig=' . time();
}

if (is_file(__DIR__ . DIRECTORY_SEPARATOR . 'cpt.php')) {
	@unlink(__DIR__ . DIRECTORY_SEPARATOR . 'cpt.php');
}

/* WPBakery */
if (isset($_GET['vc_editable'])) {
	ini_set('display_errors', 0);
}

// Ensure 404 isn't set 
add_filter(
	'status_header', 
	function($status_header, $code, $description, $protocol) {
		if ((int)$code === 404) {
			!defined('FISHPIG_WP_IS_404') ? define('FISHPIG_WP_IS_404', true) : '';
			return '';
		}
		
		return $status_header;
	}, 
	10, 
	4
);

/* Remove wptexturize to fix shortcodes */
remove_filter('the_content', 'wptexturize');

/* Include local.php*/
$localFile = __DIR__ . DIRECTORY_SEPARATOR . 'local.php';

if (is_file($localFile)) {
	include($localFile);
}
