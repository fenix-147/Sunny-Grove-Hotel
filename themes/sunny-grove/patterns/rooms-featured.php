<?php
/**
 * Title: Phòng tiêu biểu
 * Slug: sunny-grove/rooms-featured
 * Categories: sunny-grove
 * Keywords: phòng, room, thẻ phòng
 * Description: 3 phòng đầu tiên (theo cột Thứ tự) lấy tự động từ dữ liệu Phòng.
 *
 * @package SunnyGrove
 */

echo sunny_grove_section_open( 'sand' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_section_head( __( 'Phòng nghỉ', 'sunny-grove' ), __( 'Chọn góc nghỉ hợp với nhà mình', 'sunny-grove' ), __( 'Phòng gia đình rộng rãi, phòng đôi lãng mạn hay phòng cho cả nhóm bạn – đều sáng, thoáng và gần vườn.', 'sunny-grove' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_query_open( 'sg_room', 3, array( 'type' => 'default' ), 'sg-grid' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_room_card(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_query_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_buttons( array( array( __( 'Xem tất cả phòng', 'sunny-grove' ), sunny_grove_page_url( 'rooms' ), true ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
