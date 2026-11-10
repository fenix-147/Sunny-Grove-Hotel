<?php
/**
 * Title: Băng chuyền Khám phá Phú Quốc
 * Slug: sunny-grove/places-carousel
 * Categories: sunny-grove
 * Keywords: địa điểm, khám phá, băng chuyền, carousel, phú quốc
 * Description: Nền than ấm, băng chuyền thẻ ảnh 4:5 có nhãn khoảng cách, dòng so sánh in nghiêng và nút trượt tròn – lấy tự động từ dữ liệu Địa điểm.
 *
 * @package SunnyGrove
 */

echo sunny_grove_section_open( 'ink', 'sg-dark sg-explore' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_group_open( 'alignwide sg-head-split', array( 'type' => 'flex', 'flexWrap' => 'wrap', 'justifyContent' => 'space-between', 'verticalAlignment' => 'bottom' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_group_open( 'sg-head-split__text' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_eyebrow( __( 'Khám phá Phú Quốc', 'sunny-grove' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_heading( __( 'Hôm nay|cả nhà đi đâu?', 'sunny-grove' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_group_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_group_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_query_open( 'sg_place', 12, array( 'type' => 'default' ), 'is-style-sg-carousel sg-carousel--bleed' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_place_card(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_query_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
