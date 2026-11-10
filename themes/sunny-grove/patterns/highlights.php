<?php
/**
 * Title: Điểm nổi bật (4 icon)
 * Slug: sunny-grove/highlights
 * Categories: sunny-grove
 * Keywords: icon, điểm nổi bật, kids club, hồ bơi
 * Description: 4 điểm nổi bật trên nền lanh, icon nét mảnh, ngăn bằng đường kẻ dọc: Kids Club, hồ bơi cho bé, gần biển, đưa đón sân bay.
 *
 * @package SunnyGrove
 */

$sunny_grove_items = array(
	array( 'kids', __( 'Kids Club', 'sunny-grove' ), __( 'Bé vui chơi cùng bảo mẫu, ba mẹ thảnh thơi thư giãn.', 'sunny-grove' ) ),
	array( 'pool', __( 'Hồ bơi cho bé', 'sunny-grove' ), __( 'Hồ nông 40cm, có cứu hộ, ngay cạnh hồ người lớn.', 'sunny-grove' ) ),
	array( 'beach', __( 'Gần biển', 'sunny-grove' ), __( 'Chỉ 5 phút đi bộ là tới bãi cát và hoàng hôn.', 'sunny-grove' ) ),
	array( 'shuttle', __( 'Đưa đón sân bay', 'sunny-grove' ), __( 'Miễn phí khi ở từ 3 đêm, có ghế ngồi cho bé.', 'sunny-grove' ) ),
);

echo sunny_grove_section_open( 'sand-deep', 'sg-highlights' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<!-- wp:group {"align":"wide","className":"sg-highlights__grid","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide sg-highlights__grid">
<?php foreach ( $sunny_grove_items as $sunny_grove_item ) : ?>
<!-- wp:group {"className":"sg-highlight","layout":{"type":"default"}} -->
<div class="wp-block-group sg-highlight"><!-- wp:image {"width":"34px","height":"34px","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/icons/' . $sunny_grove_item[0] . '.svg' ); ?>" alt="" style="width:34px;height:34px"/></figure>
<!-- /wp:image -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php echo esc_html( $sunny_grove_item[1] ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html( $sunny_grove_item[2] ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
<?php endforeach; ?>
</div>
<!-- /wp:group -->
<?php
echo sunny_grove_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
