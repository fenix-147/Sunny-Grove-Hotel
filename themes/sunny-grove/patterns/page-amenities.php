<?php
/**
 * Title: Trang mẫu – Tiện ích
 * Slug: sunny-grove/page-amenities
 * Categories: sunny-grove-pages
 * Block Types: core/post-content
 * Post Types: page
 * Viewport Width: 1400
 * Description: Banner video và từng tiện ích với giờ hoạt động, ảnh/video thực tế.
 *
 * @package SunnyGrove
 */

echo sunny_grove_banner( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	array(
		'media'   => 'banner-amenities',
		'video'   => 'video:amenities',
		'eyebrow' => __( 'Tiện ích', 'sunny-grove' ),
		'title'   => __( 'Ngày nào cũng có trò vui', 'sunny-grove' ),
		'text'    => __( 'Hồ bơi, Kids Club, nhà hàng, vườn nhiệt đới và các dịch vụ giúp chuyến đi nhẹ nhàng hơn.', 'sunny-grove' ),
	)
);
echo sunny_grove_pattern( 'amenity-list' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_pattern( 'cta-booking' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
