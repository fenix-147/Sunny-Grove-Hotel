<?php
/**
 * Plugin Name:       Sunny Grove Core
 * Description:       Dữ liệu của khách sạn Sunny Grove: Phòng, Tiện ích, Địa điểm, Ưu đãi, thông tin liên hệ, nhãn hiển thị, các block động và nút chat nổi.
 * Version:           1.0.4
 * Requires at least: 6.6
 * Requires PHP:      7.4
 * Author:            Sunny Grove
 * License:           GPL-2.0-or-later
 * Text Domain:       sunny-grove-core
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

define( 'SGC_VERSION', '1.0.4' );
define( 'SGC_FILE', __FILE__ );
define( 'SGC_DIR', plugin_dir_path( __FILE__ ) );
define( 'SGC_URL', plugin_dir_url( __FILE__ ) );

require_once SGC_DIR . 'includes/helpers.php';
require_once SGC_DIR . 'includes/i18n.php';
require_once SGC_DIR . 'includes/icons.php';
require_once SGC_DIR . 'includes/post-types.php';
require_once SGC_DIR . 'includes/meta.php';
require_once SGC_DIR . 'includes/settings.php';
require_once SGC_DIR . 'includes/bindings.php';
require_once SGC_DIR . 'includes/blocks.php';
require_once SGC_DIR . 'includes/query.php';
require_once SGC_DIR . 'includes/editor.php';
require_once SGC_DIR . 'includes/i18n-content.php';
require_once SGC_DIR . 'includes/i18n-admin.php';
require_once SGC_DIR . 'includes/seo.php';
require_once SGC_DIR . 'includes/security.php';
require_once SGC_DIR . 'includes/two-factor.php';
require_once SGC_DIR . 'includes/reviews.php';
require_once SGC_DIR . 'includes/migrate.php';
require_once SGC_DIR . 'includes/frontend.php';
require_once SGC_DIR . 'includes/roles.php';
require_once SGC_DIR . 'includes/demo.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once SGC_DIR . 'includes/cli.php';
}

/**
 * Kích hoạt plugin: đăng ký loại nội dung, nạp giá trị mặc định, tạo vai trò.
 */
function sgc_activate() {
	sgc_register_post_types();
	sgc_seed_options();
	sgc_add_roles();
	sg_i18n_rewrite_rules(); // Luật đường dẫn bản EN (/en/rooms/…) phải có trước khi lưu lại.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'sgc_activate' );

function sgc_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'sgc_deactivate' );

/**
 * Tải bản dịch giao diện quản trị của plugin.
 */
function sgc_load_textdomain() {
	load_plugin_textdomain( 'sunny-grove-core', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'sgc_load_textdomain', 1 );
