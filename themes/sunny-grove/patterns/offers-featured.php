<?php
/**
 * Title: Ưu đãi đang chạy
 * Slug: sunny-grove/offers-featured
 * Categories: sunny-grove
 * Keywords: ưu đãi, khuyến mãi, combo
 * Description: 3 ưu đãi còn hạn, ưu đãi hết hạn tự ẩn.
 *
 * @package SunnyGrove
 */

echo sunny_grove_section_open( 'sand' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_section_head( __( 'Ưu đãi', 'sunny-grove' ), __( 'Ưu đãi đang chạy', 'sunny-grove' ), __( 'Đặt trực tiếp với khách sạn để có giá tốt nhất và quà nhỏ cho bé.', 'sunny-grove' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_query_open( 'sg_offer', 3, array( 'type' => 'default' ), 'sg-grid' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_offer_card(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_query_close( 'offers_empty' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_buttons( array( array( __( 'Xem tất cả ưu đãi', 'sunny-grove' ), sunny_grove_page_url( 'offers' ), true ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
