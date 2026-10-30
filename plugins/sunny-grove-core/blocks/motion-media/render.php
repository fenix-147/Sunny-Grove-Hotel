<?php
/**
 * Ảnh động của thẻ.
 *
 * @package SunnyGroveCore
 * @var array    $attributes
 * @var WP_Block $block
 */

defined( 'ABSPATH' ) || exit;

$sgc_post_id = sgc_context_post_id( $block );
if ( ! $sgc_post_id ) {
	return;
}
$sgc_ratio = preg_match( '#^\d+(\.\d+)?/\d+(\.\d+)?$#', $attributes['aspectRatio'] ) ? $attributes['aspectRatio'] : '4/3';
$sgc_video = $attributes['showVideo'] ? sgc_video_src( get_post_meta( $sgc_post_id, 'sg_video', true ) ) : '';
$sgc_img   = get_the_post_thumbnail(
	$sgc_post_id,
	'large',
	array(
		'class'   => 'sg-media__img',
		'sizes'   => $attributes['sizes'],
		'loading' => 'lazy',
		'alt'     => '',
	)
);
$sgc_badge = $attributes['showBadge'] ? sg_meta_tr( $sgc_post_id, 'sg_badge' ) : '';

$sgc_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'sg-media' . ( $sgc_video ? ' has-video' : '' ),
		'style' => 'aspect-ratio:' . $sgc_ratio . ';',
	)
);
$sgc_tag  = $attributes['isLink'] ? 'a' : 'div';
$sgc_link = $attributes['isLink'] ? ' href="' . esc_url( get_permalink( $sgc_post_id ) ) . '" tabindex="-1" aria-hidden="true"' : '';
?>
<<?php echo $sgc_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo $sgc_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo $sgc_link; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php echo $sgc_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php if ( $sgc_video ) : ?>
		<video class="sg-media__video" muted loop playsinline preload="none" tabindex="-1" aria-hidden="true" data-src="<?php echo esc_url( $sgc_video ); ?>"></video>
	<?php endif; ?>
	<?php if ( $sgc_badge ) : ?>
		<span class="sg-media__badge"><?php echo esc_html( $sgc_badge ); ?></span>
	<?php endif; ?>
</<?php echo $sgc_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
