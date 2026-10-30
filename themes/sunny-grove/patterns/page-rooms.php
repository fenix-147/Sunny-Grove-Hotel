<?php
/**
 * Title: Trang mẫu – Phòng & Giá
 * Slug: sunny-grove/page-rooms
 * Categories: sunny-grove-pages
 * Block Types: core/post-content
 * Post Types: page
 * Viewport Width: 1400
 * Description: Banner video, bộ lọc nhóm khách và danh sách phòng.
 *
 * @package SunnyGrove
 */

echo sunny_grove_banner( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	array(
		'media'   => 'banner-rooms',
		'video'   => 'video:rooms',
		'eyebrow' => __( 'Phòng & Giá', 'sunny-grove' ),
		'title'   => __( 'Ngủ ngon giữa vườn xanh', 'sunny-grove' ),
		'text'    => __( 'Chọn nhóm khách để xem phòng phù hợp. Giá đã gồm bữa sáng cho cả nhà.', 'sunny-grove' ),
	)
);
echo sunny_grove_pattern( 'room-grid' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_pattern( 'cta-booking' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
