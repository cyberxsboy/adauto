<?php
/**
 * Plugin Name: AdAuto - Anti Adblock Pro
 * Plugin URI: https://github.com/mudman/adauto
 * Description: Professional anti-adblock plugin that bypasses browser ad blockers (AdBlock, uBlock Origin, AdGuard, etc.) for HTML image/text ads and Google AdSense. Features dynamic class randomization, mutation observers, CSS injection protection, and automatic ad restoration. Compatible with WordPress 4.6+ and PHP 5.6+.
 * Version: 1.0.0
 * Author: Mud Man
 * Author URI: https://nirenchuanshuo.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: adauto
 * Domain Path: /languages
 * Requires at least: 4.6
 * Requires PHP: 5.6
 * Tested up to: 6.5
 *
 * @package              AdAuto
 * @category             Plugin
 * @author               Mud Man
 * @author-uri           https://nirenchuanshuo.com
 * @version              1.0.0
 * @since                1.0.0
 * @copyright            Copyright (c) 2024 Mud Man
 * @license-uri          https://www.gnu.org/licenses/gpl-2.0.html
 *
 * == Description ==
 *
 * AdAuto - Anti Adblock Pro is a powerful WordPress plugin designed to protect your website's 
 * advertisements from being blocked by browser extensions and software.
 *
 * With the rise of ad blockers, website owners are losing significant revenue. AdAuto provides 
 * a comprehensive solution to ensure your advertisements are displayed to all visitors, regardless 
 * of their ad-blocking preferences.
 *
 * == Key Features ==
 *
 * ✅ Bypass Major Ad Blockers - Works against AdBlock, uBlock Origin, AdGuard, and other popular blockers
 * ✅ Protect HTML Ads - Image/text link advertisements with automatic protection attributes
 * ✅ Google AdSense Protection - Specialized protection for Google AdSense units
 * ✅ Dynamic Class Randomization - Avoids detection by using random class names
 * ✅ Mutation Observer Protection - Detects and counters DOM modifications by ad blockers
 * ✅ CSS Injection Prevention - Injects high-priority CSS rules to maintain visibility
 * ✅ Automatic Restoration - Periodically restores hidden or removed ad elements
 * ✅ API Detection Blocking - Blocks common adblock detection methods
 * ✅ Bait Element Distraction - Creates decoy elements to distract ad blockers
 * ✅ WordPress Admin Panel - Easy-to-use settings interface
 * ✅ High Compatibility - Works with WordPress 4.6+ and PHP 5.6+
 * ✅ Performance Optimized - Minimal impact on page load speed
 * ✅ Responsive Design - Mobile-friendly protection
 * ✅ Regular Updates - Continuous improvement against new blocking techniques
 *
 * == Supported Ad Types ==
 *
 * - HTML image/text link ads
 * - Google AdSense (all formats)
 * - Banner advertisements
 * - Sponsored content blocks
 * - Affiliate links with images
 * - Custom ad implementations
 *
 * == Browser Compatibility ==
 *
 * - Chrome/Chromium (latest + 2 versions)
 * - Firefox (latest + 2 versions)
 * - Safari (latest + 2 versions)
 * - Edge (latest + 2 versions)
 * - Opera (latest + 2 versions)
 * - Internet Explorer 11+
 * - Mobile browsers (iOS Safari, Chrome Mobile)
 *
 * == Installation ==
 *
 * 1. Upload the `adauto` folder to `/wp-content/plugins/` directory
 * 2. Go to Plugins menu in WordPress dashboard
 * 3. Find "AdAuto - Anti Adblock Pro" in the list
 * 4. Click "Activate"
 * 5. Configure settings at Settings > AdAuto
 *
 * == Configuration ==
 *
 * After activation, navigate to Settings > AdAuto and enable:
 * - Enable Protection (master switch)
 * - Protect HTML Ads (for image/text link ads)
 * - Protect Google AdSense (for AdSense units)
 * - Recommended: Enable all options for maximum protection
 *
 * == Changelog ==
 *
 * = 1.0.0 =
 * * Initial release with full anti-adblock functionality
 * * Support for HTML ads and Google AdSense
 * * Dynamic class randomization system
 * * Mutation observer implementation
 * * CSS injection protection
 * * Automatic restoration mechanism
 * * API detection blocking
 * * Complete admin interface
 * * Full compatibility with WordPress 4.6+ and PHP 5.6+
 *
 * == License ==
 *
 * Copyright (c) 2024 Mud Man
 * Released under the GPL v2 or later license.
 * https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('ADAUTO_VERSION', '1.0.0');
define('ADAUTO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ADAUTO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ADAUTO_BASENAME', plugin_basename(__FILE__));

// PHP version compatibility check
if (version_compare(PHP_VERSION, '5.6', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p><strong>AdAuto Error:</strong> This plugin requires PHP 5.6 or higher. Your server is running PHP ' . PHP_VERSION . '.</p></div>';
    });
    return;
}

// WordPress version compatibility check
global $wp_version;
if (version_compare($wp_version, '4.6', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p><strong>AdAuto Error:</strong> This plugin requires WordPress 4.6 or higher. You are using WordPress ' . $wp_version . '.</p></div>';
    });
    return;
}

// Autoloader for PHP 5.6+ with fallback
if (file_exists(ADAUTO_PLUGIN_DIR . 'includes/class-adauto-autoloader.php')) {
    require_once ADAUTO_PLUGIN_DIR . 'includes/class-adauto-autoloader.php';
} else {
    // Manual loading for compatibility
    require_once ADAUTO_PLUGIN_DIR . 'includes/class-adauto-core.php';
}

// Initialize the plugin
function adauto_init() {
    // Check if main class exists
    if (!class_exists('AdAuto_Core')) {
        return;
    }
    
    // Get instance of the core class
    $adauto = AdAuto_Core::get_instance();
    
    // Initialize the plugin
    if (method_exists($adauto, 'init')) {
        $adauto->init();
    }
    
    return $adauto;
}

// Hook into plugins loaded action
add_action('plugins_loaded', 'adauto_init');

// Activation hook
register_activation_hook(__FILE__, 'adauto_activate');
function adauto_activate() {
    // Set default options
    $defaults = array(
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
    );
    
    add_option('adauto_settings', $defaults);
    
    // Clear any caches
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'adauto_deactivate');
function adauto_deactivate() {
    // Cleanup if needed
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
}

// Uninstall hook
register_uninstall_hook(__FILE__, 'adauto_uninstall');
function adauto_uninstall() {
    delete_option('adauto_settings');
}
