<?php
/**
 * Title: Banner video trang con
 * Slug: sunny-grove/page-banner
 * Categories: sunny-grove
 * Keywords: banner, video, tiêu đề trang
 * Description: Banner đầu trang có video/ảnh nền, tiêu đề và một câu giới thiệu.
 *
 * @package SunnyGrove
 */

echo sunny_grove_banner( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	array(
		'media'   => 'banner-rooms',
		'video'   => 'video:rooms',
		'eyebrow' => __( 'Sunny Grove', 'sunny-grove' ),
		'title'   => __( 'Tiêu đề trang', 'sunny-grove' ),
		'text'    => __( 'Một câu giới thiệu ngắn, ấm áp về nội dung trang.', 'sunny-grove' ),
	)
);
