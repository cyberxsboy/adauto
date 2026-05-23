<?php
/**
 * AdAuto 反广告拦截插件核心功能类
 *
 * @package AdAuto
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 反广告拦截功能主类
 */
class AdAuto_Core {
    
    /**
     * 单例实例
     *
     * @var AdAuto_Core
     */
    private static $instance = null;
    
    /**
     * 插件设置
     *
     * @var array
     */
    private $settings = array();
    
    /**
     * 构造函数
     */
    public function __construct() {
        $this->load_settings();
    }
    
    /**
     * 获取单例实例
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
     * 初始化插件
     */
    public function init() {
        if ($this->is_enabled()) {
            $this->add_hooks();
        }
        
        if (is_admin()) {
            $this->add_admin_hooks();
        }
    }
    
    /**
     * 从数据库加载设置
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
     * 检查插件是否启用
     *
     * @return bool
     */
    private function is_enabled() {
        return !empty($this->settings['enabled']);
    }
    
    /**
     * 获取设置值
     *
     * @param string $key 设置键名
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get_setting($key, $default = null) {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }
    
    /**
     * 添加前端钩子
     */
    private function add_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_head', array($this, 'output_head_protection'), 1);
        add_action('wp_footer', array($this, 'output_footer_protection'), 999);
        
        if ($this->get_setting('protect_html_ads')) {
            add_filter('the_content', array($this, 'filter_ad_content'));
        }
        
        if ($this->get_setting('protect_adsense')) {
            add_action('wp_head', array($this, 'protect_adsense'));
        }
    }
    
    /**
     * 添加后台钩子
     */
    private function add_admin_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_filter('plugin_action_links_' . ADAUTO_BASENAME, array($this, 'add_plugin_links'));
    }
    
    /**
     * 加载JavaScript和CSS资源
     */
    public function enqueue_assets() {
        if (is_admin()) {
            return;
        }
        
        wp_enqueue_script(
            'adauto-protection',
            ADAUTO_PLUGIN_URL . 'assets/js/adauto-protection.js',
            array(),
            ADAUTO_VERSION,
            false
        );
        
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
        
        wp_enqueue_style(
            'adauto-protection',
            ADAUTO_PLUGIN_URL . 'assets/css/adauto-protection.css',
            array(),
            ADAUTO_VERSION
        );
        
        $custom_css = $this->get_setting('custom_css');
        if (!empty($custom_css)) {
            wp_add_inline_style('adauto-protection', $custom_css);
        }
    }
    
    /**
     * 输出头部保护代码
     */
    public function output_head_protection() {
        echo '<meta name="adblocker" content="false">' . "\n";
        echo '<meta name="ad-blocker" content="disable">' . "\n";
        
        $critical_css = $this->generate_critical_css();
        wp_add_inline_style('adauto-protection', $critical_css, 'before');
        
        if ($this->get_setting('bait_elements')) {
            $this->output_bait_elements();
        }
    }
    
    /**
     * 生成关键CSS用于即时保护
     *
     * @return string CSS代码
     */
    private function generate_critical_css() {
        $css = "
/* AdAuto 关键保护 - 即时生效 */
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

/* 防止常见广告拦截隐藏技术 */
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
     * 输出诱饵元素分散广告拦截器注意力
     */
    private function output_bait_elements() {
        $bait_html = '
<!-- AdAuto 诱饵元素 -->
<div style="position:absolute;left:-9999px;top:-9999px;width:1px;height:1px;visibility:hidden;">
    <div class="ad ads advert advertisement banner adbanner ad-container ad-placement pub_300x250 textads banner-ad ad-wrapper adunit ad-unit ad-space adslot ad-slot"></div>
</div>
';
        
        echo $bait_html;
    }
    
    /**
     * 输出底部保护代码
     */
    public function output_footer_protection() {
        $protection_script = $this->generate_footer_script();
        echo '<script type="text/javascript">' . $protection_script . '</script>' . "\n";
    }
    
    /**
     * 生成底部保护JavaScript
     *
     * @return string JavaScript代码
     */
    private function generate_footer_script() {
        $script = '
// AdAuto 底部保护 - 最终层
(function() {
    if (typeof window.adautoFooterLoaded !== "undefined") return;
    window.adautoFooterLoaded = true;
    
    var protectedSelectors = [
        "[data-avauto-type=\'sponsored\']",
        "[data-avauto-type=\'adsense\']",
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
    
    forceDisplay();
    setInterval(forceDisplay, 2000);
    
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
     * 过滤广告内容添加保护属性
     *
     * @param string $content 文章内容
     * @return string 修改后的内容
     */
    public function filter_ad_content($content) {
        if (!$this->get_setting('protect_html_ads')) {
            return $content;
        }
        
        $patterns = array(
            '/<a[^>]*href=[\'"][^\'"]*[\'"][^>]*>\s*<img[^>]*>/i',
            '/<div[^>]*class=[\'"][^\'"]*ad[^\'"]*[\'"][^>]*>/i',
            '/<div[^>]*data-ad[^\s>][^>]*>/i'
        );
        
        foreach ($patterns as $pattern) {
            $content = preg_replace_callback($pattern, array($this, 'wrap_ad_element'), $content);
        }
        
        return $content;
    }
    
    /**
     * 包装广告元素添加保护
     *
     * @param array $matches 正则匹配结果
     * @return string 包装后的元素
     */
    private function wrap_ad_element($matches) {
        $original = $matches[0];
        $wrapper_id = 'adauto-' . uniqid();
        
        $protected = sprintf(
            '<span id="%s" data-adauto-type="sponsored" data-adauto-original="%s">%s</span>',
            esc_attr($wrapper_id),
            base64_encode($original),
            $original
        );
        
        return $protected;
    }
    
    /**
     * 保护Google AdSense广告
     */
    public function protect_adsense() {
        if (!$this->get_setting('protect_adsense')) {
            return;
        }
        
        $adsense_protection = '
<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
<script>
// AdAuto AdSense 保护
(function() {
    var originalPush = (adsbygoogle || {}).push;
    if (typeof originalPush === "function") {
        adsbygoogle.push = function() {
            var result = originalPush.apply(this, arguments);
            
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
     * 添加后台菜单
     */
    public function add_admin_menu() {
        add_options_page(
            'AdAuto 设置',
            'AdAuto',
            'manage_options',
            'adauto-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * 注册插件设置
     */
    public function register_settings() {
        register_setting('adauto_settings_group', 'adauto_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings')
        ));
        
        add_settings_section(
            'adauto_main_section',
            '基本设置',
            array($this, 'render_section_info'),
            'adauto-settings'
        );
        
        add_settings_field(
            'enabled',
            '启用反拦截保护',
            array($this, 'render_checkbox_field'),
            'adauto-settings',
            'adauto_main_section',
            array('label_for' => 'enabled', 'description' => '开启反广告拦截保护功能，绕过浏览器广告拦截器')
        );
        
        add_settings_field(
            'protect_html_ads',
            '保护HTML图文广告',
            array($this, 'render_checkbox_field'),
            'adauto-settings',
            'adauto_main_section',
            array('label_for' => 'protect_html_ads', 'description' => '自动识别并保护网站中的图片/链接类广告')
        );
        
        add_settings_field(
            'protect_adsense',
            '保护Google AdSense',
            array($this, 'render_checkbox_field'),
            'adauto-settings',
            'adauto_main_section',
            array('label_for' => 'protect_adsense', 'description' => '专门针对Google AdSense广告进行深度优化保护')
        );
        
        add_settings_field(
            'randomize_classes',
            '随机化CSS类名',
            array($this, 'render_checkbox_field'),
            'adauto-settings',
            'adauto_main_section',
            array('label_for' => 'randomize_classes', 'description' => '使用随机生成的CSS类名，避免被广告拦截器识别')
        );
        
        add_settings_field(
            'custom_css',
            '自定义CSS样式',
            array($this, 'render_textarea_field'),
            'adauto-settings',
            'adauto_main_section',
            array('label_for' => 'custom_css', 'description' => '添加额外的自定义CSS规则来增强广告保护效果')
        );
    }
    
    /**
     * 清理设置输入
     *
     * @param array $input 输入的设置
     * @return array 清理后的设置
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
     * 渲染复选框字段
     *
     * @param array $args 字段参数
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
     * 渲染文本域字段
     *
     * @param array $args 字段参数
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
     * 渲染分区说明
     */
    public function render_section_info() {
        echo '<p>' . '在下方配置反广告拦截保护的相关设置选项。' . '</p>';
    }
    
    /**
     * 渲染设置页面
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
                submit_button('保存设置');
                ?>
            </form>
            
            <hr/>
            
            <h2><?php echo '插件信息'; ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php echo '版本号'; ?></th>
                    <td><?php echo esc_html(ADAUTO_VERSION); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php echo 'PHP版本'; ?></th>
                    <td><?php echo esc_html(PHP_VERSION); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php echo 'WordPress版本'; ?></th>
                    <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * 添加插件操作链接
     *
     * @param array $links 已有链接
     * @return array 修改后的链接
     */
    public function add_plugin_links($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=adauto-settings') . '">' . '设置' . '</a>';
        array_unshift($links, $settings_link);
        
        return $links;
    }
}
