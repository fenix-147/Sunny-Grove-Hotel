<?php
/**
 * Block "Form đặt phòng".
 *
 * @package SunnyGroveBooking
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$sgb_wrapper = get_block_wrapper_attributes( array( 'class' => 'sgb-block sgb-block--' . ( 'compact' === $attributes['variant'] ? 'compact' : 'full' ) ) );
?>
<div <?php echo $sgb_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php echo sgb_render_form( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
