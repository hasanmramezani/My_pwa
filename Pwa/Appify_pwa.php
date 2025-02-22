<?php
/**
 * Plugin Name: Appify - WP PWA Converter for WordPress
 * Plugin URI: https://seokar.click/product/appify/
 * Description: Convert your WordPress site into a Progressive Web App (PWA) with ease..
 * Version: 2.0.3
 * Author: Sajjad Akbari
 * Author URI: https://sajjadakbari.ir/
 * License: GPL-2.0+
 * Text Domain: appify
 * Domain Path: /languages
 */
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// تعریف ثابت‌های افزونه
define('WP_PWA_CONVERTER_VERSION', '1.0.0');
define('WP_PWA_CONVERTER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_PWA_CONVERTER_PLUGIN_URL', plugin_dir_url(__FILE__));

// بارگیری فایل‌های وابسته
require_once WP_PWA_CONVERTER_PLUGIN_DIR . 'includes/pwa-core.php';
require_once WP_PWA_CONVERTER_PLUGIN_DIR . 'includes/notifications.php';
require_once WP_PWA_CONVERTER_PLUGIN_DIR . 'includes/optimizations.php';
require_once WP_PWA_CONVERTER_PLUGIN_DIR . 'includes/critical.php';

require_once WP_PWA_CONVERTER_PLUGIN_DIR . 'admin/settings/main.php';
require_once WP_PWA_CONVERTER_PLUGIN_DIR . 'admin/settings/appearance.php';
require_once WP_PWA_CONVERTER_PLUGIN_DIR . 'admin/settings/notifications.php';
require_once WP_PWA_CONVERTER_PLUGIN_DIR . 'admin/settings/build-settings.php';

// فعال‌سازی ترجمه‌ها
function wp_pwa_converter_load_textdomain() {
    load_plugin_textdomain('wp-pwa-converter', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'wp_pwa_converter_load_textdomain');

// فعال‌سازی افزونه
function wp_pwa_converter_activate() {
    // کدهای مورد نیاز برای فعال‌سازی افزونه
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'wp_pwa_converter_activate');

// غیرفعال‌سازی افزونه
function wp_pwa_converter_deactivate() {
    // کدهای مورد نیاز برای غیرفعال‌سازی افزونه
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'wp_pwa_converter_deactivate');

// حذف افزونه
function wp_pwa_converter_uninstall() {
    // کدهای مورد نیاز برای حذف افزونه
    delete_option('wppwa_general_settings');
    delete_option('wppwa_notifications_settings');
    delete_option('wppwa_appearance_settings');
    delete_option('wppwa_build_settings');
}
register_uninstall_hook(__FILE__, 'wp_pwa_converter_uninstall');

// افزودن لینک تنظیمات به صفحه افزونه‌ها
function wp_pwa_converter_add_settings_link($links) {
    $settings_link = '<a href="admin.php?page=wppwa-settings">' . __('Settings', 'wp-pwa-converter') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wp_pwa_converter_add_settings_link');

// بارگیری استایل‌ها و اسکریپت‌های عمومی
function wp_pwa_converter_enqueue_public_assets() {
    if (wp_pwa_converter_is_pwa_enabled()) {
        wp_enqueue_style(
            'wppwa-public-style',
            WP_PWA_CONVERTER_PLUGIN_URL . 'public/style.css',
            [],
            filemtime(WP_PWA_CONVERTER_PLUGIN_DIR . 'public/style.css')
        );

        wp_enqueue_script(
            'wppwa-public-script',
            WP_PWA_CONVERTER_PLUGIN_URL . 'public/js/main.js',
            ['jquery'],
            filemtime(WP_PWA_CONVERTER_PLUGIN_DIR . 'public/js/main.js'),
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'wp_pwa_converter_enqueue_public_assets');

// بارگیری استایل‌ها و اسکریپت‌های مدیریتی
function wp_pwa_converter_enqueue_admin_assets($hook) {
    if (strpos($hook, 'wppwa-settings') !== false) {
        wp_enqueue_style(
            'wppwa-admin-style',
            WP_PWA_CONVERTER_PLUGIN_URL . 'assets/css/admin-main.css',
            [],
            filemtime(WP_PWA_CONVERTER_PLUGIN_DIR . 'assets/css/admin-main.css')
        );

        wp_enqueue_script(
            'wppwa-admin-script',
            WP_PWA_CONVERTER_PLUGIN_URL . 'assets/js/admin-main.js',
            ['jquery'],
            filemtime(WP_PWA_CONVERTER_PLUGIN_DIR . 'assets/js/admin-main.js'),
            true
        );

        wp_localize_script('wppwa-admin-script', 'wppwaAdminData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('wppwa_admin_nonce')
        ]);
    }
}
add_action('admin_enqueue_scripts', 'wp_pwa_converter_enqueue_admin_assets');

// بررسی فعال‌بودن PWA
function wp_pwa_converter_is_pwa_enabled() {
    $settings = get_option('wppwa_general_settings', []);
    return isset($settings['enable_pwa']) && $settings['enable_pwa'] === 'yes';
}
