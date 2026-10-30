<?php
/**
 * Title: Trang mẫu – Liên hệ
 * Slug: sunny-grove/page-contact
 * Categories: sunny-grove-pages
 * Block Types: core/post-content
 * Post Types: page
 * Viewport Width: 1400
 * Description: Banner, thông tin liên hệ, bản đồ và form liên hệ.
 *
 * @package SunnyGrove
 */

echo sunny_grove_banner( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	array(
		'media'   => 'banner-contact',
		'video'   => 'video:contact',
		'height'  => 'small',
		'eyebrow' => __( 'Liên hệ', 'sunny-grove' ),
		'title'   => __( 'Chúng tôi luôn ở đây', 'sunny-grove' ),
		'text'    => __( 'Hỏi gì cũng được – từ đón sân bay, nôi em bé đến tour câu cá.', 'sunny-grove' ),
	)
);
echo sunny_grove_pattern( 'contact-section' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
