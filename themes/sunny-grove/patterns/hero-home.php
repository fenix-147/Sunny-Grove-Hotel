<?php
/**
 * Title: Hero video trang chủ
 * Slug: sunny-grove/hero-home
 * Categories: sunny-grove, featured
 * Keywords: hero, video, banner, slogan, tìm phòng
 * Description: Video nền toàn màn hình, slogan (lấy từ Thông tin khách sạn) và khung tìm phòng.
 *
 * @package SunnyGrove
 */

echo sunny_grove_banner( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	array(
		'media'        => 'hero',
		'video'        => 'video:hero',
		'video_mobile' => 'video:hero-m',
		'height'       => 'full',
		'overlay'      => 38,
		'eyebrow'      => __( 'Sunny Grove Hotel · Phú Quốc', 'sunny-grove' ),
		'title_bind'   => array( 'hotel', 'slogan' ),
		'text'         => __( 'Vườn nhiệt đới mát lành, hồ bơi cho bé, biển xanh chỉ vài bước chân.', 'sunny-grove' ),
		'inner'        => '<!-- wp:sunny-grove-booking/booking-form {"variant":"compact"} /-->' . "\n",
	)
);
