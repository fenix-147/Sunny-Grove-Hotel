<?php
/**
 * Sunny Grove – Block theme.
 *
 * Theme chỉ lo giao diện. Dữ liệu (phòng, địa điểm, ưu đãi, thông tin liên hệ…) nằm trong plugin Sunny Grove Core,
 * form đặt phòng nằm trong plugin Sunny Grove Booking.
 *
 * @package SunnyGrove
 */

defined( 'ABSPATH' ) || exit;

define( 'SUNNY_GROVE_VERSION', wp_get_theme( get_template() )->get( 'Version' ) );

require_once get_template_directory() . '/inc/media.php';
require_once get_template_directory() . '/inc/patterns.php';

/**
 * Thiết lập theme.
 */
function sunny_grove_setup() {
	add_theme_support( 'editor-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'custom-logo', array( 'flex-width' => true, 'flex-height' => true ) );
	add_theme_support( 'menus' );
	add_editor_style( 'assets/css/theme.css' );

	// Vị trí menu cổ điển: Polylang gán được mỗi ngôn ngữ một menu riêng.
	register_nav_menus(
		array(
			'primary' => __( 'Menu chính (đầu trang)', 'sunny-grove' ),
			'footer'  => __( 'Menu chân trang', 'sunny-grove' ),
		)
	);
}
add_action( 'after_setup_theme', 'sunny_grove_setup' );

/**
 * Bản dịch của theme (chuỗi trong các pattern).
 */
function sunny_grove_textdomain() {
	load_theme_textdomain( 'sunny-grove', get_template_directory() . '/languages' );
}
add_action( 'init', 'sunny_grove_textdomain', 1 );

/**
 * CSS & JS phía khách.
 */
function sunny_grove_assets() {
	wp_enqueue_style( 'sunny-grove', get_template_directory_uri() . '/assets/css/theme.css', array(), SUNNY_GROVE_VERSION );
	wp_enqueue_script(
		'sunny-grove',
		get_template_directory_uri() . '/assets/js/theme.js',
		array(),
		SUNNY_GROVE_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
	$label = static function ( $key ) {
		return function_exists( 'sgc_label' ) ? sgc_label( $key ) : '';
	};
	wp_localize_script(
		'sunny-grove',
		'sunnyGrove',
		array(
			'prev' => $label( 'carousel_prev' ),
			'next' => $label( 'carousel_next' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'sunny_grove_assets' );

/**
 * Tải trước 2 file font dùng ngay ở màn hình đầu tiên (chữ Việt nội dung + tiêu đề) để chữ hiện nhanh, không nhảy.
 */
function sunny_grove_preload_fonts() {
	$fonts = array( 'be-vietnam-pro-latin-400-normal.woff2', 'be-vietnam-pro-vietnamese-400-normal.woff2', 'lora-latin-wght-normal.woff2' );
	foreach ( $fonts as $font ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( get_template_directory_uri() . '/assets/fonts/' . $font )
		);
	}
}
add_action( 'wp_head', 'sunny_grove_preload_fonts', 1 );

/**
 * Đánh dấu trình duyệt có JavaScript để bật hiệu ứng hiện dần (không có JS thì nội dung vẫn hiện bình thường).
 */
function sunny_grove_js_flag() {
	echo "<script>if('IntersectionObserver' in window){document.documentElement.classList.add('sg-js');}</script>\n";
}
add_action( 'wp_head', 'sunny_grove_js_flag', 2 );

/**
 * Kiểu khối tùy chọn để chủ web áp dụng trong trình soạn thảo.
 */
function sunny_grove_block_styles() {
	register_block_style(
		'core/query',
		array(
			'name'  => 'sg-carousel',
			'label' => __( 'Băng chuyền ngang', 'sunny-grove' ),
		)
	);
	register_block_style(
		'core/group',
		array(
			'name'  => 'sg-card',
			'label' => __( 'Thẻ bo góc', 'sunny-grove' ),
		)
	);
	register_block_style(
		'core/group',
		array(
			'name'  => 'sg-reveal',
			'label' => __( 'Hiện dần khi cuộn', 'sunny-grove' ),
		)
	);
	register_block_style(
		'core/image',
		array(
			'name'  => 'sg-kenburns',
			'label' => __( 'Ken Burns (phóng chậm)', 'sunny-grove' ),
		)
	);
	register_block_style(
		'core/paragraph',
		array(
			'name'  => 'sg-eyebrow',
			'label' => __( 'Dòng nhỏ trên tiêu đề', 'sunny-grove' ),
		)
	);
	register_block_style(
		'core/list',
		array(
			'name'  => 'sg-checks',
			'label' => __( 'Danh sách dấu tích', 'sunny-grove' ),
		)
	);
}
add_action( 'init', 'sunny_grove_block_styles' );

/**
 * Nhóm pattern.
 */
function sunny_grove_pattern_categories() {
	register_block_pattern_category( 'sunny-grove', array( 'label' => __( 'Sunny Grove – Khối có sẵn', 'sunny-grove' ) ) );
	register_block_pattern_category( 'sunny-grove-pages', array( 'label' => __( 'Sunny Grove – Trang mẫu', 'sunny-grove' ) ) );
}
add_action( 'init', 'sunny_grove_pattern_categories' );

/**
 * Nhắc cài plugin đi kèm.
 */
function sunny_grove_plugin_notice() {
	if ( function_exists( 'sgc_label' ) || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	echo '<div class="notice notice-warning"><p>' . esc_html__( 'Theme Sunny Grove cần plugin “Sunny Grove Core” để hiển thị phòng, địa điểm, ưu đãi và thông tin liên hệ. Hãy cài và kích hoạt plugin này.', 'sunny-grove' ) . '</p></div>';
}
add_action( 'admin_notices', 'sunny_grove_plugin_notice' );
