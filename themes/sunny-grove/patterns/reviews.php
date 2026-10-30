<?php
/**
 * Title: Đánh giá khách hàng
 * Slug: sunny-grove/reviews
 * Categories: sunny-grove, testimonials
 * Keywords: đánh giá, review, testimonial, google
 * Description: Đánh giá khách hàng lấy từ menu Đánh giá (song ngữ, số sao, nguồn).
 *
 * @package SunnyGrove
 */

echo sunny_grove_section_open( 'white' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_section_head( __( 'Khách nói gì', 'sunny-grove' ), __( 'Những kỳ nghỉ đáng nhớ', 'sunny-grove' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<!-- wp:sunny-grove/reviews {"count":3,"align":"wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} /-->
<?php
echo sunny_grove_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
