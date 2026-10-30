<?php
/**
 * Đánh giá khách hàng: quản lý trong trang quản trị (tên, nơi ở, số sao, nội dung VI/EN, nguồn),
 * hiển thị bằng block "Đánh giá khách hàng". Không nhúng widget bên ngoài.
 * Không xuất schema đánh giá: Google không chấp nhận khách sạn tự đánh dấu đánh giá về chính mình.
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Nguồn đánh giá.
 *
 * @return array
 */
function sg_review_sources() {
	return array(
		'google'      => __( 'Google', 'sunny-grove-core' ),
		'booking'     => __( 'Booking.com', 'sunny-grove-core' ),
		'tripadvisor' => __( 'TripAdvisor', 'sunny-grove-core' ),
		'direct'      => __( 'Khách gửi trực tiếp', 'sunny-grove-core' ),
	);
}

/**
 * Loại nội dung "Đánh giá".
 */
function sg_register_review_type() {
	register_post_type(
		'sg_review',
		array(
			'labels'              => array(
				'name'          => __( 'Đánh giá', 'sunny-grove-core' ),
				'singular_name' => __( 'Đánh giá', 'sunny-grove-core' ),
				'add_new'       => __( 'Thêm đánh giá', 'sunny-grove-core' ),
				'add_new_item'  => __( 'Thêm đánh giá mới', 'sunny-grove-core' ),
				'edit_item'     => __( 'Sửa đánh giá', 'sunny-grove-core' ),
				'all_items'     => __( 'Tất cả đánh giá', 'sunny-grove-core' ),
				'menu_name'     => __( 'Đánh giá', 'sunny-grove-core' ),
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'menu_position'       => 25,
			'menu_icon'           => 'dashicons-star-filled',
			'supports'            => array( 'title', 'page-attributes' ),
			'map_meta_cap'        => true,
			'rewrite'             => false,
			'query_var'           => false,
		)
	);
}
add_action( 'init', 'sg_register_review_type', 5 );

/**
 * Trường của đánh giá (dùng chung khung nhập liệu với Phòng/Ưu đãi: trường chữ có 2 ô VI | EN).
 *
 * @param array $fields Trường theo loại nội dung.
 * @return array
 */
function sg_review_fields( $fields ) {
	$fields['sg_review'] = array(
		'sg_rating'      => array(
			'type'    => 'select',
			'label'   => __( 'Số sao', 'sunny-grove-core' ),
			'options' => array(
				'5' => '★★★★★',
				'4' => '★★★★',
				'3' => '★★★',
				'2' => '★★',
				'1' => '★',
			),
		),
		'sg_review_text' => array(
			'type'  => 'textarea',
			'label' => __( 'Nội dung đánh giá', 'sunny-grove-core' ),
			'help'  => __( 'Chép nguyên văn lời khách. Ô English để trống thì bản tiếng Anh hiện nguyên văn tiếng Việt.', 'sunny-grove-core' ),
		),
		'sg_review_from' => array(
			'type'  => 'text',
			'label' => __( 'Đến từ / kiểu chuyến đi', 'sunny-grove-core' ),
			'help'  => __( 'Ví dụ: Hà Nội · gia đình 4 người', 'sunny-grove-core' ),
		),
		'sg_source'      => array(
			'type'    => 'select',
			'label'   => __( 'Nguồn', 'sunny-grove-core' ),
			'options' => sg_review_sources(),
		),
		'sg_review_date' => array(
			'type'  => 'date',
			'label' => __( 'Ngày đánh giá', 'sunny-grove-core' ),
		),
	);
	return $fields;
}
add_filter( 'sgc_meta_fields', 'sg_review_fields' );

/**
 * Cột danh sách đánh giá.
 *
 * @param array $cols Cột.
 * @return array
 */
function sg_review_columns( $cols ) {
	$cols['title'] = __( 'Khách', 'sunny-grove-core' );
	return array_merge(
		array_slice( $cols, 0, 2 ),
		array(
			'sg_rating' => __( 'Số sao', 'sunny-grove-core' ),
			'sg_text'   => __( 'Nội dung', 'sunny-grove-core' ),
			'sg_source' => __( 'Nguồn', 'sunny-grove-core' ),
		),
		array_slice( $cols, 2 )
	);
}
add_filter( 'manage_sg_review_posts_columns', 'sg_review_columns' );

/**
 * Nội dung cột.
 *
 * @param string $col     Cột.
 * @param int    $post_id ID.
 */
function sg_review_column( $col, $post_id ) {
	if ( 'sg_rating' === $col ) {
		echo esc_html( str_repeat( '★', max( 1, min( 5, (int) get_post_meta( $post_id, 'sg_rating', true ) ) ) ) );
	} elseif ( 'sg_text' === $col ) {
		echo esc_html( wp_trim_words( (string) get_post_meta( $post_id, 'sg_review_text', true ), 18 ) );
	} elseif ( 'sg_source' === $col ) {
		echo esc_html( sg_review_sources()[ get_post_meta( $post_id, 'sg_source', true ) ] ?? '' );
	}
}
add_action( 'manage_sg_review_posts_custom_column', 'sg_review_column', 10, 2 );

/**
 * HTML danh sách đánh giá (dùng cho block).
 *
 * @param array $attributes Thuộc tính block.
 * @return string
 */
function sg_render_reviews( $attributes ) {
	$args = array(
		'post_type'      => 'sg_review',
		'post_status'    => 'publish',
		'posts_per_page' => max( 1, min( 12, (int) ( $attributes['count'] ?? 3 ) ) ),
		'orderby'        => array(
			'menu_order' => 'ASC',
			'date'       => 'DESC',
		),
		'no_found_rows'  => true,
	);
	if ( ! empty( $attributes['source'] ) && isset( sg_review_sources()[ $attributes['source'] ] ) ) {
		$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'   => 'sg_source',
				'value' => $attributes['source'],
			),
		);
	}
	if ( ! empty( $attributes['minRating'] ) ) {
		$args['meta_query'][] = array(
			'key'     => 'sg_rating',
			'value'   => (int) $attributes['minRating'],
			'compare' => '>=',
			'type'    => 'NUMERIC',
		);
	}
	$reviews = get_posts( $args );
	if ( ! $reviews ) {
		return '';
	}
	$out = '';
	foreach ( $reviews as $review ) {
		$rating = max( 1, min( 5, (int) get_post_meta( $review->ID, 'sg_rating', true ) ) );
		$source = sg_review_sources()[ get_post_meta( $review->ID, 'sg_source', true ) ] ?? '';
		$from   = sg_meta_tr( $review->ID, 'sg_review_from' );
		$label  = sprintf( sgc_label( 'review_rating' ), $rating );
		$out   .= '<figure class="wp-block-group is-style-sg-card sg-review">'
			. '<p class="sg-stars" role="img" aria-label="' . esc_attr( $label ) . '">' . esc_html( str_repeat( '★', $rating ) . str_repeat( '☆', 5 - $rating ) ) . '</p>'
			. '<blockquote class="sg-review__quote"><p>' . esc_html( sg_meta_tr( $review->ID, 'sg_review_text' ) ) . '</p></blockquote>'
			. '<figcaption><p class="sg-review__name">' . esc_html( get_the_title( $review ) ) . '</p>'
			. ( $from || $source ? '<p class="sg-review__from">' . esc_html( implode( ' · ', array_filter( array( $from, $source ? sprintf( sgc_label( 'review_source' ), $source ) : '' ) ) ) ) . '</p>' : '' )
			. '</figcaption></figure>';
	}
	return '<div ' . get_block_wrapper_attributes( array( 'class' => 'sg-reviews' ) ) . '>' . $out . '</div>';
}
