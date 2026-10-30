<?php
/**
 * Title: Liên hệ & bản đồ
 * Slug: sunny-grove/contact-section
 * Categories: sunny-grove
 * Keywords: liên hệ, bản đồ, hotline, zalo, whatsapp, form
 * Description: Hotline, Zalo, WhatsApp, email, địa chỉ, bản đồ Google Maps và form liên hệ.
 *
 * @package SunnyGrove
 */

echo sunny_grove_section_open(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"42%"} -->
<div class="wp-block-column" style="flex-basis:42%"><!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'Luôn sẵn lòng hỗ trợ', 'sunny-grove' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'Gọi, nhắn Zalo hay WhatsApp đều được – chúng tôi trả lời nhanh từ 7:00 đến 22:00 mỗi ngày.', 'sunny-grove' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:sunny-grove/hotel-info {"fields":["phone","zalo","whatsapp","email","address","checkin"]} /--></div>
<!-- /wp:column -->

<!-- wp:column {"width":"58%"} -->
<div class="wp-block-column" style="flex-basis:58%"><!-- wp:sunny-grove/hotel-info {"fields":[],"showMap":true} /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
<?php echo sunny_grove_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php echo sunny_grove_section_open( 'white' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php echo sunny_grove_section_head( __( 'Gửi lời nhắn', 'sunny-grove' ), __( 'Bạn cần tư vấn thêm?', 'sunny-grove' ), __( 'Để lại lời nhắn, chúng tôi sẽ phản hồi qua email hoặc điện thoại trong ngày.', 'sunny-grove' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<!-- wp:shortcode -->
[sunny_grove_contact_form]
<!-- /wp:shortcode -->
<?php
echo sunny_grove_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
