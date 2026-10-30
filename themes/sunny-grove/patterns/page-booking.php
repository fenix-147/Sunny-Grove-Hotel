<?php
/**
 * Title: Trang mẫu – Đặt phòng
 * Slug: sunny-grove/page-booking
 * Categories: sunny-grove-pages
 * Block Types: core/post-content
 * Post Types: page
 * Viewport Width: 1400
 * Description: Banner thấp và form yêu cầu đặt phòng.
 *
 * @package SunnyGrove
 */

echo sunny_grove_banner( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	array(
		'media'   => 'banner-booking',
		'video'   => 'video:booking',
		'height'  => 'small',
		'eyebrow' => __( 'Đặt phòng', 'sunny-grove' ),
		'title'   => __( 'Gửi yêu cầu đặt phòng', 'sunny-grove' ),
		'text'    => __( 'Chỉ 1 phút, chưa cần thanh toán. Chúng tôi sẽ gọi lại để xác nhận phòng và giá tốt nhất.', 'sunny-grove' ),
	)
);
echo sunny_grove_pattern( 'booking-section' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
