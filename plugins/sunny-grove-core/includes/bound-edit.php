<?php
/**
 * Sửa chữ được nối dữ liệu (Block Bindings) ngay trong trình soạn thảo.
 *
 * - Chữ lấy từ Thông tin khách sạn (Slogan, Tên khách sạn…): người có quyền sửa cài đặt Sunny Grove gõ thẳng vào
 *   block; khi bấm Lưu, chữ được lưu ngược về ô cài đặt đúng ngôn ngữ đang gạt (VI | EN).
 * - Mọi block nối dữ liệu đều có khung "Nội dung này lấy từ … – sửa tại đây" và viền nét đứt.
 *
 * Dữ liệu trao đổi qua REST: GET/POST /sunny-grove/v1/hotel-text với khóa dạng "<trường>__<ngôn ngữ>".
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Các trường chữ của Thông tin khách sạn sửa được tại chỗ.
 *
 * @return array khóa => định nghĩa trường
 */
function sgc_bound_editable_fields() {
	$fields = sgc_data( 'hotel' )['i18n'] ?? array();
	unset( $fields['keep_phrases'] );
	return array_filter(
		$fields,
		static function ( $field ) {
			return in_array( $field['type'] ?? 'text', array( 'text', 'textarea' ), true );
		}
	);
}

/**
 * Dữ liệu trả về cho trình soạn thảo.
 *
 * @return array
 */
function sgc_bound_hotel_text() {
	$out = array();
	foreach ( array_keys( sgc_bound_editable_fields() ) as $key ) {
		foreach ( array_keys( sg_languages() ) as $lang ) {
			$out[ $key . '__' . $lang ] = sgc_hotel( $key, $lang );
		}
	}
	return $out;
}

/**
 * Đăng ký đường dẫn REST.
 */
function sgc_bound_rest_routes() {
	register_rest_route(
		'sunny-grove/v1',
		'/hotel-text',
		array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => static function () {
					return rest_ensure_response( sgc_bound_hotel_text() );
				},
				'permission_callback' => static function () {
					return current_user_can( 'manage_sunny_grove' );
				},
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => 'sgc_bound_rest_save',
				'permission_callback' => static function () {
					return current_user_can( 'manage_sunny_grove' );
				},
			),
		)
	);
}
add_action( 'rest_api_init', 'sgc_bound_rest_routes' );

/**
 * Lưu chữ sửa từ trình soạn thảo vào Thông tin khách sạn.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function sgc_bound_rest_save( WP_REST_Request $request ) {
	$fields = sgc_bound_editable_fields();
	$params = (array) $request->get_json_params();
	$i18n   = (array) get_option( 'sgc_hotel_i18n', array() );
	$saved  = false;
	foreach ( $fields as $key => $field ) {
		foreach ( array_keys( sg_languages() ) as $lang ) {
			$name = $key . '__' . $lang;
			if ( ! array_key_exists( $name, $params ) || ! is_scalar( $params[ $name ] ) ) {
				continue;
			}
			$value = html_entity_decode( wp_strip_all_tags( (string) $params[ $name ] ), ENT_QUOTES, 'UTF-8' );
			$value = str_replace( "\u{00A0}", ' ', $value );
			$i18n[ $lang ][ $key ] = sgc_sanitize_setting( $value, $field );
			$saved                 = true;
		}
	}
	if ( $saved ) {
		update_option( 'sgc_hotel_i18n', $i18n );
		do_action( 'sgc_settings_saved', 'hotel' );
	}
	return rest_ensure_response( sgc_bound_hotel_text() );
}

/**
 * Thông tin cho script trình soạn thảo: quyền, tên trường, link tới ô cần sửa.
 *
 * @return array
 */
function sgc_bound_editor_config() {
	$hotel  = sgc_data( 'hotel' );
	$labels = sgc_data( 'labels' );
	$names  = array(
		'hotel' => array(),
		'label' => array(),
	);
	foreach ( array( 'i18n', 'general' ) as $group ) {
		foreach ( $hotel[ $group ] as $key => $field ) {
			$names['hotel'][ $key ] = $field['label'];
		}
	}
	foreach ( $labels['fields'] as $key => $field ) {
		$names['label'][ $key ] = $field['label'];
	}
	return array(
		'canEdit'  => current_user_can( 'manage_sunny_grove' ),
		'editable' => array_keys( sgc_bound_editable_fields() ),
		'names'    => $names,
		// Khóa link dựng sẵn → ô cài đặt gốc.
		'alias'    => array(
			'tel_url'      => 'phone',
			'mailto_url'   => 'email',
			'zalo_url'     => 'zalo',
			'whatsapp_url' => 'whatsapp',
			'booking_url'  => 'booking_page',
		),
		'urls'     => array(
			'hotel' => admin_url( 'admin.php?page=sgc-hotel' ),
			'label' => admin_url( 'admin.php?page=sgc-labels' ),
			'post'  => admin_url( 'post.php?action=edit&post=' ),
		),
		'labels'   => array(
			'panel'       => __( 'Nội dung nối dữ liệu', 'sunny-grove-core' ),
			'hotelPlace'  => __( 'Sunny Grove → Thông tin khách sạn', 'sunny-grove-core' ),
			'labelPlace'  => __( 'Sunny Grove → Nhãn hiển thị', 'sunny-grove-core' ),
			'metaPlace'   => __( 'hộp thông tin của bài', 'sunny-grove-core' ),
			'metaHere'    => __( 'hộp thông tin ở cuối trang sửa bài này', 'sunny-grove-core' ),
			/* translators: 1: tên trường, 2: nơi sửa */
			'from'        => __( 'Nội dung này lấy từ “%1$s” (%2$s) – sửa tại đây.', 'sunny-grove-core' ),
			/* translators: 1: tên trường, 2: ngôn ngữ */
			'editable'    => __( 'Bạn có thể gõ trực tiếp vào khối này. Khi bấm “Lưu”, ô “%1$s” (%2$s) trong cài đặt sẽ được cập nhật theo.', 'sunny-grove-core' ),
			'noRight'     => __( 'Bạn không có quyền sửa cài đặt khách sạn nên không gõ trực tiếp được – nhờ quản trị viên sửa tại link dưới đây.', 'sunny-grove-core' ),
			'open'        => __( 'Mở chỗ sửa', 'sunny-grove-core' ),
			'toolbar'     => __( 'Nội dung lấy từ cài đặt – mở chỗ sửa', 'sunny-grove-core' ),
			'entity'      => __( 'Thông tin khách sạn (Sunny Grove)', 'sunny-grove-core' ),
			'vi'          => __( 'tiếng Việt', 'sunny-grove-core' ),
			'en'          => __( 'tiếng Anh', 'sunny-grove-core' ),
		),
	);
}

/**
 * Viền nét đứt cho block nối dữ liệu (trong khung soạn thảo).
 */
function sgc_bound_canvas_style() {
	if ( is_admin() ) {
		wp_enqueue_style( 'sgc-bound-canvas', SGC_URL . 'assets/bindings-editor.css', array(), SGC_VERSION );
	}
}
add_action( 'enqueue_block_assets', 'sgc_bound_canvas_style' );
