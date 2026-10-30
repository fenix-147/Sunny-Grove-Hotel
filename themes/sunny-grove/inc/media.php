<?php
/**
 * Ảnh/video cho các pattern.
 *
 * Pattern không chứa ảnh cố định: theme hỏi plugin (qua bộ lọc "sunny_grove_media") xem có ảnh mẫu
 * trong Thư viện Media hay không; nếu không có, dùng ảnh giữ chỗ nhẹ để chủ web tự thay.
 *
 * @package SunnyGrove
 */

defined( 'ABSPATH' ) || exit;

/**
 * Lấy ảnh/video theo khóa.
 *
 * @param string $key Khóa (ví dụ "hero", "video:home").
 * @return array { id:int, url:string }
 */
function sunny_grove_media( $key ) {
	$default = array(
		'id'  => 0,
		'url' => 0 === strpos( $key, 'video:' ) ? '' : get_template_directory_uri() . '/assets/images/placeholder.svg',
	);
	$media   = apply_filters( 'sunny_grove_media', null, $key );
	return is_array( $media ) && ! empty( $media['url'] ) ? array_merge( $default, $media ) : $default;
}

/**
 * Mã JSON an toàn để đặt trong thuộc tính block của pattern.
 *
 * @param array $attrs Thuộc tính.
 * @return string
 */
function sunny_grove_attrs( array $attrs ) {
	return serialize_block_attributes( $attrs );
}
