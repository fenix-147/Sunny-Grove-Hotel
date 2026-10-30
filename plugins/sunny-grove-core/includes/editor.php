<?php
/**
 * Trình soạn thảo hiển thị giống ngoài website:
 * - Query Loop chỉ lấy bài cùng ngôn ngữ với trang đang sửa (Polylang bản miễn phí không lọc request REST của trình soạn thảo).
 * - Trường phòng/địa điểm/ưu đãi hiện giá trị đã định dạng (giá, ngày…) thay vì số thô.
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Loại nội dung được lọc theo ngôn ngữ trong trình soạn thảo.
 *
 * @return string[]
 */
function sgc_editor_lang_types() {
	return array_merge( array_keys( sgc_data( 'post-types' ) ), array( 'post' ) );
}

/**
 * Các khóa trường có giá trị định dạng sẵn (khớp với sgc_meta_value), kèm tên và gợi ý khi còn trống
 * – gợi ý hiện chữ xám trong trình soạn thảo, không xuất hiện ngoài website.
 *
 * @return array key => array( name, empty )
 */
function sgc_meta_display_fields() {
	return array(
		'price'       => array( __( 'Giá phòng', 'sunny-grove-core' ), __( 'Chưa nhập giá phòng', 'sunny-grove-core' ) ),
		'adults'      => array( __( 'Số người lớn', 'sunny-grove-core' ), __( 'Chưa nhập số người lớn', 'sunny-grove-core' ) ),
		'children'    => array( __( 'Số trẻ em', 'sunny-grove-core' ), __( 'Chưa nhập số trẻ em', 'sunny-grove-core' ) ),
		'guests'      => array( __( 'Số khách', 'sunny-grove-core' ), __( 'Chưa nhập số khách', 'sunny-grove-core' ) ),
		'area'        => array( __( 'Diện tích', 'sunny-grove-core' ), __( 'Chưa nhập diện tích', 'sunny-grove-core' ) ),
		'bed'         => array( __( 'Giường', 'sunny-grove-core' ), __( 'Chưa nhập loại giường', 'sunny-grove-core' ) ),
		'view'        => array( __( 'Hướng nhìn', 'sunny-grove-core' ), __( 'Chưa nhập hướng nhìn', 'sunny-grove-core' ) ),
		'hours'       => array( __( 'Giờ hoạt động', 'sunny-grove-core' ), __( 'Chưa nhập giờ hoạt động', 'sunny-grove-core' ) ),
		'hours_line'  => array( __( 'Giờ hoạt động', 'sunny-grove-core' ), __( 'Chưa nhập giờ hoạt động', 'sunny-grove-core' ) ),
		'short'       => array( __( 'Mô tả ngắn', 'sunny-grove-core' ), __( 'Chưa nhập mô tả ngắn', 'sunny-grove-core' ) ),
		'excerpt'     => array( __( 'Tóm tắt', 'sunny-grove-core' ), __( 'Chưa nhập tóm tắt', 'sunny-grove-core' ) ),
		'compare'     => array( __( 'Câu so sánh', 'sunny-grove-core' ), __( 'Chưa nhập câu so sánh', 'sunny-grove-core' ) ),
		'distance'    => array( __( 'Khoảng cách', 'sunny-grove-core' ), __( 'Chưa nhập khoảng cách', 'sunny-grove-core' ) ),
		'travel'      => array( __( 'Thời gian di chuyển', 'sunny-grove-core' ), __( 'Chưa nhập thời gian di chuyển', 'sunny-grove-core' ) ),
		'offer_price' => array( __( 'Giá ưu đãi', 'sunny-grove-core' ), __( 'Chưa nhập giá ưu đãi', 'sunny-grove-core' ) ),
		'old_price'   => array( __( 'Giá cũ', 'sunny-grove-core' ), __( 'Chưa nhập giá cũ', 'sunny-grove-core' ) ),
		'badge'       => array( __( 'Nhãn nổi bật', 'sunny-grove-core' ), __( 'Chưa nhập nhãn nổi bật', 'sunny-grove-core' ) ),
		'valid_until' => array( __( 'Hạn ưu đãi', 'sunny-grove-core' ), __( 'Chưa nhập ngày hết hạn', 'sunny-grove-core' ) ),
		'conditions'  => array( __( 'Điều kiện', 'sunny-grove-core' ), __( 'Chưa nhập điều kiện', 'sunny-grove-core' ) ),
		'map_url'     => array( __( 'Link bản đồ', 'sunny-grove-core' ), __( 'Chưa nhập link bản đồ', 'sunny-grove-core' ) ),
	);
}

/**
 * Giá trị đã định dạng của mọi trường, theo ngôn ngữ của chính bài đó.
 *
 * @param int $post_id ID bài.
 * @return array
 */
function sgc_meta_display( $post_id ) {
	return sgc_with_lang(
		sgc_post_lang( $post_id ),
		static function () use ( $post_id ) {
			$out = array();
			foreach ( array_keys( sgc_meta_display_fields() ) as $key ) {
				$out[ $key ] = html_entity_decode( sgc_meta_value( $post_id, $key ), ENT_QUOTES, 'UTF-8' );
			}
			return $out;
		}
	);
}

/**
 * Ngôn ngữ trình soạn thảo gửi kèm request (tham số sg_lang).
 *
 * @param WP_REST_Request $request Request.
 * @return string
 */
function sgc_request_lang( $request ) {
	$lang = sanitize_key( (string) $request->get_param( 'sg_lang' ) );
	return $lang && isset( sgc_languages()[ $lang ] ) ? $lang : '';
}

/**
 * Trường REST: sg_lang (ngôn ngữ của bài) và sg_display (giá trị đã định dạng).
 */
function sgc_register_editor_rest_fields() {
	$lang_types = array_merge( sgc_editor_lang_types(), array( 'page' ) );
	register_rest_field(
		$lang_types,
		'sg_lang',
		array(
			'get_callback' => static function ( $post ) {
				return sgc_post_lang( $post['id'] );
			},
			'schema'       => array(
				'type'     => 'string',
				'context'  => array( 'view', 'edit' ),
				'readonly' => true,
			),
		)
	);
	register_rest_field(
		array_keys( sgc_data( 'post-types' ) ),
		'sg_display',
		array(
			'get_callback' => static function ( $post ) {
				return sgc_meta_display( $post['id'] );
			},
			'schema'       => array(
				'type'     => 'object',
				'context'  => array( 'view', 'edit' ),
				'readonly' => true,
			),
		)
	);
}
add_action( 'rest_api_init', 'sgc_register_editor_rest_fields' );

/**
 * Danh sách bài qua REST: chỉ lấy bài thuộc ngôn ngữ sg_lang.
 *
 * @param array           $args    Tham số WP_Query.
 * @param WP_REST_Request $request Request.
 * @return array
 */
function sgc_rest_query_lang( $args, $request ) {
	$lang = sgc_request_lang( $request );
	if ( ! $lang || ! taxonomy_exists( 'language' ) ) {
		return $args;
	}
	$args['tax_query']   = isset( $args['tax_query'] ) ? (array) $args['tax_query'] : array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	$args['tax_query'][] = array(
		'taxonomy' => 'language',
		'field'    => 'slug',
		'terms'    => $lang,
	);
	return $args;
}
foreach ( sgc_editor_lang_types() as $sgc_type ) {
	add_filter( "rest_{$sgc_type}_query", 'sgc_rest_query_lang', 10, 2 );
}
unset( $sgc_type );

/**
 * Block hiển thị từ máy chủ (block-renderer) trong trình soạn thảo: dùng nhãn theo ngôn ngữ trang đang sửa.
 *
 * @param mixed           $response Phản hồi.
 * @param array           $handler  Handler.
 * @param WP_REST_Request $request  Request.
 * @return mixed
 */
function sgc_rest_lang_before( $response, $handler, $request ) {
	$lang = sgc_request_lang( $request );
	if ( $lang ) {
		$GLOBALS['sgc_lang_override']  = $lang;
		$GLOBALS['sgc_lang_switched'] = switch_to_locale( sgc_languages()[ $lang ]['locale'] );
	}
	return $response;
}
add_filter( 'rest_request_before_callbacks', 'sgc_rest_lang_before', 10, 3 );

/**
 * Trả lại ngôn ngữ sau request.
 *
 * @param mixed $response Phản hồi.
 * @return mixed
 */
function sgc_rest_lang_after( $response ) {
	if ( ! empty( $GLOBALS['sgc_lang_override'] ) ) {
		if ( ! empty( $GLOBALS['sgc_lang_switched'] ) ) {
			restore_previous_locale();
		}
		$GLOBALS['sgc_lang_override']  = '';
		$GLOBALS['sgc_lang_switched'] = false;
	}
	return $response;
}
add_filter( 'rest_request_after_callbacks', 'sgc_rest_lang_after' );

/**
 * Script trình soạn thảo: gắn ngôn ngữ trang đang sửa vào các request lấy bài.
 */
function sgc_enqueue_editor_lang() {
	if ( ! function_exists( 'pll_languages_list' ) ) {
		return;
	}
	global $wp_post_types;
	$bases = array();
	foreach ( sgc_editor_lang_types() as $type ) {
		if ( isset( $wp_post_types[ $type ] ) ) {
			$bases[] = $wp_post_types[ $type ]->rest_base ? $wp_post_types[ $type ]->rest_base : $type;
		}
	}
	// Ngôn ngữ ban đầu: bài đang mở, hoặc ngôn ngữ chọn khi tạo bản dịch mới.
	$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$lang    = $post_id ? sgc_post_lang( $post_id ) : '';
	if ( ! $lang && isset( $_GET['new_lang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$lang = sanitize_key( wp_unslash( $_GET['new_lang'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	wp_enqueue_script( 'sgc-editor-lang', SGC_URL . 'assets/editor-lang.js', array( 'wp-api-fetch', 'wp-url', 'wp-data', 'wp-core-data' ), SGC_VERSION, false );
	wp_add_inline_script(
		'sgc-editor-lang',
		'window.sgcEditorLang = ' . wp_json_encode(
			array(
				'bases'      => $bases,
				'translated' => array_merge( sgc_editor_lang_types(), array( 'page' ) ),
				'postId'     => $post_id,
				'lang'       => $lang,
				'default'    => sgc_default_lang(),
			)
		) . ';',
		'before'
	);
}
add_action( 'enqueue_block_editor_assets', 'sgc_enqueue_editor_lang', 5 );
