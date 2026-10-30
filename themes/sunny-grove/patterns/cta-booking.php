<?php
/**
 * Title: Lời kêu gọi đặt phòng
 * Slug: sunny-grove/cta-booking
 * Categories: sunny-grove, call-to-action
 * Keywords: cta, đặt phòng, hotline
 * Description: Banner video hoàng hôn với nút Đặt phòng và Gọi hotline (lấy từ Thông tin khách sạn).
 *
 * @package SunnyGrove
 */

echo sunny_grove_banner( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	array(
		'media'   => 'cta',
		'video'   => 'video:cta',
		'height'  => 'small',
		'overlay' => 50,
		'level'   => 2,
		'title'   => __( 'Sẵn sàng cho kỳ nghỉ của cả nhà?', 'sunny-grove' ),
		'text'    => __( 'Gửi yêu cầu chỉ trong 1 phút – chúng tôi gọi lại xác nhận ngay trong ngày.', 'sunny-grove' ),
		'inner'   => sunny_grove_buttons(
			array(
				array( '', '', false, array( 'text' => sunny_grove_bind( 'label', 'book_now' ), 'url' => sunny_grove_bind( 'hotel', 'booking_url' ) ) ),
				array( '', '', true, array( 'text' => sunny_grove_bind( 'label', 'call' ), 'url' => sunny_grove_bind( 'hotel', 'tel_url' ) ) ),
			)
		),
	)
);
