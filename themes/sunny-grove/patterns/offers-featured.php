<?php
/**
 * Title: Ưu đãi đang chạy (danh sách kẻ dòng)
 * Slug: sunny-grove/offers-featured
 * Categories: sunny-grove
 * Keywords: ưu đãi, khuyến mãi, combo
 * Description: Tiêu đề bên trái, bên phải là 3 ưu đãi còn hạn dạng danh sách kẻ dòng (ảnh vuông · tên + mô tả · giá/giảm %). Ưu đãi hết hạn tự ẩn.
 *
 * @package SunnyGrove
 */

echo sunny_grove_section_open( '', 'sg-offers' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<!-- wp:columns {"align":"wide","className":"sg-offers__cols"} -->
<div class="wp-block-columns alignwide sg-offers__cols"><!-- wp:column {"width":"38%"} -->
<div class="wp-block-column" style="flex-basis:38%">
<?php
echo sunny_grove_eyebrow( __( 'Ưu đãi', 'sunny-grove' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_heading( __( 'Đặt trực tiếp,|nhận nhiều hơn', 'sunny-grove' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_paragraph( esc_html__( 'Giá tốt nhất khi đặt thẳng với khách sạn, kèm một món quà nhỏ cho bé.', 'sunny-grove' ), 'sg-lead' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_paragraph( '<a href="' . esc_url( sunny_grove_page_url( 'offers' ) ) . '">' . esc_html__( 'Xem tất cả ưu đãi →', 'sunny-grove' ) . '</a>', 'sg-link-more' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
</div>
<!-- /wp:column -->

<!-- wp:column {"width":"62%"} -->
<div class="wp-block-column" style="flex-basis:62%">
<?php
echo sunny_grove_query_open( 'sg_offer', 3, array( 'type' => 'default' ), 'sg-offer-list' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_offer_row(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_query_close( 'offers_empty' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
</div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
<?php
echo sunny_grove_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
