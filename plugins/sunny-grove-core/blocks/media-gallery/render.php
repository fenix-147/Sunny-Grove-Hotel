<?php
/**
 * Thư viện ảnh/video thực tế.
 *
 * @package SunnyGroveCore
 * @var array    $attributes
 * @var WP_Block $block
 */

defined( 'ABSPATH' ) || exit;

$sgc_post_id = sgc_context_post_id( $block );
$sgc_ids     = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $sgc_post_id, 'sg_gallery', true ) ) ) );
$sgc_video   = sgc_video_src( get_post_meta( $sgc_post_id, 'sg_video', true ) );
if ( ! $sgc_ids && ! $sgc_video ) {
	$sgc_thumb = get_post_thumbnail_id( $sgc_post_id );
	if ( ! $sgc_thumb ) {
		return;
	}
	$sgc_ids = array( $sgc_thumb );
}
$sgc_ids    = array_slice( $sgc_ids, 0, max( 1, (int) $attributes['max'] ) );
$sgc_layout = 'scroller' === $attributes['layout'] ? 'scroller' : 'grid';
$sgc_sizes  = 'scroller' === $sgc_layout ? '(min-width: 1024px) 60vw, 85vw' : '(min-width: 1024px) 25vw, 50vw';
$sgc_poster = $sgc_ids ? wp_get_attachment_image_url( $sgc_ids[0], 'large' ) : get_the_post_thumbnail_url( $sgc_post_id, 'large' );
$sgc_wrap   = get_block_wrapper_attributes( array( 'class' => 'sg-gallery sg-gallery--' . $sgc_layout ) );
?>
<div <?php echo $sgc_wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $attributes['showTitle'] ) : ?>
		<p class="sg-gallery__title"><?php echo esc_html( sgc_label( 'gallery' ) ); ?></p>
	<?php endif; ?>
	<ul class="sg-gallery__list" role="list">
		<?php if ( $sgc_video ) : ?>
			<li class="sg-gallery__item sg-gallery__item--video">
				<video class="sg-gallery__video" controls muted playsinline preload="none" src="<?php echo esc_url( $sgc_video ); ?>"<?php echo $sgc_poster ? ' poster="' . esc_url( $sgc_poster ) . '"' : ''; ?>></video>
			</li>
		<?php endif; ?>
		<?php foreach ( $sgc_ids as $sgc_index => $sgc_id ) : ?>
			<li class="sg-gallery__item">
				<?php
				if ( wp_attachment_is( 'video', $sgc_id ) ) {
					printf( '<video class="sg-gallery__video" controls muted playsinline preload="none" src="%s"></video>', esc_url( wp_get_attachment_url( $sgc_id ) ) );
				} else {
					// Kiểu trượt ngang nằm đầu trang chi tiết: ảnh đầu tiên là ảnh chính (LCP) nên tải ngay.
					$sgc_first = 'scroller' === $sgc_layout && 0 === $sgc_index && ! $sgc_video;
					echo wp_get_attachment_image(
						$sgc_id,
						'large',
						false,
						array(
							'loading'       => $sgc_first ? 'eager' : 'lazy',
							'fetchpriority' => $sgc_first ? 'high' : 'auto',
							'sizes'         => $sgc_sizes,
						)
					); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
