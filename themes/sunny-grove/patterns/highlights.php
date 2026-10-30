<?php
/**
 * Title: Điểm nổi bật (4 icon)
 * Slug: sunny-grove/highlights
 * Categories: sunny-grove
 * Keywords: icon, điểm nổi bật, kids club, hồ bơi
 * Description: 4 điểm nổi bật dạng icon: Kids Club, hồ bơi trẻ em, gần biển, đưa đón sân bay.
 *
 * @package SunnyGrove
 */

$sunny_grove_items = array(
	array( 'kids', __( 'Kids Club', 'sunny-grove' ), __( 'Bé vui chơi cùng bảo mẫu, ba mẹ thảnh thơi thư giãn.', 'sunny-grove' ) ),
	array( 'pool', __( 'Hồ bơi trẻ em', 'sunny-grove' ), __( 'Nước nông, có cứu hộ, ngay cạnh hồ bơi người lớn.', 'sunny-grove' ) ),
	array( 'beach', __( 'Gần biển', 'sunny-grove' ), __( 'Chỉ 5 phút đi bộ là tới bãi cát và hoàng hôn.', 'sunny-grove' ) ),
	array( 'shuttle', __( 'Đưa đón sân bay', 'sunny-grove' ), __( 'Miễn phí cho đặt phòng từ 3 đêm, có ghế trẻ em.', 'sunny-grove' ) ),
);

echo sunny_grove_section_open( 'white' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_section_head( __( 'Vì sao cả nhà yêu Sunny Grove', 'sunny-grove' ), __( 'Mọi thứ cho một kỳ nghỉ nhẹ nhàng', 'sunny-grove' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<!-- wp:group {"align":"wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|50"},"blockGap":"var:preset|spacing|50"}},"layout":{"type":"grid","minimumColumnWidth":"14rem"}} -->
<div class="wp-block-group alignwide" style="margin-top:var(--wp--preset--spacing--50)">
<?php foreach ( $sunny_grove_items as $sunny_grove_item ) : ?>
<!-- wp:group {"className":"sg-highlight","layout":{"type":"default"}} -->
<div class="wp-block-group sg-highlight"><!-- wp:image {"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/icons/' . $sunny_grove_item[0] . '.svg' ); ?>" alt=""/></figure>
<!-- /wp:image -->

<!-- wp:heading {"textAlign":"center","level":3} -->
<h3 class="wp-block-heading has-text-align-center"><?php echo esc_html( $sunny_grove_item[1] ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center"><?php echo esc_html( $sunny_grove_item[2] ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
<?php endforeach; ?>
</div>
<!-- /wp:group -->
<?php
echo sunny_grove_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
