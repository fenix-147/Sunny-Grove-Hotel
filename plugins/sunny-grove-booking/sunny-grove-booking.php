<?php
/**
 * Plugin Name:       Sunny Grove Booking
 * Description:       Form "Yêu cầu đặt phòng" cho khách sạn: lưu yêu cầu trong trang quản trị, gửi email song ngữ cho khách và khách sạn, chống spam, song ngữ cùng Sunny Grove Core. Hoạt động độc lập với theme.
 * Version:           1.1.0
 * Requires at least: 6.6
 * Requires PHP:      7.4
 * Author:            Sunny Grove
 * License:           GPL-2.0-or-later
 * Text Domain:       sunny-grove-booking
 *
 * @package SunnyGroveBooking
 */

defined( 'ABSPATH' ) || exit;

define( 'SGB_VERSION', '1.1.0' );
define( 'SGB_FILE', __FILE__ );
define( 'SGB_DIR', plugin_dir_path( __FILE__ ) );
define( 'SGB_URL', plugin_dir_url( __FILE__ ) );

require_once SGB_DIR . 'includes/settings.php';
require_once SGB_DIR . 'includes/post-type.php';
require_once SGB_DIR . 'includes/form.php';
require_once SGB_DIR . 'includes/submit.php';
require_once SGB_DIR . 'includes/emails.php';
require_once SGB_DIR . 'includes/admin.php';
require_once SGB_DIR . 'includes/contact.php';

/**
 * Kích hoạt: đăng ký loại nội dung, nạp mặc định, cấp quyền.
 */
function sgb_activate() {
	sgb_register_post_type();
	sgb_seed_options();
	sgb_grant_caps();
}
register_activation_hook( __FILE__, 'sgb_activate' );

/**
 * Bản dịch giao diện quản trị.
 */
function sgb_load_textdomain() {
	load_plugin_textdomain( 'sunny-grove-booking', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'sgb_load_textdomain', 1 );
