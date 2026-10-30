<?php
/**
 * Title: Tiện ích khác (trang chi tiết tiện ích)
 * Slug: sunny-grove/amenities-other
 * Inserter: no
 *
 * @package SunnyGrove
 */

echo sunny_grove_section_open( '', 'sg-section--other' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_bound_heading( 'other_amenities' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_query_open( 'sg_amenity', 4, array( 'type' => 'default' ), 'sg-grid sg-grid--4', array( 'sgExcludeCurrent' => true ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_amenity_card(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_query_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
