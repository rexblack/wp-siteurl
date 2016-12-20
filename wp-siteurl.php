<?php

/**
 Plugin Name: Site URL
 Plugin URI: http://github.com/benignware/wp-bootstrap-hooks
 Description: Fix Site URL Conflicts
 Version: 0.0.1
 Author: Rafael Nowrotek, Benignware
 Author URI: http://benignware.com
 License: MIT
*/

function wp_siteurl_get_baseurl() {
  if (defined('WP_BASE_URL')) {
    return WP_BASE_URL;
  }
  $path = ABSPATH ? ABSPATH : get_home_path();
  if (file_exists(dirname($path) . "/wp-config.php")) {
    $file = dirname($path) . "/wp-config.php";
  } else {
    $file = $path . "/wp-config.php";
  }
  $url = rtrim( (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . "/" . rtrim(dirname(str_replace($_SERVER['DOCUMENT_ROOT'], '', $file)), "./"), "/" );
  // TODO: WPSiteUrlCache
  return $url;
}

function wp_siteurl_get_option() {
  global $wpdb;
  $opt = $wpdb->get_row("SELECT * FROM $wpdb->options WHERE option_name = 'siteurl'");
  if ($opt) {
    return $opt->option_value;
  }
  return null;
}

function wp_siteurl_is_valid() {
  $siteurl = wp_siteurl_get_option();
  $baseurl = wp_siteurl_get_baseurl();
  return $siteurl == $baseurl;
}

function wp_siteurl_admin_init() {
  //echo "ADMIN";
}
add_action( 'admin_init', 'wp_siteurl_admin_init', 1 );

function wp_siteurl_filter( $value ) {
  $value = wp_siteurl_get_baseurl();
  // echo "GET $value";
	return $value;
}
add_filter( 'option_siteurl', 'wp_siteurl_filter', 1 );
add_filter( 'option_home', 'wp_siteurl_filter', 1 );


function wp_siteurl_notice() {
  $siteurl = wp_siteurl_get_option();
  $baseurl = wp_siteurl_get_baseurl();
  $message = "Base URL '$baseurl' conflicts with siteurl-option '$siteurl'";
  $message = "<b>Site URL Conflict</b><br/>$baseurl <-> $siteurl";
  if ($siteurl != $baseurl) : ?>
    <div class="notice notice-warning is-dismissible">
        <p><?php _e( $message, 'sample-text-domain' ); ?></p>
    </div>
  <?php endif;
}
add_action( 'admin_notices', 'wp_siteurl_notice' );

// define('FORCE_SSL_ADMIN', false);
$baseurl = wp_siteurl_get_baseurl();

update_option('siteurl', $baseurl);
update_option('home', $baseurl);

if (!defined('WP_HOME')) {
  define('WP_HOME', $baseurl);
}

if (!defined('WP_SITEURL')) {
  define('WP_SITEURL', $baseurl);
}

// if (!defined('WP_SITEURL')) {
// define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content' );

// function process_post() {
//
// }
// add_action( 'init', 'process_post' );

// function wp_siteurl_redirect( $location ) {
//   return null;
//   return $location;
// }
// add_filter( 'wp_redirect', 'wp_siteurl_redirect', 1 );



function wp_siteurl_get_site_url($url) {
  // Strip protocol
  //$pattern = "~^https?\:\/\/relaunch\.audiolith";
  $siteurl = wp_siteurl_get_option();
  $siteurl_pattern = "~https?\:\/\/" . preg_quote(preg_replace("~^https?\:\/\/~", "", $siteurl)) . "~";
  // echo "URI: " . $siteuri . " ---> URL: " . $url . "<br/>";
  if (preg_match($siteurl_pattern, $url)) {
    // URL matches siteurl in db
    $baseurl = wp_siteurl_get_baseurl();
    // echo "YES: BASE-URL: " . $baseurl . " -  --> ";
    $url = preg_replace($siteurl_pattern, $baseurl, $url);
    // echo "RESULT: $ourl --> $siteurl_pattern ---> $url<br/>";
  }
  return $url;
}

add_filter( 'site_url', 'wp_siteurl_get_site_url', 1);
add_filter( 'content_url', 'wp_siteurl_get_site_url', 1);
add_filter( 'plugins_url', 'wp_siteurl_get_site_url', 1);
add_filter( 'script_loader_src', 'wp_siteurl_get_site_url', 1 );
add_filter( 'style_loader_src', 'wp_siteurl_get_site_url', 1 );

function wp_siteurl_filter_upload_dir($paths) {
  $paths = array_merge($paths);
  $paths['url'] = wp_siteurl_get_site_url($paths['url']);
  $paths['baseurl'] = wp_siteurl_get_site_url($paths['baseurl']);
  return $paths;
}

add_filter( 'upload_dir', 'wp_siteurl_filter_upload_dir', 1, 2);


?>
