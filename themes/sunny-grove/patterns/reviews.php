<?php
/**
 * Title: Đánh giá Google Maps
 * Slug: sunny-grove/reviews
 * Categories: sunny-grove, testimonials
 * Keywords: đánh giá, review, testimonial, google maps
 * Description: Đầu khối căn giữa (điểm trung bình, số đánh giá), 3 cột trích dẫn nguyên văn ngăn bằng đường kẻ mảnh, link xem tất cả / viết đánh giá. Lấy từ menu Đánh giá; chưa có đánh giá nào thì cả khối tự ẩn.
 *
 * @package SunnyGrove
 */

echo sunny_grove_section_open( '', 'sg-testimonials' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<!-- wp:sunny-grove/reviews {"align":"wide"} /-->
<?php
echo sunny_grove_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
