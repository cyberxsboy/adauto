<?php
/*
Plugin Name: AdAuto - 反广告拦截专业版
Plugin URI: https://github.com/mudman/adauto
Description: 绕过浏览器广告拦截器（AdBlock、uBlock Origin、AdGuard等），保护HTML图文广告和Google AdSense正常显示
Version: 1.0.0
Author: Mud Man
Author URI: https://nirenchuanshuo.com
License: GPL v2 or later
Text Domain: adauto
*/

if (!defined('ABSPATH')) {
    exit;
}

define('ADAUTO_VERSION', '1.0.0');
define('ADAUTO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ADAUTO_PLUGIN_URL', plugin_dir_url(__FILE__));

if (version_compare(PHP_VERSION, '5.6', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p><strong>AdAuto 错误：</strong>本插件需要 PHP 5.6 或更高版本。当前服务器运行的是 PHP ' . PHP_VERSION . '。</p></div>';
    });
    return;
}

require_once ADAUTO_PLUGIN_DIR . 'includes/class-adauto-core.php';

function adauto_init() {
    if (class_exists('AdAuto_Core')) {
        $adauto = AdAuto_Core::get_instance();
        if (method_exists($adauto, 'init')) {
            $adauto->init();
        }
        return $adauto;
    }
}
add_action('plugins_loaded', 'adauto_init');

register_activation_hook(__FILE__, 'adauto_activate');
function adauto_activate() {
    add_option('adauto_settings', array(
        'enabled' => true,
        'protect_html_ads' => true,
        'protect_adsense' => true,
        'randomize_classes' => true,
        'use_mutation_observer' => true,
        'inject_css_protection' => true,
        'auto_restore' => true,
        'bait_elements' => true,
        'log_mode' => false,
        'custom_css' => '',
    ));
}

register_deactivation_hook(__FILE__, 'adauto_deactivate');
function adauto_deactivate() {}

register_uninstall_hook(__FILE__, 'adauto_uninstall');
function adauto_uninstall() {
    delete_option('adauto_settings');
}
