<?php
/**
 * Title: Tiện ích nổi bật (thẻ ảnh động)
 * Slug: sunny-grove/amenities-featured
 * Categories: sunny-grove
 * Keywords: tiện ích, hồ bơi, kids club, ảnh động
 * Description: 4 tiện ích đầu tiên, thẻ có video ngắn tự chạy khi cuộn tới.
 *
 * @package SunnyGrove
 */

echo sunny_grove_section_open( 'white' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_section_head( __( 'Tiện ích', 'sunny-grove' ), __( 'Cả nhà đều có niềm vui riêng', 'sunny-grove' ), __( 'Bé có Kids Club và hồ bơi nông, ba mẹ có vườn yên tĩnh, nhà hàng và tour đảo.', 'sunny-grove' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_query_open( 'sg_amenity', 4, array( 'type' => 'default' ), 'sg-grid sg-grid--4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_amenity_card(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_query_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_buttons( array( array( __( 'Xem tất cả tiện ích', 'sunny-grove' ), sunny_grove_page_url( 'amenities' ), true ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
