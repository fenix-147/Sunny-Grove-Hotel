<?php
/**
 * Title: Mẹo đi chơi Phú Quốc
 * Slug: sunny-grove/explore-tips
 * Categories: sunny-grove
 * Keywords: mẹo, khám phá, xe máy, tour
 * Description: Ba mẹo ngắn cho gia đình khi đi chơi quanh đảo.
 *
 * @package SunnyGrove
 */

$sunny_grove_tips = array(
	array( __( 'Thuê xe ngay tại lễ tân', 'sunny-grove' ), __( 'Xe máy, xe đạp và xe điện 7 chỗ – có mũ bảo hiểm trẻ em.', 'sunny-grove' ) ),
	array( __( 'Đi Nam đảo buổi sáng', 'sunny-grove' ), __( 'Cáp treo Hòn Thơm và Bãi Sao đẹp nhất trước 11 giờ, bé đỡ nắng.', 'sunny-grove' ) ),
	array( __( 'Ngắm hoàng hôn ở Sunset Town', 'sunny-grove' ), __( 'Có mặt lúc 17:30 để chọn chỗ đẹp, sau đó xem show Nụ Hôn Của Biển.', 'sunny-grove' ) ),
);

echo sunny_grove_section_open( 'white' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_section_head( __( 'Mẹo nhỏ', 'sunny-grove' ), __( 'Đi chơi nhẹ nhàng cùng bé', 'sunny-grove' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<!-- wp:group {"align":"wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|50"},"blockGap":"var:preset|spacing|40"}},"layout":{"type":"grid","minimumColumnWidth":"16rem"}} -->
<div class="wp-block-group alignwide" style="margin-top:var(--wp--preset--spacing--50)">
<?php foreach ( $sunny_grove_tips as $sunny_grove_tip ) : ?>
<!-- wp:group {"className":"is-style-sg-card","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-sg-card"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php echo esc_html( $sunny_grove_tip[0] ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html( $sunny_grove_tip[1] ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
<?php endforeach; ?>
</div>
<!-- /wp:group -->
<?php
echo sunny_grove_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
