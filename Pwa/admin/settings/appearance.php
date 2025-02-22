<?php
/**
 * Appearance Settings Page
 * 
 * @package WP_PWA_Converter
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// بررسی وجود کلاس برای جلوگیری از تداخل
if (!class_exists('WP_PWA_Appearance_Settings')) :

    class WP_PWA_Appearance_Settings {

        // پارامترهای پیشفرض
        private $default_settings = [
            'primary_color'   => '#2196f3',
            'secondary_color' => '#4caf50',
            'font_family'     => 'Roboto',
            'dark_mode'       => 'no',
            'layout_style'    => 'material'
        ];

        public function __construct() {
            add_action('admin_init', [$this, 'init_settings']);
            add_action('admin_menu', [$this, 'add_menu_item'], 20);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        }

        // ثبت منو و زیرمنو
        public function add_menu_item() {
            add_submenu_page(
                'wppwa-settings',
                __('Appearance Settings', 'wp-pwa-converter'),
                __('Appearance', 'wp-pwa-converter'),
                'manage_options',
                'wppwa-appearance',
                [$this, 'render_settings_page']
            );
        }

        // ثبت تنظیمات
        public function init_settings() {
            register_setting(
                'wppwa_appearance_group',
                'wppwa_appearance_settings',
                [
                    'sanitize_callback' => [$this, 'sanitize_settings'],
                    'default'           => $this->default_settings
                ]
            );

            // سکشن اصلی
            add_settings_section(
                'wppwa_appearance_main',
                __('Customize Mobile App UI', 'wp-pwa-converter'),
                [$this, 'render_section_header'],
                'wppwa-appearance'
            );

            // فیلدها
            $this->add_setting_field(
                'primary_color',
                __('Primary Color', 'wp-pwa-converter'),
                'color_picker'
            );

            $this->add_setting_field(
                'secondary_color',
                __('Secondary Color', 'wp-pwa-converter'),
                'color_picker'
            );

            $this->add_setting_field(
                'font_family',
                __('Font Family', 'wp-pwa-converter'),
                'font_select'
            );

            $this->add_setting_field(
                'dark_mode',
                __('Dark Mode', 'wp-pwa-converter'),
                'toggle_switch'
            );

            $this->add_setting_field(
                'layout_style',
                __('Layout Style', 'wp-pwa-converter'),
                'layout_radio'
            );
        }

        // تابع کمکی برای افزودن فیلدها
        private function add_setting_field($id, $title, $type) {
            add_settings_field(
                "wppwa_appearance_{$id}",
                $title,
                [$this, "render_{$type}_field"],
                'wppwa-appearance',
                'wppwa_appearance_main',
                ['id' => $id]
            );
        }

        // ========== رندر فیلدها ==========
        public function render_color_picker_field($args) {
            $settings = get_option('wppwa_appearance_settings');
            $value = $settings[$args['id']] ?? $this->default_settings[$args['id']];
            ?>
            <input type="color" 
                   name="wppwa_appearance_settings[<?php echo esc_attr($args['id']); ?>]" 
                   value="<?php echo esc_attr($value); ?>" 
                   class="wppwa-color-picker">
            <?php
        }

        public function render_font_select_field($args) {
            $settings = get_option('wppwa_appearance_settings');
            $value = $settings[$args['id']] ?? $this->default_settings[$args['id']];
            $fonts = $this->get_google_fonts_list();
            ?>
            <select name="wppwa_appearance_settings[<?php echo esc_attr($args['id']); ?>]" 
                    class="wppwa-font-select"
                    data-default="<?php echo esc_attr($this->default_settings['font_family']); ?>">
                <?php foreach ($fonts as $font) : ?>
                    <option value="<?php echo esc_attr($font); ?>" <?php selected($value, $font); ?>>
                        <?php echo esc_html($font); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="font-preview" style="font-family: '<?php echo esc_attr($value); ?>'; margin-top: 10px;">
                <?php esc_html_e('The quick brown fox jumps over the lazy dog', 'wp-pwa-converter'); ?>
            </div>
            <?php
        }

        public function render_toggle_switch_field($args) {
            $settings = get_option('wppwa_appearance_settings');
            $value = $settings[$args['id']] ?? $this->default_settings[$args['id']];
            ?>
            <label class="wppwa-switch">
                <input type="checkbox" 
                       name="wppwa_appearance_settings[<?php echo esc_attr($args['id']); ?>]" 
                       value="yes" <?php checked($value, 'yes'); ?>>
                <span class="slider round"></span>
            </label>
            <?php
        }

        public function render_layout_radio_field($args) {
            $settings = get_option('wppwa_appearance_settings');
            $value = $settings[$args['id']] ?? $this->default_settings[$args['id']];
            $layouts = [
                'material'  => __('Material Design', 'wp-pwa-converter'),
                'ios'       => __('iOS Style', 'wp-pwa-converter'),
                'custom'    => __('Custom', 'wp-pwa-converter')
            ];
            foreach ($layouts as $key => $label) :
                ?>
                <label style="margin-right: 20px;">
                    <input type="radio" 
                           name="wppwa_appearance_settings[<?php echo esc_attr($args['id']); ?>]" 
                           value="<?php echo esc_attr($key); ?>" <?php checked($value, $key); ?>>
                    <?php echo esc_html($label); ?>
                </label>
                <?php
            endforeach;
        }

        // ========== توابع کمکی ==========
        private function get_google_fonts_list() {
            $fonts = get_transient('wppwa_google_fonts_list');
            
            if (false === $fonts) {
                $response = wp_remote_get('https://www.googleapis.com/webfonts/v1/webfonts?key=YOUR_API_KEY');
                
                if (!is_wp_error($response)) {
                    $data = json_decode(wp_remote_retrieve_body($response), true);
                    $fonts = array_map(function($font) {
                        return $font['family'];
                    }, $data['items']);
                    
                    set_transient('wppwa_google_fonts_list', $fonts, WEEK_IN_SECONDS);
                } else {
                    $fonts = ['Roboto', 'Open Sans', 'Lato'];
                }
            }
            
            return $fonts;
        }

        // سانیتیزیشن تنظیمات
        public function sanitize_settings($input) {
            $output = [];
            
            $output['primary_color'] = sanitize_hex_color($input['primary_color'] ?? $this->default_settings['primary_color']);
            $output['secondary_color'] = sanitize_hex_color($input['secondary_color'] ?? $this->default_settings['secondary_color']);
            $output['font_family'] = sanitize_text_field($input['font_family'] ?? $this->default_settings['font_family']);
            $output['dark_mode'] = in_array($input['dark_mode'], ['yes', 'no']) ? $input['dark_mode'] : 'no';
            $output['layout_style'] = in_array($input['layout_style'], ['material', 'ios', 'custom']) ? $input['layout_style'] : 'material';
            
            return $output;
        }

        // رندر صفحه
        public function render_settings_page() {
            if (!current_user_can('manage_options')) {
                return;
            }
            ?>
            <div class="wrap wppwa-settings-wrap">
                <h1><?php esc_html_e('Appearance Settings', 'wp-pwa-converter'); ?></h1>
                
                <?php settings_errors(); ?>
                
                <form method="post" action="options.php">
                    <?php
                    settings_fields('wppwa_appearance_group');
                    do_settings_sections('wppwa-appearance');
                    submit_button(__('Save Changes', 'wp-pwa-converter'));
                    ?>
                </form>
                
                <!-- پیش نمایش زنده -->
                <div class="wppwa-live-preview">
                    <h3><?php esc_html_e('Live Preview', 'wp-pwa-converter'); ?></h3>
                    <div class="preview-box" data-theme-mode="<?php echo esc_attr(get_option('wppwa_appearance_settings')['dark_mode'] ?? 'no'); ?>">
                        <!-- محتوای پیش نمایش -->
                    </div>
                </div>
            </div>
            <?php
        }

        // اضافه کردن استایل و اسکریپت
        public function enqueue_assets($hook) {
            if ('wp-pwa-converter_page_wppwa-appearance' !== $hook) {
                return;
            }

            wp_enqueue_style(
                'wppwa-admin-appearance',
                plugins_url('assets/css/admin-appearance.css', dirname(__FILE__)),
                [],
                filemtime(plugin_dir_path(dirname(__FILE__)) . 'assets/css/admin-appearance.css')
            );

            wp_enqueue_script(
                'wppwa-admin-appearance',
                plugins_url('assets/js/admin-appearance.js', dirname(__FILE__)),
                ['jquery', 'wp-color-picker'],
                filemtime(plugin_dir_path(dirname(__FILE__)) . 'assets/js/admin-appearance.js'),
                true
            );

            // لوکالیزیشن برای اسکریپت
            wp_localize_script('wppwa-admin-appearance', 'wppwaAdminData', [
                'defaultFont' => $this->default_settings['font_family'],
                'rtl' => is_rtl() ? '1' : '0'
            ]);

            // فعال سازی رنگ پیکر
            wp_enqueue_style('wp-color-picker');
        }

        // هدر سکشن
        public function render_section_header() {
            echo '<p class="description">' . esc_html__('Customize the visual appearance of your mobile app. Changes will be reflected in real-time.', 'wp-pwa-converter') . '</p>';
        }
    }

    // مقداردهی اولیه
    new WP_PWA_Appearance_Settings();

endif;
