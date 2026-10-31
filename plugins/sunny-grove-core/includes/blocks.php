<?php
/**
 * Đăng ký block động của Sunny Grove.
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Danh sách block (thư mục trong /blocks).
 *
 * @return string[]
 */
function sgc_block_names() {
	return array( 'video-banner', 'motion-media', 'media-gallery', 'term-filter', 'post-terms-list', 'hotel-info', 'menu', 'language-switcher', 'room-facts', 'place-facts', 'reviews' );
}

/**
 * Đăng ký script/style dùng chung và các block.
 */
function sgc_register_blocks() {
	wp_register_script(
		'sgc-blocks-editor',
		SGC_URL . 'assets/blocks-editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n', 'wp-data' ),
		SGC_VERSION,
		true
	);
	wp_set_script_translations( 'sgc-blocks-editor', 'sunny-grove-core', SGC_DIR . 'languages' );
	wp_register_style( 'sgc-blocks',SGC_URL . 'assets/blocks.css', array(), SGC_VERSION );
	wp_register_script(
		'sgc-blocks-view',
		SGC_URL . 'assets/blocks-view.js',
		array(),
		SGC_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
	foreach ( sgc_block_names() as $name ) {
		register_block_type( SGC_DIR . 'blocks/' . $name );
	}
}
add_action( 'init', 'sgc_register_blocks' );

/**
 * Nhãn cho JavaScript phía khách (đổi theo ngôn ngữ đang xem).
 */
function sgc_view_strings() {
	if ( wp_script_is( 'sgc-blocks-view', 'enqueued' ) || wp_script_is( 'sgc-blocks-view', 'registered' ) ) {
		wp_localize_script(
			'sgc-blocks-view',
			'sgcView',
			array(
				'pause'     => sgc_label( 'video_pause' ),
				'play'      => sgc_label( 'video_play' ),
				'menuOpen'  => sgc_label( 'menu_open' ),
				'menuClose' => sgc_label( 'menu_close' ),
				'galPrev'   => sgc_label( 'gallery_prev' ),
				'galNext'   => sgc_label( 'gallery_next' ),
				'galClose'  => sgc_label( 'gallery_close' ),
				'galCount'  => sgc_label( 'gallery_counter', array( 'n' => '{n}', 'tong' => '{tong}' ) ),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'sgc_view_strings', 20 );

/**
 * Nhóm block riêng trong trình chèn block.
 *
 * @param array $categories Nhóm.
 * @return array
 */
function sgc_block_category( $categories ) {
	array_unshift(
		$categories,
		array(
			'slug'  => 'sunny-grove',
			'title' => __( 'Sunny Grove', 'sunny-grove-core' ),
			'icon'  => null,
		)
	);
	return $categories;
}
add_filter( 'block_categories_all', 'sgc_block_category' );

/**
 * Danh sách phân loại cho trình soạn thảo.
 */
function sgc_editor_data() {
	wp_add_inline_script(
		'sgc-blocks-editor',
		'window.sgcEditor = ' . wp_json_encode(
			array(
				'taxonomies' => array(
					'sg_guest_type'   => __( 'Nhóm khách (Phòng)', 'sunny-grove-core' ),
					'sg_place_tag'    => __( 'Nhãn lọc (Địa điểm)', 'sunny-grove-core' ),
					'sg_room_feature' => __( 'Tiện nghi trong phòng', 'sunny-grove-core' ),
				),
				'reviewSources' => function_exists( 'sg_review_sources' ) ? sg_review_sources() : array(),
			)
		) . ';',
		'before'
	);
}
add_action( 'enqueue_block_editor_assets', 'sgc_editor_data' );

/**
 * Lấy URL video từ giá trị trường (ID tệp hoặc đường dẫn).
 *
 * @param string $value Giá trị.
 * @return string
 */
function sgc_video_src( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '';
	}
	if ( ctype_digit( $value ) ) {
		return (string) wp_get_attachment_url( (int) $value );
	}
	return $value;
}
