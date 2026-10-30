<?php
/**
 * Tương thích Polylang: dịch được Phòng, Tiện ích, Địa điểm, Ưu đãi và các nhóm phân loại.
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Loại nội dung cần dịch.
 *
 * @param array $types Loại.
 * @return array
 */
function sgc_pll_post_types( $types ) {
	foreach ( array( 'sg_room', 'sg_amenity', 'sg_place', 'sg_offer' ) as $type ) {
		$types[ $type ] = $type;
	}
	return $types;
}
add_filter( 'pll_get_post_types', 'sgc_pll_post_types' );

/**
 * Phân loại cần dịch.
 *
 * @param array $taxonomies Phân loại.
 * @return array
 */
function sgc_pll_taxonomies( $taxonomies ) {
	foreach ( array( 'sg_guest_type', 'sg_room_feature', 'sg_place_tag' ) as $tax ) {
		$taxonomies[ $tax ] = $tax;
	}
	return $taxonomies;
}
add_filter( 'pll_get_taxonomies', 'sgc_pll_taxonomies' );

/**
 * Trường dùng chung giữa các bản dịch (số liệu, ảnh, link): tự sao chép khi tạo bản dịch
 * và tự đồng bộ khi sửa. Trường chữ (loại giường, view, mô tả…) được dịch riêng.
 *
 * @param array $keys Khóa meta.
 * @param bool  $sync True khi đồng bộ, false khi sao chép lúc tạo bản dịch.
 * @return array
 */
function sgc_pll_copy_metas( $keys, $sync ) {
	$shared = array( 'sg_price_from', 'sg_area', 'sg_adults', 'sg_children', 'sg_gallery', 'sg_video', 'sg_distance', 'sg_time', 'sg_mode', 'sg_map_url', 'sg_valid_from', 'sg_valid_to' );
	$texts  = array( 'sg_bed', 'sg_view', 'sg_short', 'sg_hours', 'sg_compare', 'sg_price', 'sg_old_price', 'sg_badge', 'sg_conditions' );
	if ( $sync ) {
		// Khi đồng bộ, Polylang mặc định đồng bộ mọi trường – bỏ các trường chữ để mỗi ngôn ngữ giữ bản dịch riêng.
		return array_values( array_diff( array_unique( array_merge( $keys, $shared ) ), $texts ) );
	}
	return array_values( array_unique( array_merge( $keys, $shared, $texts ) ) );
}
add_filter( 'pll_copy_post_metas', 'sgc_pll_copy_metas', 10, 2 );
