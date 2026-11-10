<?php
/**
 * Title: Lời mời đặt phòng
 * Slug: sunny-grove/cta-booking
 * Categories: sunny-grove, call-to-action
 * Keywords: cta, đặt phòng, zalo, lời mời
 * Description: "Hẹn gặp cả nhà / ở Sunny Grove" căn giữa, nút Đặt phòng và nút nhắn Zalo (link lấy từ Thông tin khách sạn).
 *
 * @package SunnyGrove
 */

echo sunny_grove_section_open( '', 'sg-cta' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_heading( __( 'Hẹn gặp cả nhà|ở Sunny Grove', 'sunny-grove' ), 2, true, 'sg-cta__title' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_paragraph( esc_html__( 'Gửi yêu cầu trong 1 phút, chúng tôi gọi lại xác nhận ngay trong ngày.', 'sunny-grove' ), 'sg-lead', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_buttons( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	array(
		array( '', '', false, array( 'text' => sunny_grove_bind( 'label', 'book_now' ), 'url' => sunny_grove_bind( 'hotel', 'booking_url' ) ) ),
		array( '', '', true, array( 'text' => sunny_grove_bind( 'label', 'zalo_cta' ), 'url' => sunny_grove_bind( 'hotel', 'zalo_url' ) ) ),
	)
);
echo sunny_grove_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
