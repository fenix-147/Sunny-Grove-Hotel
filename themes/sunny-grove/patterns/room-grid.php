<?php
/**
 * Title: Danh sách phòng có bộ lọc
 * Slug: sunny-grove/room-grid
 * Categories: sunny-grove
 * Keywords: phòng, danh sách, bộ lọc, gia đình, cặp đôi
 * Description: Bộ lọc Gia đình / Cặp đôi / Nhóm bạn và toàn bộ phòng, tự cập nhật khi thêm phòng mới.
 *
 * @package SunnyGrove
 */

echo sunny_grove_section_open( '', 'sg-section--tight' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<!-- wp:sunny-grove/term-filter {"taxonomy":"sg_guest_type","align":"wide"} /-->
<?php
echo sunny_grove_query_open( 'sg_room', 30, array( 'type' => 'default' ), 'sg-grid' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_room_card(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_query_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
