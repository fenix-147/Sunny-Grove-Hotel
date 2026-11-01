<?php
/**
 * Title: Form yêu cầu đặt phòng
 * Slug: sunny-grove/booking-section
 * Categories: sunny-grove
 * Keywords: đặt phòng, booking, form
 * Description: Form yêu cầu đặt phòng đầy đủ và lý do nên đặt trực tiếp.
 *
 * @package SunnyGrove
 */

echo sunny_grove_section_open(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"62%"} -->
<div class="wp-block-column" style="flex-basis:62%"><!-- wp:sunny-grove-booking/booking-form /--></div>
<!-- /wp:column -->

<!-- wp:column {"width":"38%"} -->
<div class="wp-block-column" style="flex-basis:38%"><!-- wp:group {"className":"is-style-sg-card","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-sg-card"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php esc_html_e( 'Vì sao nên đặt trực tiếp?', 'sunny-grove' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item {"sgIcon":"voucher"} -->
<li><?php esc_html_e( 'Giá tốt nhất, không qua trung gian', 'sunny-grove' ); ?></li>
<!-- /wp:list-item -->

<!-- wp:list-item {"sgIcon":"phone"} -->
<li><?php esc_html_e( 'Chưa cần thanh toán – chúng tôi gọi lại xác nhận', 'sunny-grove' ); ?></li>
<!-- /wp:list-item -->

<!-- wp:list-item {"sgIcon":"card"} -->
<li><?php esc_html_e( 'Thanh toán tại khách sạn hoặc chuyển khoản đặt cọc', 'sunny-grove' ); ?></li>
<!-- /wp:list-item -->

<!-- wp:list-item {"sgIcon":"crib"} -->
<li><?php esc_html_e( 'Nôi em bé, giường phụ được chuẩn bị sẵn', 'sunny-grove' ); ?></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'Cần gấp? Gọi hoặc nhắn tin cho chúng tôi:', 'sunny-grove' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:sunny-grove/hotel-info {"fields":["phone","zalo","whatsapp"]} /--></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
<?php
echo sunny_grove_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
