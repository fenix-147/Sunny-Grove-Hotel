<?php
/**
 * Title: Đánh giá khách hàng
 * Slug: sunny-grove/reviews
 * Categories: sunny-grove, testimonials
 * Keywords: đánh giá, review, testimonial, google
 * Description: 3 thẻ đánh giá (nội dung mẫu). Có thể thay bằng block của plugin đánh giá Google.
 *
 * @package SunnyGrove
 */

$sunny_grove_reviews = array(
	array( __( 'Hai bé nhà mình mê Kids Club tới mức không muốn về. Phòng rộng, sạch, nhân viên siêu dễ thương!', 'sunny-grove' ), __( 'Chị Thu Hà', 'sunny-grove' ), __( 'Hà Nội · gia đình 4 người', 'sunny-grove' ) ),
	array( __( 'The garden is so peaceful and the kids’ pool is perfect for toddlers. We would come back in a heartbeat.', 'sunny-grove' ), __( 'Emma & Tom', 'sunny-grove' ), __( 'Melbourne · family with a toddler', 'sunny-grove' ) ),
	array( __( 'Gần biển, gần chợ đêm, có xe máy thuê ngay tại lễ tân. Cả nhóm đi chơi rất tiện.', 'sunny-grove' ), __( 'Anh Minh Quân', 'sunny-grove' ), __( 'TP. Hồ Chí Minh · nhóm bạn', 'sunny-grove' ) ),
);

echo sunny_grove_section_open( 'white' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_section_head( __( 'Khách nói gì', 'sunny-grove' ), __( 'Những kỳ nghỉ đáng nhớ', 'sunny-grove' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<!-- wp:group {"align":"wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|50"},"blockGap":"var:preset|spacing|40"}},"layout":{"type":"grid","minimumColumnWidth":"18rem"}} -->
<div class="wp-block-group alignwide" style="margin-top:var(--wp--preset--spacing--50)">
<?php foreach ( $sunny_grove_reviews as $sunny_grove_review ) : ?>
<!-- wp:group {"className":"is-style-sg-card sg-review","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-sg-card sg-review"><!-- wp:paragraph {"className":"sg-stars"} -->
<p class="sg-stars">★★★★★</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"sg-review__quote"} -->
<p class="sg-review__quote"><?php echo esc_html( $sunny_grove_review[0] ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"sg-review__name"} -->
<p class="sg-review__name"><?php echo esc_html( $sunny_grove_review[1] ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"sg-review__from"} -->
<p class="sg-review__from"><?php echo esc_html( $sunny_grove_review[2] ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
<?php endforeach; ?>
</div>
<!-- /wp:group -->

<!-- wp:paragraph {"align":"center","className":"sg-meta-line"} -->
<p class="has-text-align-center sg-meta-line"><?php esc_html_e( '[CẦN THAY] Đánh giá minh họa – hãy thay bằng đánh giá thật hoặc block “Google Reviews”.', 'sunny-grove' ); ?></p>
<!-- /wp:paragraph -->
<?php
echo sunny_grove_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
