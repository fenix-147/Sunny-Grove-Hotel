<?php
/**
 * Thông số phòng.
 *
 * @package SunnyGroveCore
 * @var array    $attributes
 * @var WP_Block $block
 */

defined( 'ABSPATH' ) || exit;

$sgc_post_id = sgc_context_post_id( $block );
$sgc_facts   = array(
	'adult'    => sgc_meta_value( $sgc_post_id, 'adults' ),
	'child'    => sgc_meta_value( $sgc_post_id, 'children' ),
	'area'     => sgc_meta_value( $sgc_post_id, 'area' ),
	'bed'      => $attributes['compact'] ? '' : sgc_meta_value( $sgc_post_id, 'bed' ),
	'view'     => sgc_meta_value( $sgc_post_id, 'view' ),
);
$sgc_facts   = array_filter( $sgc_facts );
if ( ! $sgc_facts ) {
	return;
}
$sgc_wrap = get_block_wrapper_attributes( array( 'class' => 'sg-facts' . ( $attributes['compact'] ? ' is-compact' : '' ) ) );
?>
<ul <?php echo $sgc_wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> role="list">
	<?php foreach ( $sgc_facts as $sgc_icon => $sgc_text ) : ?>
		<li class="sg-facts__item"><?php echo sgc_icon( $sgc_icon, 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( $sgc_text ); ?></span></li>
	<?php endforeach; ?>
</ul>
