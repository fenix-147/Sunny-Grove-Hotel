<?php
/**
 * Loại nội dung: Phòng, Tiện ích, Địa điểm, Ưu đãi và các nhóm phân loại.
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Đường dẫn (slug) của từng loại nội dung, đọc từ data/post-types.json.
 *
 * @param string $type Tên loại.
 * @return string
 */
function sgc_type_slug( $type ) {
	$data = sgc_data( 'post-types' );
	$slug = isset( $data[ $type ]['slug'] ) ? $data[ $type ]['slug'] : $type;
	return apply_filters( 'sgc_type_slug', $slug, $type );
}

/**
 * Đăng ký loại nội dung và phân loại.
 */
function sgc_register_post_types() {
	$common = array(
		'public'       => true,
		'show_in_rest' => true,
		'has_archive'  => false,
		'map_meta_cap' => true,
	);

	register_post_type(
		'sg_room',
		array_merge(
			$common,
			array(
				'labels'        => array(
					'name'          => __( 'Phòng', 'sunny-grove-core' ),
					'singular_name' => __( 'Phòng', 'sunny-grove-core' ),
					'add_new'       => __( 'Thêm phòng', 'sunny-grove-core' ),
					'add_new_item'  => __( 'Thêm phòng mới', 'sunny-grove-core' ),
					'edit_item'     => __( 'Sửa phòng', 'sunny-grove-core' ),
					'all_items'     => __( 'Tất cả phòng', 'sunny-grove-core' ),
					'search_items'  => __( 'Tìm phòng', 'sunny-grove-core' ),
					'menu_name'     => __( 'Phòng', 'sunny-grove-core' ),
				),
				'menu_icon'     => 'dashicons-admin-home',
				'menu_position' => 21,
				'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'custom-fields', 'revisions' ),
				'rewrite'       => array( 'slug' => sgc_type_slug( 'sg_room' ), 'with_front' => false ),
			)
		)
	);

	register_post_type(
		'sg_amenity',
		array_merge(
			$common,
			array(
				'labels'        => array(
					'name'          => __( 'Tiện ích', 'sunny-grove-core' ),
					'singular_name' => __( 'Tiện ích', 'sunny-grove-core' ),
					'add_new'       => __( 'Thêm tiện ích', 'sunny-grove-core' ),
					'add_new_item'  => __( 'Thêm tiện ích mới', 'sunny-grove-core' ),
					'edit_item'     => __( 'Sửa tiện ích', 'sunny-grove-core' ),
					'all_items'     => __( 'Tất cả tiện ích', 'sunny-grove-core' ),
					'menu_name'     => __( 'Tiện ích', 'sunny-grove-core' ),
				),
				'menu_icon'     => 'dashicons-palmtree',
				'menu_position' => 22,
				'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'custom-fields', 'revisions' ),
				'rewrite'       => array( 'slug' => sgc_type_slug( 'sg_amenity' ), 'with_front' => false ),
			)
		)
	);

	register_post_type(
		'sg_place',
		array_merge(
			$common,
			array(
				'labels'        => array(
					'name'          => __( 'Địa điểm', 'sunny-grove-core' ),
					'singular_name' => __( 'Địa điểm', 'sunny-grove-core' ),
					'add_new'       => __( 'Thêm địa điểm', 'sunny-grove-core' ),
					'add_new_item'  => __( 'Thêm địa điểm mới', 'sunny-grove-core' ),
					'edit_item'     => __( 'Sửa địa điểm', 'sunny-grove-core' ),
					'all_items'     => __( 'Tất cả địa điểm', 'sunny-grove-core' ),
					'menu_name'     => __( 'Khám phá Phú Quốc', 'sunny-grove-core' ),
				),
				'menu_icon'     => 'dashicons-location-alt',
				'menu_position' => 23,
				'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'custom-fields', 'revisions' ),
				'rewrite'       => array( 'slug' => sgc_type_slug( 'sg_place' ), 'with_front' => false ),
			)
		)
	);

	register_post_type(
		'sg_offer',
		array_merge(
			$common,
			array(
				'labels'        => array(
					'name'          => __( 'Ưu đãi', 'sunny-grove-core' ),
					'singular_name' => __( 'Ưu đãi', 'sunny-grove-core' ),
					'add_new'       => __( 'Thêm ưu đãi', 'sunny-grove-core' ),
					'add_new_item'  => __( 'Thêm ưu đãi mới', 'sunny-grove-core' ),
					'edit_item'     => __( 'Sửa ưu đãi', 'sunny-grove-core' ),
					'all_items'     => __( 'Tất cả ưu đãi', 'sunny-grove-core' ),
					'menu_name'     => __( 'Ưu đãi', 'sunny-grove-core' ),
				),
				'menu_icon'     => 'dashicons-tickets-alt',
				'menu_position' => 24,
				'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'custom-fields', 'revisions' ),
				'rewrite'       => array( 'slug' => sgc_type_slug( 'sg_offer' ), 'with_front' => false ),
			)
		)
	);

	// Phân loại dạng ô tích (hierarchical) để người dùng chọn bằng checkbox.
	// public = true để WordPress gắn class "{taxonomy}-{slug}" cho thẻ, phục vụ bộ lọc nhanh.
	$tax_common = array(
		'public'             => true,
		'publicly_queryable' => false,
		'rewrite'            => false,
		'hierarchical'       => true,
		'show_in_rest'       => true,
		'show_admin_column'  => true,
		'show_tagcloud'      => false,
	);

	register_taxonomy(
		'sg_guest_type',
		array( 'sg_room', 'sg_offer' ),
		array_merge(
			$tax_common,
			array(
				'labels' => array(
					'name'          => __( 'Nhóm khách', 'sunny-grove-core' ),
					'singular_name' => __( 'Nhóm khách', 'sunny-grove-core' ),
					'add_new_item'  => __( 'Thêm nhóm khách', 'sunny-grove-core' ),
				),
			)
		)
	);

	register_taxonomy(
		'sg_room_feature',
		array( 'sg_room' ),
		array_merge(
			$tax_common,
			array(
				'show_admin_column' => false,
				'labels'            => array(
					'name'          => __( 'Tiện nghi trong phòng', 'sunny-grove-core' ),
					'singular_name' => __( 'Tiện nghi', 'sunny-grove-core' ),
					'add_new_item'  => __( 'Thêm tiện nghi', 'sunny-grove-core' ),
				),
			)
		)
	);

	register_taxonomy(
		'sg_place_tag',
		array( 'sg_place' ),
		array_merge(
			$tax_common,
			array(
				'labels' => array(
					'name'          => __( 'Nhãn lọc địa điểm', 'sunny-grove-core' ),
					'singular_name' => __( 'Nhãn lọc', 'sunny-grove-core' ),
					'add_new_item'  => __( 'Thêm nhãn lọc', 'sunny-grove-core' ),
				),
			)
		)
	);
}
add_action( 'init', 'sgc_register_post_types', 5 );

/**
 * Cột "Thứ tự" giúp sắp xếp phòng/tiện ích/địa điểm trực quan trong danh sách quản trị.
 */
function sgc_admin_columns() {
	foreach ( array( 'sg_room', 'sg_amenity', 'sg_place', 'sg_offer' ) as $type ) {
		add_filter(
			"manage_{$type}_posts_columns",
			static function ( $cols ) use ( $type ) {
				$new = array();
				foreach ( $cols as $key => $label ) {
					if ( 'title' === $key ) {
						$new['sgc_thumb'] = __( 'Ảnh', 'sunny-grove-core' );
					}
					$new[ $key ] = $label;
					if ( 'title' === $key ) {
						$new['sgc_summary'] = __( 'Thông tin chính', 'sunny-grove-core' );
						$new['sgc_order']   = __( 'Thứ tự', 'sunny-grove-core' );
					}
				}
				return $new;
			}
		);
		add_action(
			"manage_{$type}_posts_custom_column",
			static function ( $col, $post_id ) {
				if ( 'sgc_thumb' === $col ) {
					echo get_the_post_thumbnail( $post_id, array( 60, 60 ) );
				} elseif ( 'sgc_order' === $col ) {
					echo (int) get_post_field( 'menu_order', $post_id );
				} elseif ( 'sgc_summary' === $col ) {
					echo esc_html( sgc_admin_summary( $post_id ) );
				}
			},
			10,
			2
		);
	}
}
add_action( 'admin_init', 'sgc_admin_columns' );

/**
 * Tóm tắt các trường chính để hiển thị ở danh sách quản trị.
 *
 * @param int $post_id ID.
 * @return string
 */
function sgc_admin_summary( $post_id ) {
	$type  = get_post_type( $post_id );
	$parts = array();
	switch ( $type ) {
		case 'sg_room':
			$parts[] = sgc_meta_value( $post_id, 'price' );
			$parts[] = sgc_meta_value( $post_id, 'guests' );
			$parts[] = sgc_meta_value( $post_id, 'area' );
			break;
		case 'sg_amenity':
			$parts[] = sgc_meta_value( $post_id, 'hours' );
			break;
		case 'sg_place':
			$parts[] = sgc_meta_value( $post_id, 'distance' );
			$parts[] = sgc_meta_value( $post_id, 'travel' );
			break;
		case 'sg_offer':
			$parts[] = sgc_meta_value( $post_id, 'offer_price' );
			$parts[] = sgc_meta_value( $post_id, 'valid_until' );
			break;
	}
	if ( get_post_meta( $post_id, '_sgc_demo', true ) ) {
		$parts[] = '[CẦN THAY]';
	}
	return implode( ' · ', array_filter( $parts ) );
}
