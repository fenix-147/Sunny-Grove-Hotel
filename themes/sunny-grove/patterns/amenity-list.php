<?php
/**
 * Title: Danh sách tiện ích chi tiết
 * Slug: sunny-grove/amenity-list
 * Categories: sunny-grove
 * Keywords: tiện ích, gallery, giờ mở cửa
 * Description: Mỗi tiện ích một khối: thư viện ảnh/video thực tế, giờ hoạt động, mô tả ngắn. Xen kẽ trái/phải.
 *
 * @package SunnyGrove
 */

echo sunny_grove_section_open( '', 'sg-amenity-list' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_query_open( 'sg_amenity', 30, array( 'type' => 'default' ), 'sg-amenity-list' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<!-- wp:columns {"verticalAlignment":"center","className":"sg-amenity-row"} -->
<div class="wp-block-columns are-vertically-aligned-center sg-amenity-row"><!-- wp:column {"verticalAlignment":"center","width":"55%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:55%"><!-- wp:sunny-grove/media-gallery {"layout":"grid","max":5} /--></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"45%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:45%">
<?php echo sunny_grove_bound_paragraph( 'meta', 'hours_line', 'sg-hours' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<!-- wp:post-title {"level":2,"isLink":true} /-->
<?php echo sunny_grove_bound_paragraph( 'meta', 'short', 'sg-lead' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<!-- wp:post-excerpt {"excerptLength":40} /-->
<?php echo sunny_grove_buttons( array( array( '', '', true, array( 'text' => sunny_grove_bind( 'label', 'read_more' ), 'url' => sunny_grove_bind( 'meta', 'permalink' ) ) ) ), 'left' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
<?php
echo sunny_grove_query_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
