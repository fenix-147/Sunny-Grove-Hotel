<?php
/**
 * Danh sách tiện nghi / nhãn của bài.
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
$sgc_style = 'chips' === $attributes['variant'] ? 'chips' : 'checks';
$sgc_wrap  = get_block_wrapper_attributes( array( 'class' => 'sg-terms sg-terms--' . $sgc_style ) );
?>
<div <?php echo $sgc_wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $attributes['showTitle'] && 'sg_room_feature' === $sgc_tax ) : ?>
		<h3 class="sg-terms__title"><?php echo esc_html( sgc_label( 'room_features' ) ); ?></h3>
	<?php endif; ?>
	<ul class="sg-terms__list" role="list">
		<?php foreach ( $sgc_terms as $sgc_term ) : ?>
			<li class="sg-terms__item">
				<?php
				if ( 'checks' === $sgc_style ) {
					echo sgc_icon( 'check', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				echo esc_html( $sgc_term->name );
				?>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
