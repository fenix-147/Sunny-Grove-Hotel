<?php
/**
 * Title: Ưu đãi khác (trang chi tiết ưu đãi)
 * Slug: sunny-grove/offers-other
 * Inserter: no
 *
 * @package SunnyGrove
 */

echo sunny_grove_section_open( '', 'sg-section--other' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_bound_heading( 'other_offers' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_query_open( 'sg_offer', 3, array( 'type' => 'default' ), 'sg-grid', array( 'sgExcludeCurrent' => true ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_offer_card(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_query_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
