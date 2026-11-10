<?php
/**
 * Title: Phòng tiêu biểu
 * Slug: sunny-grove/rooms-featured
 * Categories: sunny-grove
 * Keywords: phòng, room, thẻ phòng, khung vòm
 * Description: Tiêu đề bên trái + chip lọc bên phải, 3 phòng đầu tiên (theo cột Thứ tự) dạng thẻ ảnh khung vòm, thẻ giữa so le.
 *
 * @package SunnyGrove
 */

echo sunny_grove_section_open( '', 'sg-rooms' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_group_open( 'alignwide sg-head-split', array( 'type' => 'flex', 'flexWrap' => 'wrap', 'justifyContent' => 'space-between', 'verticalAlignment' => 'bottom' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_group_open( 'sg-head-split__text' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_eyebrow( __( 'Phòng nghỉ', 'sunny-grove' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_heading( __( 'Chọn góc nghỉ|hợp với nhà mình', 'sunny-grove' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_group_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<!-- wp:sunny-grove/term-filter {"taxonomy":"sg_guest_type"} /-->
<?php
echo sunny_grove_group_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_query_open( 'sg_room', 3, array( 'type' => 'default' ), 'sg-grid sg-grid--rooms' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_room_card(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_query_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
