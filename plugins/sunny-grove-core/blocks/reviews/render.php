<?php
/**
 * Block "Đánh giá khách hàng".
 *
 * @package SunnyGroveCore
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

echo sg_render_reviews( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
