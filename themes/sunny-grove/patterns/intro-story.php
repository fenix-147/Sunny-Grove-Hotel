<?php
/**
 * Title: Câu chuyện thương hiệu
 * Slug: sunny-grove/intro-story
 * Categories: sunny-grove
 * Keywords: giới thiệu, câu chuyện, about
 * Description: Hai cột: ảnh có hiệu ứng Ken Burns và đoạn giới thiệu cảm xúc về Sunny Grove.
 *
 * @package SunnyGrove
 */

$sunny_grove_img = sunny_grove_media( 'story' );
$sunny_grove_ia  = array(
	'sizeSlug'        => 'large',
	'linkDestination' => 'none',
	'className'       => 'is-style-sg-kenburns',
);
if ( $sunny_grove_img['id'] ) {
	$sunny_grove_ia = array( 'id' => (int) $sunny_grove_img['id'] ) + $sunny_grove_ia;
}

echo sunny_grove_section_open(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<!-- wp:columns {"verticalAlignment":"center","align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60"}}}} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%"><!-- wp:image <?php echo sunny_grove_attrs( $sunny_grove_ia ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> -->
<figure class="wp-block-image size-large is-style-sg-kenburns"><img src="<?php echo esc_url( $sunny_grove_img['url'] ); ?>" alt="<?php echo esc_attr__( 'Gia đình dạo chơi trong vườn nhiệt đới', 'sunny-grove' ); ?>"<?php echo $sunny_grove_img['id'] ? ' class="wp-image-' . (int) $sunny_grove_img['id'] . '"' : ''; ?>/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%"><!-- wp:paragraph {"className":"is-style-sg-eyebrow"} -->
<p class="is-style-sg-eyebrow"><?php esc_html_e( 'Câu chuyện Grove', 'sunny-grove' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'Một khu vườn xanh giữa đảo nắng', 'sunny-grove' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"sg-lead"} -->
<p class="sg-lead"><?php esc_html_e( '“Grove” là lùm cây xanh mát. Chúng tôi giữ lại những tán dừa, bụi dâm bụt và lối đi rợp bóng để mỗi buổi sáng, cả nhà thức dậy với tiếng chim và mùi biển.', 'sunny-grove' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item {"sgIcon":"bed"} -->
<li><?php esc_html_e( 'Phòng rộng, có giường phụ và nôi em bé miễn phí', 'sunny-grove' ); ?></li>
<!-- /wp:list-item -->

<!-- wp:list-item {"sgIcon":"dining"} -->
<li><?php esc_html_e( 'Thực đơn trẻ em và bữa sáng ngon miệng mỗi ngày', 'sunny-grove' ); ?></li>
<!-- /wp:list-item -->

<!-- wp:list-item {"sgIcon":"greeting"} -->
<li><?php esc_html_e( 'Nhân viên thân thiện, nói tiếng Việt và tiếng Anh', 'sunny-grove' ); ?></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->

<?php echo sunny_grove_buttons( array( array( __( 'Xem các loại phòng', 'sunny-grove' ), sunny_grove_page_url( 'rooms' ), true ) ), 'left' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
<?php
echo sunny_grove_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
