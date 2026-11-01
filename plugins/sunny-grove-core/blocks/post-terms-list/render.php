<?php
/**
 * Danh sách tiện nghi / nhãn của bài.
 * Kiểu "icons" / "compact": mỗi mục có icon riêng (icon của tiện nghi, chưa chọn → lá). Kiểu "chips": thẻ nhỏ.
 *
 * @package SunnyGroveCore
 * @var array    $attributes
 * @var WP_Block $block
 */

defined( 'ABSPATH' ) || exit;

$sgc_post_id = sgc_context_post_id( $block );
$sgc_tax     = sanitize_key( $attributes['taxonomy'] );
$sgc_terms   = $sgc_post_id && taxonomy_exists( $sgc_tax ) ? get_the_terms( $sgc_post_id, $sgc_tax ) : array();
if ( ! $sgc_terms || is_wp_error( $sgc_terms ) ) {
	return;
}
$sgc_limit = (int) ( $attributes['limit'] ?? 0 );
if ( $sgc_limit > 0 ) {
	$sgc_terms = array_slice( $sgc_terms, 0, $sgc_limit );
}
$sgc_style = in_array( $attributes['variant'], array( 'chips', 'compact' ), true ) ? $attributes['variant'] : 'icons';
$sgc_class = 'sg-terms sg-terms--' . $sgc_style . ( 'compact' === $sgc_style ? ' sg-terms--icons' : '' );
$sgc_wrap  = get_block_wrapper_attributes( array( 'class' => $sgc_class ) );
?>
<div <?php echo $sgc_wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $attributes['showTitle'] && 'sg_room_feature' === $sgc_tax ) : ?>
		<h3 class="sg-terms__title"><?php echo esc_html( sgc_label( 'room_features' ) ); ?></h3>
	<?php endif; ?>
	<ul class="sg-terms__list" role="list">
		<?php foreach ( $sgc_terms as $sgc_term ) : ?>
			<li class="sg-terms__item">
				<?php
				if ( 'chips' !== $sgc_style ) {
					echo sgc_icon( sgc_term_icon( $sgc_term->term_id ), 'compact' === $sgc_style ? 18 : 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				echo '<span>' . esc_html( $sgc_term->name ) . '</span>';
				?>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
