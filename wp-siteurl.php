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

function wp_siteurl_get_option($name = 'siteurl') {
  global $wpdb;
  $opt = $wpdb->get_row("SELECT * FROM $wpdb->options WHERE option_name = '$name'");
  if ($opt) {
    return $opt->option_value;
  }
  return null;
}

function wp_siteurl_is_valid() {
  $siteurl = wp_siteurl_get_option('siteurl');
  $baseurl = wp_siteurl_get_baseurl();
  return $siteurl == $baseurl;
}

// function wp_siteurl_admin_init() {
//   //echo "ADMIN";
// }
// add_action( 'admin_init', 'wp_siteurl_admin_init', 1000 );

// function wp_siteurl_filter( $value ) {
//   $value = wp_siteurl_get_baseurl();
//   return $value;
// }


function wp_siteurl_notice() {
  $siteurl = wp_siteurl_get_option('siteurl');
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
//
// update_option('siteurl', $baseurl);
// update_option('home', $baseurl);

//
// if (!defined('WP_HOME')) {
//   define('WP_HOME', $baseurl);
// }
//
// if (!defined('WP_SITEURL')) {
//   define('WP_SITEURL', $baseurl);
// }

// if (!defined('WP_SITEURL')) {
//   define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content' );
// }

// function process_post() {
//
// }
// add_action( 'init', 'process_post' );

function wp_siteurl_redirect( $location ) {
  // return $location;
  // echo $location;
  // exit;
  // return null;
  return $location;
}
add_filter( 'wp_redirect', 'wp_siteurl_redirect', 1000 );

function wp_siteurl_url_replace($url, $search_url = "", $replace_url = "") {
  if (!$search_url) {
    return $url;
  }
  $protocol_pattern = "https?\:\/\/";
  $search_uri = preg_replace("~^$protocol_pattern~", "", $search_url);
  $siteurl_pattern = "~^($protocol_pattern)?" . preg_quote($search_uri) . "~";
  // echo "URL: $url  0000 $search_url 00000 $siteurl_pattern<br/>";
  if (preg_match($siteurl_pattern, $url)) {
    // echo "MATCH: " . $siteurl_pattern . " ...";
    // URL matches siteurl in db
    $replace_uri = preg_replace("~^$protocol_pattern~", "", $replace_url);
    $replace = preg_match("~^$protocol_pattern~", $url) ? $replace_url : $replace_uri;
    $new_url = preg_replace($siteurl_pattern, $replace, "${1}$url");
    // echo "$url -> $new_url<br/>";
  } else {
    $new_url = $url;
    // echo "$url<br/>";
  }
  // echo "$url --> $replace --> $new_url<br/>";
  // exit;
  return $new_url;
}

function wp_siteurl_get_site_url($url) {
  $siteurl = wp_siteurl_get_option('siteurl');
  // echo "SITEURL: $siteurl";
  $baseurl = wp_siteurl_get_baseurl();
  return wp_siteurl_url_replace($url, $siteurl, $baseurl);
}

add_filter( 'option_siteurl', 'wp_siteurl_get_site_url', 1000 );
add_filter( 'site_url', 'wp_siteurl_get_site_url', 1);
add_filter( 'content_url', 'wp_siteurl_get_site_url', 1);
add_filter( 'plugins_url', 'wp_siteurl_get_site_url', 1);
add_filter( 'script_loader_src', 'wp_siteurl_get_site_url', 1000 );
add_filter( 'style_loader_src', 'wp_siteurl_get_site_url', 1000 );

function wp_siteurl_get_home_url($url) {
  $homeurl = wp_siteurl_get_option('home');
  $baseurl = wp_siteurl_get_baseurl();
  $result = wp_siteurl_url_replace($url, $homeurl, $baseurl);
  return wp_siteurl_url_replace($url, $homeurl, $baseurl);
}
add_filter( 'option_home', 'wp_siteurl_get_home_url', 1000 );
add_filter( 'home_url', 'wp_siteurl_get_home_url', 1);


function wp_siteurl_filter_upload_dir($paths) {
  $paths = array_merge($paths);
  $paths['url'] = wp_siteurl_get_site_url($paths['url']);
  $paths['baseurl'] = wp_siteurl_get_site_url($paths['baseurl']);
  return $paths;
}
add_filter( 'upload_dir', 'wp_siteurl_filter_upload_dir', 1, 2);


function wp_siteurl_resource_hints($urls) {
  $urls = array_map('wp_siteurl_get_site_url', $urls);
  // print_r($urls);
  return $urls;
}
add_filter( 'wp_resource_hints', 'wp_siteurl_resource_hints', 1);


function wp_siteurl_sanitize_content($content) {
  $siteurl = wp_siteurl_get_option('siteurl');
  $baseurl = wp_siteurl_get_baseurl();
  if ($siteurl == $baseurl) {
    // Everything fine
    return $content;
  }
  // Parse DOM
  $doc = new DOMDocument();
  @$doc->loadHTML('<?xml encoding="utf-8" ?>' . $content );
  $doc_xpath = new DOMXpath($doc);

  // Sanitize URLs
  $attributes = array('src', 'href');
  $query_parts = array();
  foreach ($attributes as $attribute) {
    $query_parts[] = "//*[starts-with(@$attribute, '$siteurl')]";
  }
  $query = implode("|", $query_parts);
  $siteurl_elements = $doc_xpath->query($query);
  foreach ($siteurl_elements as $siteurl_element) {
    foreach ($attributes as $attribute) {
      $siteurl_element->setAttribute($attribute, wp_siteurl_get_site_url($siteurl_element->getAttribute($attribute)));
    }
  }
  return preg_replace('~(?:<\?[^>]*>|<(?:!DOCTYPE|/?(?:html|head|body))[^>]*>)\s*~i', '', $doc->saveHTML());
}
add_filter( 'the_content', 'wp_siteurl_sanitize_content', 1000 );


// https://philipnewcomer.net/2014/06/filter-output-wordpress-widget/
function wp_siteurl_dynamic_sidebar_params() {
  global $wp_registered_widgets;
  $original_callback_params = func_get_args();
  $widget_id = $original_callback_params[0]['widget_id'];
  $original_callback = $wp_registered_widgets[ $widget_id ]['original_callback'];
  $wp_registered_widgets[ $widget_id ]['callback'] = $original_callback;
  $widget_id_base = $wp_registered_widgets[ $widget_id ]['callback'][0]->id_base;
  if ( is_callable( $original_callback ) ) {
    ob_start();
    call_user_func_array( $original_callback, $original_callback_params );
    $widget_output = ob_get_clean();
    // echo apply_filters( 'widget_output', $widget_output, $widget_id_base, $widget_id );
  }
}
add_filter( 'dynamic_sidebar_params', 'wp_siteurl_dynamic_sidebar_params' );


function wp_siteurl_widget_callback() {
  global $wp_registered_widgets;
  $original_callback_params = func_get_args();
  $widget_id = $original_callback_params[0]['widget_id'];
  $original_callback = $wp_registered_widgets[ $widget_id ]['original_callback'];
  $wp_registered_widgets[ $widget_id ]['callback'] = $original_callback;
  $widget_id_base = $wp_registered_widgets[ $widget_id ]['callback'][0]->id_base;
  if ( is_callable( $original_callback ) ) {
    ob_start();
    call_user_func_array( $original_callback, $original_callback_params );
    $widget_output = ob_get_clean();
    $widget_output = wp_siteurl_sanitize_content($widget_output);
    echo $widget_output;
  }
}


/* Third Party Support */
add_filter( 'wpml_home_url', 'wp_siteurl_get_home_url', 1 );
add_filter( 'wpml_url_converter_get_abs_home', 'wp_siteurl_get_home_url', 1 );


?>
