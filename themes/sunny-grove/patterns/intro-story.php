<?php
/**
 * Title: Câu chuyện thương hiệu
 * Slug: sunny-grove/intro-story
 * Categories: sunny-grove
 * Keywords: giới thiệu, câu chuyện, about, con số
 * Description: Hai cột: chữ + 3 con số (sửa ở Thông tin khách sạn hoặc bấm trực tiếp trên trang) và ảnh khung vòm có ảnh tròn đè lên.
 *
 * @package SunnyGrove
 */

echo sunny_grove_section_open( '', 'sg-story' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<!-- wp:columns {"verticalAlignment":"center","align":"wide","className":"sg-story__cols"} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center sg-story__cols"><!-- wp:column {"verticalAlignment":"center","width":"52%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:52%">
<?php
echo sunny_grove_eyebrow( __( 'Câu chuyện Grove', 'sunny-grove' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_heading( __( 'Một khu vườn xanh|giữa đảo nắng', 'sunny-grove' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_paragraph( esc_html__( '“Grove” là lùm cây xanh mát. Chúng tôi giữ lại những tán dừa, bụi dâm bụt và lối đi rợp bóng để mỗi buổi sáng, cả nhà thức dậy với tiếng chim và mùi biển.', 'sunny-grove' ), 'sg-lead' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_group_open( 'sg-stats', array( 'type' => 'default' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
for ( $sunny_grove_i = 1; $sunny_grove_i <= 3; $sunny_grove_i++ ) {
	echo sunny_grove_group_open( 'sg-stat', array( 'type' => 'default' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo sunny_grove_group_open( 'sg-stat__value', array( 'type' => 'flex', 'flexWrap' => 'nowrap', 'verticalAlignment' => 'bottom' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo sunny_grove_bound_paragraph( 'hotel', 'stat_' . $sunny_grove_i . '_number', 'sg-stat__number' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo sunny_grove_bound_paragraph( 'hotel', 'stat_' . $sunny_grove_i . '_unit', 'sg-stat__unit' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo sunny_grove_group_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo sunny_grove_bound_paragraph( 'hotel', 'stat_' . $sunny_grove_i . '_label', 'sg-stat__label' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo sunny_grove_group_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
echo sunny_grove_group_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_paragraph( '<a href="' . esc_url( sunny_grove_page_url( 'amenities' ) ) . '">' . esc_html__( 'Tìm hiểu về Sunny Grove →', 'sunny-grove' ) . '</a>', 'sg-link-more' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
</div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"48%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:48%">
<?php
echo sunny_grove_group_open( 'sg-story__media', array( 'type' => 'default' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_image( 'story-arch', __( 'Hai bé chơi đùa bên đài phun nước', 'sunny-grove' ), 'sg-arch', 'sg-story__arch' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_image( 'story-circle', __( 'Bữa sáng với bánh sừng bò và nước cam', 'sunny-grove' ), 'sg-circle', 'sg-story__circle' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_group_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
</div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
<?php
echo sunny_grove_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
