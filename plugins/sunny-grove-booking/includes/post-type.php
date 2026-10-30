<?php
/**
 * Loại nội dung "Yêu cầu đặt phòng" (chỉ hiện trong trang quản trị).
 *
 * @package SunnyGroveBooking
 */

defined( 'ABSPATH' ) || exit;

const SGB_CAPS_VERSION = 1;

/**
 * Trạng thái xử lý yêu cầu.
 *
 * @return array
 */
function sgb_statuses() {
	return array(
		'new'       => __( 'Mới', 'sunny-grove-booking' ),
		'contacted' => __( 'Đã liên hệ', 'sunny-grove-booking' ),
		'confirmed' => __( 'Đã xác nhận', 'sunny-grove-booking' ),
		'cancelled' => __( 'Đã hủy', 'sunny-grove-booking' ),
	);
}

/**
 * Các trường lưu cho mỗi yêu cầu.
 *
 * @return array khóa => nhãn quản trị
 */
function sgb_request_fields() {
	return array(
		'checkin'        => __( 'Ngày nhận phòng', 'sunny-grove-booking' ),
		'checkout'       => __( 'Ngày trả phòng', 'sunny-grove-booking' ),
		'room_id'        => __( 'Loại phòng', 'sunny-grove-booking' ),
		'adults'         => __( 'Người lớn', 'sunny-grove-booking' ),
		'children'       => __( 'Trẻ em', 'sunny-grove-booking' ),
		'name'           => __( 'Họ tên', 'sunny-grove-booking' ),
		'phone'          => __( 'Số điện thoại', 'sunny-grove-booking' ),
		'contact_method' => __( 'Liên hệ qua', 'sunny-grove-booking' ),
		'contact_id'     => __( 'Số Zalo/WhatsApp', 'sunny-grove-booking' ),
		'email'          => __( 'Email', 'sunny-grove-booking' ),
		'note'           => __( 'Ghi chú của khách', 'sunny-grove-booking' ),
	);
}

/**
 * Đăng ký loại nội dung.
 */
function sgb_register_post_type() {
	register_post_type(
		'sgb_request',
		array(
			'labels'              => array(
				'name'               => __( 'Yêu cầu đặt phòng', 'sunny-grove-booking' ),
				'singular_name'      => __( 'Yêu cầu đặt phòng', 'sunny-grove-booking' ),
				'menu_name'          => __( 'Đặt phòng', 'sunny-grove-booking' ),
				'all_items'          => __( 'Tất cả yêu cầu', 'sunny-grove-booking' ),
				'add_new'            => __( 'Thêm yêu cầu', 'sunny-grove-booking' ),
				'add_new_item'       => __( 'Thêm yêu cầu (đặt qua điện thoại)', 'sunny-grove-booking' ),
				'edit_item'          => __( 'Chi tiết yêu cầu', 'sunny-grove-booking' ),
				'search_items'       => __( 'Tìm yêu cầu', 'sunny-grove-booking' ),
				'not_found'          => __( 'Chưa có yêu cầu nào.', 'sunny-grove-booking' ),
				'not_found_in_trash' => __( 'Thùng rác trống.', 'sunny-grove-booking' ),
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => false,
			'menu_position'       => 4,
			'menu_icon'           => 'dashicons-calendar-alt',
			'supports'            => false,
			'capability_type'     => array( 'sgb_request', 'sgb_requests' ),
			'map_meta_cap'        => true,
			'rewrite'             => false,
			'query_var'           => false,
		)
	);
}
add_action( 'init', 'sgb_register_post_type' );

/**
 * Cấp quyền quản lý yêu cầu cho mọi vai trò có quyền sửa bài của người khác (Quản trị viên, Biên tập viên, Biên tập viên khách sạn).
 */
function sgb_grant_caps() {
	$caps = array( 'edit_sgb_requests', 'edit_others_sgb_requests', 'publish_sgb_requests', 'read_private_sgb_requests', 'delete_sgb_requests', 'delete_others_sgb_requests', 'delete_published_sgb_requests', 'edit_published_sgb_requests', 'delete_private_sgb_requests', 'edit_private_sgb_requests', 'manage_sgb_settings' );
	foreach ( wp_roles()->role_objects as $role ) {
		if ( $role->has_cap( 'edit_others_posts' ) ) {
			foreach ( $caps as $cap ) {
				$role->add_cap( $cap );
			}
		}
	}
	update_option( 'sgb_caps_version', SGB_CAPS_VERSION . '-' . count( wp_roles()->role_objects ) );
}

/**
 * Cấp lại quyền khi có vai trò mới (ví dụ vai trò do plugin Sunny Grove Core tạo).
 */
function sgb_maybe_grant_caps() {
	if ( get_option( 'sgb_caps_version' ) !== SGB_CAPS_VERSION . '-' . count( wp_roles()->role_objects ) ) {
		sgb_grant_caps();
	}
}
add_action( 'admin_init', 'sgb_maybe_grant_caps' );

/**
 * Đọc một trường của yêu cầu.
 *
 * @param int    $post_id ID.
 * @param string $key     Khóa.
 * @return string
 */
function sgb_get( $post_id, $key ) {
	return (string) get_post_meta( $post_id, '_sgb_' . $key, true );
}

/**
 * Mã yêu cầu dễ đọc: SG + năm + ID.
 *
 * @param int $post_id ID.
 * @return string
 */
function sgb_request_code( $post_id ) {
	return 'SG' . get_the_date( 'y', $post_id ) . str_pad( (string) $post_id, 4, '0', STR_PAD_LEFT );
}

/**
 * Tên loại phòng đã lưu (giữ tên lúc khách gửi, kể cả khi phòng bị xóa sau đó).
 *
 * @param int    $post_id ID yêu cầu.
 * @param string $lang    Ngôn ngữ để hiện chữ "chưa chọn".
 * @return string
 */
function sgb_room_label( $post_id, $lang = '' ) {
	$title = sgb_get( $post_id, 'room_title' );
	if ( $title ) {
		return $title;
	}
	return sgb_text( 'room_any', $lang ? $lang : sgb_get( $post_id, 'lang' ) );
}

/**
 * Số đêm.
 *
 * @param string $checkin  Y-m-d.
 * @param string $checkout Y-m-d.
 * @return int
 */
function sgb_nights( $checkin, $checkout ) {
	$in  = DateTime::createFromFormat( '!Y-m-d', $checkin );
	$out = DateTime::createFromFormat( '!Y-m-d', $checkout );
	return ( $in && $out ) ? (int) $in->diff( $out )->format( '%r%a' ) : 0;
}
