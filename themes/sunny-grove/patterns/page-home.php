<?php
/**
 * Title: Trang mẫu – Trang chủ
 * Slug: sunny-grove/page-home
 * Categories: sunny-grove-pages
 * Block Types: core/post-content
 * Post Types: page
 * Viewport Width: 1400
 * Description: Hero video, điểm nổi bật, câu chuyện, phòng, tiện ích, khám phá, ưu đãi, đánh giá và lời kêu gọi đặt phòng.
 *
 * @package SunnyGrove
 */

foreach ( array( 'hero-home', 'highlights', 'intro-story', 'rooms-featured', 'amenities-featured', 'places-carousel', 'offers-featured', 'reviews', 'cta-booking' ) as $sunny_grove_part ) {
	echo sunny_grove_pattern( $sunny_grove_part ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
