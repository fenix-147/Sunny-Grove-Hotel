<?php
/**
 * Title: Băng chuyền Khám phá Phú Quốc
 * Slug: sunny-grove/places-carousel
 * Categories: sunny-grove
 * Keywords: địa điểm, khám phá, băng chuyền, carousel, phú quốc
 * Description: Băng chuyền ngang các địa điểm (vuốt được trên điện thoại) kèm bộ lọc nhanh, lấy tự động từ dữ liệu Địa điểm.
 *
 * @package SunnyGrove
 */

echo sunny_grove_section_open( 'sand-deep' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_section_head( __( 'Khám phá Phú Quốc', 'sunny-grove' ), __( 'Hôm nay cả nhà đi đâu?', 'sunny-grove' ), __( 'Khoảng cách tính từ Sunny Grove. Vuốt ngang hoặc bấm mũi tên để xem thêm.', 'sunny-grove' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<!-- wp:sunny-grove/term-filter {"taxonomy":"sg_place_tag","align":"wide"} /-->
<?php
echo sunny_grove_query_open( 'sg_place', 12, array( 'type' => 'default' ), 'is-style-sg-carousel' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_place_card(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_query_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
