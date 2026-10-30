<?php
/**
 * Title: Địa điểm khác (trang chi tiết địa điểm)
 * Slug: sunny-grove/places-other
 * Inserter: no
 *
 * @package SunnyGrove
 */

echo sunny_grove_section_open( '', 'sg-section--other' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_bound_heading( 'other_places' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_query_open( 'sg_place', 12, array( 'type' => 'default' ), 'is-style-sg-carousel', array( 'sgExcludeCurrent' => true ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_place_card(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_query_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
