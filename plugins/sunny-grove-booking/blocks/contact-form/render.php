<?php
/**
 * Block "Form liên hệ".
 *
 * @package SunnyGroveBooking
 */

defined( 'ABSPATH' ) || exit;

echo '<div ' . get_block_wrapper_attributes() . '>' . sgb_render_contact_form() . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
