<?php
/**
 * Khoảng cách & chỉ đường.
 *
 * @package SunnyGroveCore
 * @var array    $attributes
 * @var WP_Block $block
 */

defined( 'ABSPATH' ) || exit;

$sgc_post_id  = sgc_context_post_id( $block );
$sgc_distance = sgc_meta_value( $sgc_post_id, 'distance' );
$sgc_travel   = sgc_meta_value( $sgc_post_id, 'travel' );
$sgc_mode     = get_post_meta( $sgc_post_id, 'sg_mode', true );
$sgc_compare  = $attributes['showCompare'] ? sgc_meta_value( $sgc_post_id, 'compare' ) : '';
$sgc_map      = $attributes['showMap'] ? sgc_meta_value( $sgc_post_id, 'map_url' ) : '';
if ( ! $sgc_distance && ! $sgc_travel && ! $sgc_compare && ! $sgc_map ) {
	return;
}
$sgc_wrap = get_block_wrapper_attributes( array( 'class' => 'sg-place' ) );
?>
<div <?php echo $sgc_wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $sgc_compare ) : ?>
		<p class="sg-place__compare"><?php echo esc_html( $sgc_compare ); ?></p>
	<?php endif; ?>
	<?php if ( $sgc_distance || $sgc_travel ) : ?>
		<ul class="sg-facts" role="list">
			<?php if ( $sgc_distance ) : ?>
				<li class="sg-facts__item"><?php echo sgc_icon( 'pin', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( $sgc_distance . ' ' . sgc_label( 'from_hotel' ) ); ?></span></li>
			<?php endif; ?>
			<?php if ( $sgc_travel ) : ?>
				<li class="sg-facts__item"><?php echo sgc_icon( in_array( $sgc_mode, array( 'walk', 'car', 'motorbike' ), true ) ? $sgc_mode : 'clock', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( $sgc_travel ); ?></span></li>
			<?php endif; ?>
		</ul>
	<?php endif; ?>
	<?php if ( $sgc_map ) : ?>
		<a class="sg-place__map" href="<?php echo esc_url( $sgc_map ); ?>" target="_blank" rel="noopener">
			<?php echo sgc_icon( 'external', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span><?php echo esc_html( sgc_label( 'open_map' ) ); ?></span>
			<span class="screen-reader-text"><?php echo esc_html( get_the_title( $sgc_post_id ) ); ?></span>
		</a>
	<?php endif; ?>
</div>
