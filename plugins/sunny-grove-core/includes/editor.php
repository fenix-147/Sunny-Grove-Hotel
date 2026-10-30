<?php
/**
 * Trình soạn thảo hiển thị giống ngoài website:
 * - Trường phòng/địa điểm/ưu đãi hiện giá trị đã định dạng (giá, ngày…) thay vì số thô, cho cả hai ngôn ngữ
 *   (trường REST sg_display) – nút gạt VI | EN đổi qua lại ngay, không cần tải lại.
 * - Block hiển thị từ máy chủ dùng nhãn theo ngôn ngữ đang gạt (tham số sg_lang).
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

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
 * Giá trị đã định dạng của mọi trường (kèm tiêu đề, tóm tắt) cho từng ngôn ngữ.
 *
 * @param int $post_id ID bài.
 * @return array lang => key => giá trị
 */
function sgc_meta_display( $post_id ) {
	$out = array();
	foreach ( array_keys( sg_languages() ) as $lang ) {
		$out[ $lang ] = sg_with_lang(
			$lang,
			static function () use ( $post_id ) {
				$values = array(
					'title'   => html_entity_decode( get_the_title( $post_id ), ENT_QUOTES, 'UTF-8' ),
					'excerpt' => html_entity_decode( get_the_excerpt( $post_id ), ENT_QUOTES, 'UTF-8' ),
				);
				foreach ( array_keys( sgc_meta_display_fields() ) as $key ) {
					$values[ $key ] = html_entity_decode( sgc_meta_value( $post_id, $key ), ENT_QUOTES, 'UTF-8' );
				}
				return $values;
			}
		);
	}
	return $out;
}

/**
 * Ngôn ngữ trình soạn thảo gửi kèm request (tham số sg_lang).
 *
 * @param WP_REST_Request $request Request.
 * @return string
 */
function sgc_request_lang( $request ) {
	$lang = sanitize_key( (string) $request->get_param( 'sg_lang' ) );
	return sg_is_lang( $lang ) ? $lang : '';
}

/**
 * Trường REST sg_display: giá trị đã định dạng theo từng ngôn ngữ.
 */
function sgc_register_editor_rest_fields() {
	register_rest_field(
		array_merge( array_keys( sgc_data( 'post-types' ) ), array( 'post', 'page' ) ),
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
		$GLOBALS['sg_lang_override']  = $lang;
		$GLOBALS['sgc_lang_switched'] = switch_to_locale( sg_languages()[ $lang ]['locale'] );
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
	if ( ! empty( $GLOBALS['sg_lang_override'] ) ) {
		if ( ! empty( $GLOBALS['sgc_lang_switched'] ) ) {
			restore_previous_locale();
		}
		$GLOBALS['sg_lang_override']  = '';
		$GLOBALS['sgc_lang_switched'] = false;
	}
	return $response;
}
add_filter( 'rest_request_after_callbacks', 'sgc_rest_lang_after' );
