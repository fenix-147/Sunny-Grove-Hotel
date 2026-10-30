<?php
/**
 * Block Bindings: nối chữ/nút trong block với dữ liệu (nhãn, thông tin khách sạn, trường tùy chỉnh).
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Định dạng số theo ngôn ngữ (bỏ phần thập phân nếu là số nguyên).
 *
 * @param float $number Số.
 * @return string
 */
function sgc_number( $number ) {
	$number   = (float) $number;
	$decimals = floor( $number ) === $number ? 0 : 1;
	return number_format_i18n( $number, $decimals );
}

/**
 * Giá trị đã định dạng của một trường cho bài viết.
 *
 * @param int    $post_id ID bài.
 * @param string $key     Khóa logic.
 * @return string
 */
function sgc_meta_value( $post_id, $key ) {
	$post_id = (int) $post_id;
	if ( ! $post_id ) {
		return '';
	}
	// Trường chữ lấy bản theo ngôn ngữ đang xem (chưa dịch thì dùng tiếng Việt); số liệu dùng chung.
	$get = static function ( $meta ) use ( $post_id ) {
		return sg_meta_tr( $post_id, $meta );
	};

	switch ( $key ) {
		case 'price':
			$price = $get( 'sg_price_from' );
			return '' !== $price ? sgc_label( 'price_format', array( 'gia' => sgc_number( $price ) ) ) : '';
		case 'adults':
			$n = $get( 'sg_adults' );
			return '' !== $n ? sgc_label( 'adults', array( 'n' => (int) $n ) ) : '';
		case 'children':
			$n = $get( 'sg_children' );
			return $n ? sgc_label( 'children', array( 'n' => (int) $n ) ) : '';
		case 'guests':
			return implode( ' + ', array_filter( array( sgc_meta_value( $post_id, 'adults' ), sgc_meta_value( $post_id, 'children' ) ) ) );
		case 'area':
			$n = $get( 'sg_area' );
			return '' !== $n ? sgc_label( 'area', array( 'n' => sgc_number( $n ) ) ) : '';
		case 'bed':
			return (string) $get( 'sg_bed' );
		case 'view':
			return (string) $get( 'sg_view' );
		case 'hours':
			return (string) $get( 'sg_hours' );
		case 'hours_line':
			$hours = $get( 'sg_hours' );
			return $hours ? sgc_label( 'hours' ) . ': ' . $hours : '';
		case 'short':
			$short = $get( 'sg_short' );
			return $short ? $short : get_the_excerpt( $post_id );
		case 'excerpt':
			return get_the_excerpt( $post_id );
		case 'compare':
			return (string) $get( 'sg_compare' );
		case 'map_url':
			return (string) $get( 'sg_map_url' );
		case 'distance':
			$km = $get( 'sg_distance' );
			return '' !== $km ? sgc_label( 'distance', array( 'n' => sgc_number( $km ) ) ) : '';
		case 'travel':
			$min  = $get( 'sg_time' );
			$mode = $get( 'sg_mode' );
			$text = '' !== $min ? sgc_label( 'minutes', array( 'n' => (int) $min ) ) : '';
			if ( $mode ) {
				$text = trim( $text . ' ' . sgc_label( 'mode_' . $mode ) );
			}
			return $text;
		case 'offer_price':
			return (string) $get( 'sg_price' );
		case 'old_price':
			return (string) $get( 'sg_old_price' );
		case 'badge':
			return (string) $get( 'sg_badge' );
		case 'valid_until':
			$date = $get( 'sg_valid_to' );
			if ( ! $date ) {
				return '';
			}
			$time = strtotime( $date . ' 12:00:00' );
			return sgc_label( 'offer_until', array( 'ngay' => wp_date( sgc_label( 'date_format' ), $time ) ) );
		case 'conditions':
			return (string) $get( 'sg_conditions' );
		case 'permalink':
			return (string) get_permalink( $post_id );
		case 'book_url':
			$type = get_post_type( $post_id );
			if ( 'sg_room' === $type ) {
				return sgc_booking_url( array( 'room' => $post_id ) );
			}
			if ( 'sg_offer' === $type ) {
				return sgc_booking_url( array( 'note' => sgc_label( 'offer_note', array( 'ten' => get_the_title( $post_id ) ) ) ) );
			}
			return sgc_booking_url();
	}
	return '';
}

/**
 * Giá trị thông tin khách sạn, gồm cả các link dựng sẵn.
 *
 * @param string $key Khóa.
 * @return string
 */
function sgc_hotel_value( $key ) {
	switch ( $key ) {
		case 'tel_url':
			return 'tel:' . sgc_phone_digits( sgc_hotel( 'phone' ) );
		case 'mailto_url':
			return 'mailto:' . sgc_hotel( 'email' );
		case 'zalo_url':
			return 'https://zalo.me/' . ltrim( sgc_phone_digits( sgc_hotel( 'zalo' ) ), '+' );
		case 'whatsapp_url':
			return 'https://wa.me/' . sgc_phone_international( sgc_hotel( 'whatsapp' ) );
		case 'booking_url':
			return sgc_booking_url();
		case 'home_url':
			return home_url( '/' );
	}
	return (string) sgc_hotel( $key );
}

/**
 * Trả giá trị đã thoát ký tự phù hợp với thuộc tính đang được nối.
 *
 * @param string $value     Giá trị.
 * @param string $attribute Thuộc tính block.
 * @return string
 */
function sgc_binding_output( $value, $attribute ) {
	if ( in_array( $attribute, array( 'url', 'href' ), true ) ) {
		return esc_url( $value );
	}
	return esc_html( $value );
}

/**
 * Đăng ký các nguồn dữ liệu.
 */
function sgc_register_binding_sources() {
	if ( ! function_exists( 'register_block_bindings_source' ) ) {
		return;
	}
	register_block_bindings_source(
		'sunny-grove/label',
		array(
			'label'              => __( 'Sunny Grove – Nhãn hiển thị', 'sunny-grove-core' ),
			'get_value_callback' => static function ( $args, $block, $attribute ) {
				return sgc_binding_output( sgc_label( $args['key'] ?? '' ), $attribute );
			},
		)
	);
	register_block_bindings_source(
		'sunny-grove/hotel',
		array(
			'label'              => __( 'Sunny Grove – Thông tin khách sạn', 'sunny-grove-core' ),
			'get_value_callback' => static function ( $args, $block, $attribute ) {
				return sgc_binding_output( sgc_hotel_value( $args['key'] ?? '' ), $attribute );
			},
		)
	);
	register_block_bindings_source(
		'sunny-grove/meta',
		array(
			'label'              => __( 'Sunny Grove – Thông tin phòng / địa điểm / ưu đãi', 'sunny-grove-core' ),
			'uses_context'       => array( 'postId', 'postType' ),
			'get_value_callback' => static function ( $args, $block, $attribute ) {
				$post_id = ! empty( $block->context['postId'] ) ? (int) $block->context['postId'] : (int) get_the_ID();
				return sgc_binding_output( sgc_meta_value( $post_id, $args['key'] ?? '' ), $attribute );
			},
		)
	);
}
add_action( 'init', 'sgc_register_binding_sources' );

/**
 * Trong trình soạn thảo, hiển thị nhãn dễ hiểu thay vì ô trống cho các block đã nối dữ liệu.
 */
function sgc_binding_editor_script() {
	$schema = sgc_data( 'labels' );
	$labels = array();
	$hotel  = array();
	// Nhãn và thông tin khách sạn cho từng ngôn ngữ: trình soạn thảo chọn theo ngôn ngữ của trang đang sửa.
	foreach ( array_keys( sg_languages() ) as $lang ) {
		foreach ( array_keys( $schema['fields'] ) as $key ) {
			$labels[ $lang ][ $key ] = sgc_label( $key, array(), $lang );
		}
		foreach ( array( 'phone', 'email', 'address', 'name', 'slogan', 'checkin' ) as $key ) {
			$hotel[ $lang ][ $key ] = sgc_hotel( $key, $lang );
		}
	}
	wp_add_inline_script(
		'sgc-bindings-editor',
		'window.sgcBindingPreview = ' . wp_json_encode(
			array(
				'labels'  => $labels,
				'hotel'   => $hotel,
				'fields'  => sgc_meta_display_fields(),
				'default' => sg_default_lang(),
			)
		) . ';',
		'before'
	);
}

/**
 * Đăng ký script hiển thị nguồn dữ liệu trong trình soạn thảo.
 */
function sgc_enqueue_binding_editor() {
	wp_enqueue_script( 'sgc-bindings-editor', SGC_URL . 'assets/bindings-editor.js', array( 'wp-blocks', 'wp-data', 'wp-core-data', 'wp-hooks', 'wp-i18n', 'sg-editor-i18n' ), SGC_VERSION, true );
	sgc_binding_editor_script();
}
add_action( 'enqueue_block_editor_assets', 'sgc_enqueue_binding_editor' );
