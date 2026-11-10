<?php
/**
 * Title: Tiện ích nổi bật (bento)
 * Slug: sunny-grove/amenities-featured
 * Categories: sunny-grove
 * Keywords: tiện ích, hồ bơi, kids club, bento
 * Description: 4 tiện ích đầu tiên (theo cột Thứ tự) dạng lưới bento: 1 ô lớn, 1 ô ngang, 2 ô nhỏ, chữ trên ảnh. 3 tiện ích → 1 ô lớn + 2 ô; nhiều hơn 4 → xem ở trang Tiện ích.
 *
 * @package SunnyGrove
 */

echo sunny_grove_section_open( '', 'sg-amenities' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_section_head( __( 'Tiện ích', 'sunny-grove' ), __( 'Cả nhà đều có|niềm vui riêng', 'sunny-grove' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_query_open( 'sg_amenity', 4, array( 'type' => 'default' ), 'sg-bento' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_amenity_tile(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_query_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_paragraph( '<a href="' . esc_url( sunny_grove_page_url( 'amenities' ) ) . '">' . esc_html__( 'Xem tất cả tiện ích →', 'sunny-grove' ) . '</a>', 'sg-link-more sg-bento__more', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
