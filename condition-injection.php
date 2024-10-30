<?php
/*
Plugin Name: Condition-based HTML Snippet Injection
Plugin URI: https://javan.de/condition-injection/
Description: Insert a HTML snippet into your blog only for selected conditions. Allows you to define a target group and execute targeted code on the client. The decision is made on the server side.
Version: 1.0
Author: Javan Rasokat
Author URI: https://javan.de
Text Domain: condition-injection
License: GPLv2 or later
Disclaimer: Use at your own risk. No warranty expressed or implied is provided.
*/

add_action('admin_menu', 'condition_injection_create_admin_menu');
add_action('admin_init', 'condition_injection_add_settings');
add_action('init', 'condition_injection_setup_default_settings');

$condition_injection_default_settings = array(
    'snippet' => '<big style="color:red">Hey [insert name]!</big>'
        . "\n" . '<script>/* e.g. check for reverse tabnabbing */'
        . "\n" . 'if(window.opener) {'
        . "\n" . 'console.log( window.opener );'
        . "\n" . '} else { console.log("window.opener is null"); }'
        . "\n" . '</script>',
    'enable_mobile_snippet' => false,
    'mobile_snippet' => 'injected mobile <b>snippet</b>',
    'fallback_mobile_user_agents' => 'phone|iphone|ipod|android.+mobile|xoom',
    'enable_condition_referrers' => true,
    'condition_referrers' => 'mentor.corp.google.com|corp.google.com',
    'enable_condition_ip_trunkated' => false,
    'condition_ip_trunkated' => '127.0.0.1|87.123.206|2001:16b8:2c2b:1e00:84a9:eb1e:c806',
    'enable_condition_user_agents' => false,
    'condition_user_agents' => 'macintosh|cros',
    'enable_condition_hostname' => false,
    'condition_hostnames' => '1und1|i577bce5c.versanet.de',
    'condition_injection_php_exec' => false
);

function condition_injection_add_settings() {
    global $condition_injection_default_settings;
    register_setting('condition_injection', 'condition_injection', $condition_injection_default_settings);
}

function condition_injection_setup_default_settings() {
	global $condition_injection_default_settings;
    if (get_option('condition_injection') == "" || get_option('condition_injection') == null) {
        update_option('condition_injection', $condition_injection_default_settings);
    }
}

function condition_injection_create_admin_menu() {
    add_options_page('Condition Injection', 'Condition Injection', 'manage_options', 'condition-injection/options.php');
}

add_action('wp_footer', 'condition_injection_wp_footer');

function condition_injection_wp_footer() {
    global $condition_injection_options, $condition_injection_is_mobile, $doInject;
	$condition_injection_options = get_option('condition_injection', array());

    $condition_injection_is_mobile = false;
    if (defined('IS_PHONE') && IS_PHONE) {
        $condition_injection_is_mobile = true;
    } else if (isset($_SERVER['HTTP_USER_AGENT']) && isset($condition_injection_options['fallback_mobile_user_agents'])) {
        $condition_injection_is_mobile = preg_match('/' . $condition_injection_options['fallback_mobile_user_agents'] . '/', strtolower($_SERVER['HTTP_USER_AGENT']));
    }

    $doInject = true;
    if ($doInject && isset($condition_injection_options['enable_condition_ip_trunkated']) && (boolean)$condition_injection_options['enable_condition_ip_trunkated']) {
        $doInject = preg_match('/' . $condition_injection_options['condition_ip_trunkated'] . '/', strtolower($_SERVER['REMOTE_ADDR']));
    }
    if ($doInject && isset($condition_injection_options['enable_condition_referrers']) && (boolean)$condition_injection_options['enable_condition_referrers']) {
        $doInject = preg_match('/' . $condition_injection_options['condition_referrers'] . '/', strtolower($_SERVER['HTTP_REFERER']));
    }
    if ($doInject && isset($condition_injection_options['enable_condition_user_agents']) && (boolean)$condition_injection_options['enable_condition_user_agents']) {
        $doInject = preg_match('/' . $condition_injection_options['condition_user_agents'] . '/', strtolower($_SERVER['HTTP_USER_AGENT']));
    }
    if ($doInject && isset($condition_injection_options['enable_condition_hostname']) && (boolean)$condition_injection_options['enable_condition_hostname']) {
        putenv('RES_OPTIONS=retrans:1 retry:1 timeout:1 attempts:1');
        $hostname = gethostbyaddr($_SERVER['REMOTE_ADDR']);
        $doInject = preg_match('/' . $condition_injection_options['condition_hostnames'] . '/', strtolower($hostname));
    }

    if ($doInject) {

        if ($condition_injection_is_mobile && isset($condition_injection_options['enable_mobile_snippet']) && (boolean)$condition_injection_options['enable_mobile_snippet']) {
            $buffer = $condition_injection_options['mobile_snippet'];
        } else {
            $buffer = $condition_injection_options['snippet'];
        }

        if (isset($condition_injection_options['condition_injection_php_exec']) && (boolean)$condition_injection_options['condition_injection_php_exec']) {
            ob_start();
            eval('?>' . $buffer);
            ob_end_flush();
        } else {
            echo $buffer;
        }
    }
}

add_filter( 'plugin_action_links', 'condition_injection_add_action_links', 10, 5 );
add_filter( 'plugin_row_meta',     'condition_injection_add_plugin_row_meta', 10, 2 );

function condition_injection_add_action_links( $actions, $plugin_file ) {
 $action_links = array(
     'reset' => array(
         'label' => 'Reset',
         'url'   => wp_nonce_url(get_admin_url(null, 'options-general.php?page=condition-injection/options.php&reset=true'), 'reset_action', 'my_action_reset_nonce'),
         'color' => '#a00'
     ),
     'settigns' => array(
        'label' => 'Settings',
        'url'   => get_admin_url(null, 'options-general.php?page=condition-injection/options.php')
     )
   );
  return condition_injection_plugin_action_links( $actions, $plugin_file, $action_links, 'before');
}

function condition_injection_add_plugin_row_meta( $actions, $plugin_file ) {
 $action_links = array(
   'donate' => array(
      'label' => 'Donate',
      'url'   => 'https://javan.de/donate/'
    ));
  return condition_injection_plugin_action_links( $actions, $plugin_file, $action_links, 'after');
}

function  condition_injection_plugin_action_links ( $actions, $plugin_file,  $action_links = array(), $position = 'after' ) {
  static $plugin;
  if( !isset($plugin) ) {
      $plugin = plugin_basename( __FILE__ );
  }
  if( $plugin == $plugin_file && !empty( $action_links ) ) {
     foreach( $action_links as $key => $value ) {
        $link = array( $key => '<a href="' . $value['url'] . '" ' . (isset($value['color']) ? 'style="color:'.$value['color'].'"' : '') .'>' . $value['label'] . '</a>' );

         if( $position == 'after' ) {
            $actions = array_merge( $actions, $link );
         } else {
            $actions = array_merge( $link, $actions );
         }
      }//foreach
  }// if
  return $actions;
}
