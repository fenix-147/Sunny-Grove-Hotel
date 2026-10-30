<?php
/**
 * Điều chỉnh Query Loop: ẩn ưu đãi hết hạn, bỏ bài đang xem khỏi danh sách "khác".
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Thêm điều kiện ẩn ưu đãi hết hạn (ngày kết thúc < hôm nay) vào tham số WP_Query.
 * Ưu đãi không có ngày kết thúc luôn hiện.
 *
 * @param array $query Tham số WP_Query.
 * @return array
 */
function sgc_hide_expired_offers( $query ) {
	$today                 = wp_date( 'Y-m-d' );
	$query['meta_query']   = isset( $query['meta_query'] ) ? (array) $query['meta_query'] : array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	$query['meta_query'][] = array(
		'relation' => 'OR',
		array(
			'key'     => 'sg_valid_to',
			'compare' => 'NOT EXISTS',
		),
		array(
			'key'     => 'sg_valid_to',
			'value'   => '',
			'compare' => '=',
		),
		array(
			'key'     => 'sg_valid_to',
			'value'   => $today,
			'compare' => '>=',
			'type'    => 'DATE',
		),
	);
	return $query;
}

/**
 * Lọc tham số truy vấn của block Query Loop.
 *
 * @param array    $query Tham số WP_Query.
 * @param WP_Block $block Block post-template.
 * @return array
 */
function sgc_query_loop_vars( $query, $block ) {
	$settings = isset( $block->context['query'] ) ? (array) $block->context['query'] : array();
	$types    = (array) ( $query['post_type'] ?? array() );

	// Ưu đãi hết hạn tự ẩn (ngày kết thúc < hôm nay). Ưu đãi không có ngày kết thúc luôn hiện.
	if ( in_array( 'sg_offer', $types, true ) ) {
		$query = sgc_hide_expired_offers( $query );
	}

	// Danh sách "các mục khác" trên trang chi tiết: bỏ mục đang xem.
	if ( ! empty( $settings['sgExcludeCurrent'] ) && is_singular() ) {
		$query['post__not_in'] = array_merge( (array) ( $query['post__not_in'] ?? array() ), array( get_queried_object_id() ) );
	}

	return $query;
}
add_filter( 'query_loop_block_query_vars', 'sgc_query_loop_vars', 10, 2 );

/**
 * Trình soạn thảo (request có sg_editor) cũng ẩn ưu đãi hết hạn để hiển thị giống ngoài website.
 *
 * @param array           $args    Tham số WP_Query.
 * @param WP_REST_Request $request Request.
 * @return array
 */
function sgc_rest_hide_expired_offers( $args, $request ) {
	return ( $request->get_param( 'sg_editor' ) || $request->get_param( 'sg_lang' ) ) ? sgc_hide_expired_offers( $args ) : $args;
}
add_filter( 'rest_sg_offer_query', 'sgc_rest_hide_expired_offers', 10, 2 );
