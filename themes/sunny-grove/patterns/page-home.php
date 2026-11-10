<?php
/**
 * Title: Trang mẫu – Trang chủ
 * Slug: sunny-grove/page-home
 * Categories: sunny-grove-pages
 * Block Types: core/post-content
 * Post Types: page
 * Viewport Width: 1440
 * Description: Hero + thanh đặt phòng, câu chuyện, điểm nổi bật, phòng nghỉ, khoảng lặng, tiện ích bento, khám phá Phú Quốc, ưu đãi, đánh giá và lời mời cuối trang.
 *
 * @package SunnyGrove
 */

foreach ( array( 'hero-home', 'intro-story', 'highlights', 'rooms-featured', 'quote-pause', 'amenities-featured', 'places-carousel', 'offers-featured', 'reviews', 'cta-booking' ) as $sunny_grove_part ) {
	echo sunny_grove_pattern( $sunny_grove_part ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
