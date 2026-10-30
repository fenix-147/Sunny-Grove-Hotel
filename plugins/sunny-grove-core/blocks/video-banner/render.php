<?php
/**
 * Banner video.
 *
 * @package SunnyGroveCore
 * @var array    $attributes
 * @var string   $content
 * @var WP_Block $block
 */

defined( 'ABSPATH' ) || exit;

$sgc_is_first = ! did_action( 'sgc_banner_rendered' );
do_action( 'sgc_banner_rendered' );

$sgc_height   = in_array( $attributes['height'], array( 'full', 'large', 'medium', 'small' ), true ) ? $attributes['height'] : 'large';
$sgc_position = 'bottom' === $attributes['contentPosition'] ? 'bottom' : 'center';
$sgc_overlay  = max( 0, min( 90, (int) $attributes['overlay'] ) ) / 100;
$sgc_video    = sgc_video_src( $attributes['videoUrl'] );
$sgc_mobile   = sgc_video_src( $attributes['videoMobileUrl'] );

$sgc_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'sg-banner sg-banner--' . $sgc_height . ' sg-banner--' . $sgc_position,
		'style' => '--sg-overlay:' . $sgc_overlay . ';',
	)
);

$sgc_poster = '';
if ( $attributes['posterId'] ) {
	$sgc_poster = wp_get_attachment_image(
		(int) $attributes['posterId'],
		'full',
		false,
		array(
			'class'         => 'sg-banner__poster',
			'alt'           => '',
			'sizes'         => '100vw',
			'loading'       => $sgc_is_first ? 'eager' : 'lazy',
			'fetchpriority' => $sgc_is_first ? 'high' : 'auto',
			'decoding'      => 'async',
		)
	);
} elseif ( $attributes['posterUrl'] ) {
	$sgc_poster = sprintf(
		'<img class="sg-banner__poster" src="%1$s" alt="" %2$s>',
		esc_url( $attributes['posterUrl'] ),
		$sgc_is_first ? 'fetchpriority="high"' : 'loading="lazy"'
	);
}
?>
<section <?php echo $sgc_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="sg-banner__media" aria-hidden="true">
		<?php echo $sgc_poster; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php if ( $sgc_video || $sgc_mobile ) : ?>
			<video class="sg-banner__video" muted loop playsinline preload="none" tabindex="-1"
				data-src="<?php echo esc_url( $sgc_video ? $sgc_video : $sgc_mobile ); ?>"
				<?php if ( $sgc_mobile ) : ?>
					data-src-mobile="<?php echo esc_url( $sgc_mobile ); ?>"
				<?php endif; ?>
			></video>
		<?php endif; ?>
	</div>
	<div class="sg-banner__content">
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
	<?php if ( $sgc_video || $sgc_mobile ) : ?>
		<button type="button" class="sg-banner__toggle" aria-pressed="false" hidden>
			<span class="sg-banner__icon-pause"><?php echo sgc_icon( 'pause', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<span class="sg-banner__icon-play"><?php echo sgc_icon( 'play', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<span class="screen-reader-text"><?php echo esc_html( sgc_label( 'video_pause' ) ); ?></span>
		</button>
	<?php endif; ?>
</section>
