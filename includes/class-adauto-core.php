<?php
/**
 * Core functionality class for AdAuto Anti-Adblock plugin
 *
 * Plugin Name:   AdAuto - Anti Adblock Pro
 * Plugin URI:    https://github.com/mudman/adauto
 * Description:   Core class implementing anti-adblock protection features including
 *                HTML ad protection, Google AdSense integration, dynamic class randomization,
 *                MutationObserver monitoring, CSS injection, automatic restoration,
 *                API detection blocking, and WordPress admin interface.
 * Version:       1.0.0
 * Author:        Mud Man
 * Author URI:    https://nirenchuanshuo.com
 * License:       GPL v2 or later
 * License URI:   https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:   adauto
 * Domain Path:   /languages
 *
 * @package              AdAuto
 * @subpackage           Core
 * @author               Mud Man
 * @author-uri           https://nirenchuanshuo.com
 * @version              1.0.0
 * @since                1.0.0
 * @copyright            Copyright (c) 2024 Mud Man
 * @license-uri          https://www.gnu.org/licenses/gpl-2.0.html
 *
 * == Description ==
 *
 * This file contains the core functionality class (AdAuto_Core) that implements all major 
 * anti-adblock features for the AdAuto WordPress plugin.
 *
 * == Main Features ==
 *
 * - WordPress hooks and filters integration (wp_head, wp_footer, the_content)
 * - HTML image/text link advertisement protection and filtering
 * - Google AdSense specific protection with adsbygoogle override
 * - Dynamic CSS injection for immediate protection
 * - Bait element generation to distract ad blockers
 * - Settings page rendering and management
 * - Sanitization of user inputs
 * - Compatibility checks for PHP and WordPress versions
 *
 * == Class Methods ==
 *
 * - init(): Initialize the plugin and add hooks
 * - load_settings(): Load configuration from database
 * - is_enabled(): Check if plugin is enabled
 * - get_setting(): Retrieve specific setting value
 * - enqueue_assets(): Load JavaScript and CSS assets
 * - output_head_protection(): Inject protection code in <head>
 * - output_bait_elements(): Create distraction elements
 * - output_footer_protection(): Add final protection layer in footer
 * - filter_ad_content(): Filter post content for ads
 * - wrap_ad_element(): Wrap ad elements with protection attributes
 * - protect_adsense(): Protect Google AdSense units
 * - add_admin_menu(): Register admin settings page
 * - register_settings(): Define settings fields
 * - sanitize_settings(): Validate and clean user input
 * - render_*_field(): Render form field types
 * - render_settings_page(): Output admin interface
 * - add_plugin_links(): Add settings link on plugins page
 *
 * == Dependencies ==
 *
 * - Requires WordPress 4.6+
 * - Requires PHP 5.6+
 * - Depends on adauto.php main plugin file
 * - Uses assets/js/adauto-protection.js for client-side protection
 * - Uses assets/css/adauto-protection.css for base styles
 *
 * == Usage Example ==
 *
 * // Access instance from other plugins/themes
 * if (function_exists('adauto_init')) {
 *     $adauto = adauto_init();
 *     
 *     // Get a setting value
 *     $enabled = $adauto->get_setting('enabled');
 * }
 *
 * == Changelog ==
 *
 * = 1.0.0 =
 * * Initial implementation of core functionality
 * * Complete WordPress admin interface
 * * Full hook integration system
 * * AdSense protection methods
 * * Content filtering capabilities
 * * Settings management system
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

/**
 * Main class for anti-adblock functionality
 */
class AdAuto_Core {
    
    /**
     * Single instance of the class
     *
     * @var AdAuto_Core
     */
    private static $instance = null;
    
    /**
     * Plugin settings
     *
     * @var array
     */
    private $settings = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        // Load settings
        $this->load_settings();
    }
    
    /**
     * Get singleton instance
     *
     * @return AdAuto_Core
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Add hooks only if enabled
        if ($this->is_enabled()) {
            $this->add_hooks();
        }
        
        // Admin hooks (always load)
        if (is_admin()) {
            $this->add_admin_hooks();
        }
    }
    
    /**
     * Load settings from database
     */
    private function load_settings() {
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
        
        $saved = get_option('adauto_settings', array());
        $this->settings = wp_parse_args($saved, $defaults);
    }
    
    /**
     * Check if plugin is enabled
     *
     * @return bool
     */
    private function is_enabled() {
        return !empty($this->settings['enabled']);
    }
    
    /**
     * Get setting value
     *
     * @param string $key Setting key
     * @param mixed $default Default value
     * @return mixed
     */
    public function get_setting($key, $default = null) {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }
    
    /**
     * Add frontend hooks
     */
    private function add_hooks() {
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Output protection code in head
        add_action('wp_head', array($this, 'output_head_protection'), 1);
        
        // Output protection code before body close
        add_action('wp_footer', array($this, 'output_footer_protection'), 999);
        
        // Filter ad content if needed
        if ($this->get_setting('protect_html_ads')) {
            add_filter('the_content', array($this, 'filter_ad_content'));
        }
        
        // Protect AdSense specifically
        if ($this->get_setting('protect_adsense')) {
            add_action('wp_head', array($this, 'protect_adsense'));
        }
    }
    
    /**
     * Add admin hooks
     */
    private function add_admin_hooks() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Plugin action links
        add_filter('plugin_action_links_' . ADAUTO_BASENAME, array($this, 'add_plugin_links'));
    }
    
    /**
     * Enqueue JavaScript and CSS assets
     */
    public function enqueue_assets() {
        // Only on frontend
        if (is_admin()) {
            return;
        }
        
        // Main protection script
        wp_enqueue_script(
            'adauto-protection',
            ADAUTO_PLUGIN_URL . 'assets/js/adauto-protection.js',
            array(),
            ADAUTO_VERSION,
            false
        );
        
        // Localize script with settings
        wp_localize_script('adauto-protection', 'adautoSettings', array(
            'enabled' => $this->get_setting('enabled'),
            'randomizeClasses' => $this->get_setting('randomize_classes'),
            'useMutationObserver' => $this->get_setting('use_mutation_observer'),
            'injectCSSProtection' => $this->get_setting('inject_css_protection'),
            'autoRestore' => $this->get_setting('auto_restore'),
            'baitElements' => $this->get_setting('bait_elements'),
            'logMode' => $this->get_setting('log_mode'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('adauto_nonce'),
        ));
        
        // Protection CSS
        wp_enqueue_style(
            'adauto-protection',
            ADAUTO_PLUGIN_URL . 'assets/css/adauto-protection.css',
            array(),
            ADAUTO_VERSION
        );
        
        // Custom CSS from settings
        $custom_css = $this->get_setting('custom_css');
        if (!empty($custom_css)) {
            wp_add_inline_style('adauto-protection', $custom_css);
        }
    }
    
    /**
     * Output head protection code
     */
    public function output_head_protection() {
        // Anti-adblock meta tags
        echo '<meta name="adblocker" content="false">' . "\n";
        echo '<meta name="ad-blocker" content="disable">' . "\n";
        
        // Inline critical CSS for immediate protection
        $critical_css = $this->generate_critical_css();
        wp_add_inline_style('adauto-protection', $critical_css, 'before');
        
        // Bait elements in head (hidden)
        if ($this->get_setting('bait_elements')) {
            $this->output_bait_elements();
        }
    }
    
    /**
     * Generate critical CSS for immediate protection
     *
     * @return string CSS code
     */
    private function generate_critical_css() {
        $css = "
/* AdAuto Critical Protection - Immediate */
[data-adauto-type='sponsored'],
[data-adauto-type='adsense'],
.ins-wrapper,
.sponsor-content,
.promo-banner,
.partner-link {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    position: static !important;
    height: auto !important;
    width: auto !important;
    max-height: none !important;
    overflow: visible !important;
    pointer-events: auto !important;
    z-index: auto !important;
}

/* Prevent common adblock hiding techniques */
.adsbygoogle,
.google-ad,
.ad-container,
.ad-slot,
[id*='google_ads'],
[class*='adsbygoogle'] {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}
";
        
        return $css;
    }
    
    /**
     * Output bait elements to distract adblockers
     */
    private function output_bait_elements() {
        $bait_html = '
<!-- AdAuto Bait Elements -->
<div style="position:absolute;left:-9999px;top:-9999px;width:1px;height:1px;visibility:hidden;">
    <div class="ad ads advert advertisement banner adbanner ad-container ad-placement pub_300x250 textads banner-ad ad-wrapper adunit ad-unit ad-space adslot ad-slot"></div>
</div>
';
        
        echo $bait_html;
    }
    
    /**
     * Output footer protection code
     */
    public function output_footer_protection() {
        // Final protection script
        $protection_script = $this->generate_footer_script();
        echo '<script type="text/javascript">' . $protection_script . '</script>' . "\n";
    }
    
    /**
     * Generate footer protection JavaScript
     *
     * @return string JavaScript code
     */
    private function generate_footer_script() {
        $script = '
// AdAuto Footer Protection - Final Layer
(function() {
    if (typeof window.adautoFooterLoaded !== "undefined") return;
    window.adautoFooterLoaded = true;
    
    // Force display all protected elements
    var protectedSelectors = [
        "[data-adauto-type=\'sponsored\']",
        "[data-adauto-type=\'adsense\']",
        ".ins-wrapper",
        ".sponsor-content",
        ".promo-banner"
    ];
    
    function forceDisplay() {
        protectedSelectors.forEach(function(selector) {
            var elements = document.querySelectorAll(selector);
            elements.forEach(function(el) {
                el.style.setProperty("display", "block", "important");
                el.style.setProperty("visibility", "visible", "important");
                el.style.setProperty("opacity", "1", "important");
            });
        });
    }
    
    // Run immediately and on interval
    forceDisplay();
    setInterval(forceDisplay, 2000);
    
    // Block adblock detection APIs
    try {
        Object.defineProperty(window, "canRunAds", {
            get: function() { return true; },
            set: function() {},
            configurable: false
        });
        
        Object.defineProperty(window, "canRunAdsense", {
            get: function() { return true; },
            set: function() {},
            configurable: false
        });
    } catch(e) {}
})();
';
        
        return $script;
    }
    
    /**
     * Filter ad content to add protection attributes
     *
     * @param string $content Post content
     * @return string Modified content
     */
    public function filter_ad_content($content) {
        if (!$this->get_setting('protect_html_ads')) {
            return $content;
        }
        
        // Pattern to detect HTML image/text ads
        $patterns = array(
            '/<a[^>]*href=[\'"][^\'"]*[\'"][^>]*>\\s*<img[^>]*>/i',
            '/<div[^>]*class=[\'"][^\'"]*ad[^\'"]*[\'"][^>]*>/i',
            '/<div[^>]*data-ad[^\s>][^>]*>/i'
        );
        
        foreach ($patterns as $pattern) {
            $content = preg_replace_callback($pattern, array($this, 'wrap_ad_element'), $content);
        }
        
        return $content;
    }
    
    /**
     * Wrap ad element with protection
     *
     * @param array $matches Regex matches
     * @return string Wrapped element
     */
    private function wrap_ad_element($matches) {
        $original = $matches[0];
        
        // Generate random wrapper ID
        $wrapper_id = 'adauto-' . uniqid();
        
        // Add data attributes for protection
        $protected = sprintf(
            '<span id="%s" data-adauto-type="sponsored" data-adauto-original="%s">%s</span>',
            esc_attr($wrapper_id),
            base64_encode($original),
            $original
        );
        
        return $protected;
    }
    
    /**
     * Protect Google AdSense ads
     */
    public function protect_adsense() {
        if (!$this->get_setting('protect_adsense')) {
            return;
        }
        
        // Add AdSense specific protection script
        $adsense_protection = '
<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
<script>
// AdAuto AdSense Protection
(function() {
    // Override AdSense initialization to add protection
    var originalPush = (adsbygoogle || {}).push;
    if (typeof originalPush === "function") {
        adsbygoogle.push = function() {
            var result = originalPush.apply(this, arguments);
            
            // Protect all AdSense units after push
            setTimeout(function() {
                var ads = document.querySelectorAll(".adsbygoogle");
                ads.forEach(function(ad) {
                    ad.setAttribute("data-adauto-type", "adsense");
                    ad.style.setProperty("display", "block", "important");
                    ad.style.setProperty("visibility", "visible", "important");
                });
            }, 100);
            
            return result;
        };
    }
    
    // Monitor for dynamically added AdSense
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1 && 
                    (node.className && node.className.indexOf && node.className.indexOf("adsbygoogle") > -1 ||
                     node.querySelector && node.querySelector(".adsbygoogle"))) {
                    
                    node.setAttribute("data-adauto-type", "adsense");
                    node.style.setProperty("display", "block", "important");
                }
            });
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
})();
</script>
';
        
        echo $adsense_protection . "\n";
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('AdAuto Settings', 'adauto'),
            __('AdAuto', 'adauto'),
            'manage_options',
            'adauto-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('adauto_settings_group', 'adauto_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings')
        ));
        
        // Settings sections
        add_settings_section(
            'adauto_main_section',
            __('Main Settings', 'adauto'),
            array($this, 'render_section_info'),
            'adauto-settings'
        );
        
        // Individual settings fields
        add_settings_field(
            'enabled',
            __('Enable Protection', 'adauto'),
            array($this, 'render_checkbox_field'),
            'adauto-settings',
            'adauto_main_section',
            array('label_for' => 'enabled', 'description' => __('Enable anti-adblock protection', 'adauto'))
        );
        
        add_settings_field(
            'protect_html_ads',
            __('Protect HTML Ads', 'adauto'),
            array($this, 'render_checkbox_field'),
            'adauto-settings',
            'adauto_main_section',
            array('label_for' => 'protect_html_ads', 'description' => __('Protect image/text link ads', 'adauto'))
        );
        
        add_settings_field(
            'protect_adsense',
            __('Protect Google AdSense', 'adauto'),
            array($this, 'render_checkbox_field'),
            'adauto-settings',
            'adauto_main_section',
            array('label_for' => 'protect_adsense', 'description' => __('Protect AdSense advertisements', 'adauto'))
        );
        
        add_settings_field(
            'randomize_classes',
            __('Randomize Classes', 'adauto'),
            array($this, 'render_checkbox_field'),
            'adauto-settings',
            'adauto_main_section',
            array('label_for' => 'randomize_classes', 'description' => __('Use random class names to avoid detection', 'adauto'))
        );
        
        add_settings_field(
            'custom_css',
            __('Custom CSS', 'adauto'),
            array($this, 'render_textarea_field'),
            'adauto-settings',
            'adauto_main_section',
            array('label_for' => 'custom_css', 'description' => __('Additional custom CSS rules', 'adauto'))
        );
    }
    
    /**
     * Sanitize settings input
     *
     * @param array $input Input settings
     * @return array Sanitized settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        $sanitized['enabled'] = isset($input['enabled']) ? (bool) $input['enabled'] : false;
        $sanitized['protect_html_ads'] = isset($input['protect_html_ads']) ? (bool) $input['protect_html_ads'] : false;
        $sanitized['protect_adsense'] = isset($input['protect_adsense']) ? (bool) $input['protect_adsense'] : false;
        $sanitized['randomize_classes'] = isset($input['randomize_classes']) ? (bool) $input['randomize_classes'] : false;
        $sanitized['use_mutation_observer'] = isset($input['use_mutation_observer']) ? (bool) $input['use_mutation_observer'] : true;
        $sanitized['inject_css_protection'] = isset($input['inject_css_protection']) ? (bool) $input['inject_css_protection'] : true;
        $sanitized['auto_restore'] = isset($input['auto_restore']) ? (bool) $input['auto_restore'] : true;
        $sanitized['bait_elements'] = isset($input['bait_elements']) ? (bool) $input['bait_elements'] : true;
        $sanitized['log_mode'] = isset($input['log_mode']) ? (bool) $input['log_mode'] : false;
        $sanitized['custom_css'] = isset($input['custom_css']) ? sanitize_textarea_field($input['custom_css']) : '';
        
        return $sanitized;
    }
    
    /**
     * Render checkbox field
     *
     * @param array $args Field arguments
     */
    public function render_checkbox_field($args) {
        $value = $this->get_setting($args['label_for'], false);
        $checked = checked($value, true, false);
        
        printf(
            '<label><input type="checkbox" name="adauto_settings[%s]" value="1" %s /> %s</label>',
            esc_attr($args['label_for']),
            $checked,
            esc_html($args['description'])
        );
    }
    
    /**
     * Render textarea field
     *
     * @param array $args Field arguments
     */
    public function render_textarea_field($args) {
        $value = $this->get_setting($args['label_for'], '');
        
        printf(
            '<textarea name="adauto_settings[%s]" rows="5" cols="50" class="large-text code">%s</textarea><br/><p class="description">%s</p>',
            esc_attr($args['label_for']),
            esc_textarea($value),
            esc_html($args['description'])
        );
    }
    
    /**
     * Render section info
     */
    public function render_section_info() {
        echo '<p>' . esc_html__('Configure anti-adblock protection settings below.', 'adauto') . '</p>';
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form action="options.php" method="post">
                <?php
                settings_fields('adauto_settings_group');
                do_settings_sections('adauto-settings');
                submit_button(__('Save Settings', 'adauto'));
                ?>
            </form>
            
            <hr/>
            
            <h2><?php _e('Plugin Information', 'adauto'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Version', 'adauto'); ?></th>
                    <td><?php echo esc_html(ADAUTO_VERSION); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('PHP Version', 'adauto'); ?></th>
                    <td><?php echo esc_html(PHP_VERSION); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('WordPress Version', 'adauto'); ?></th>
                    <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * Add plugin action links
     *
     * @param array $links Existing links
     * @return array Modified links
     */
    public function add_plugin_links($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=adauto-settings') . '">' . __('Settings', 'adauto') . '</a>';
        array_unshift($links, $settings_link);
        
        return $links;
    }
}

// End of file
