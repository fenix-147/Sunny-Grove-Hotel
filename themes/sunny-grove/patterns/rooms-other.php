<?php
/**
 * Title: Các phòng khác (trang chi tiết phòng)
 * Slug: sunny-grove/rooms-other
 * Inserter: no
 *
 * @package SunnyGrove
 */

echo sunny_grove_section_open( '', 'sg-section--other' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_bound_heading( 'other_rooms' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_query_open( 'sg_room', 3, array( 'type' => 'default' ), 'sg-grid', array( 'sgExcludeCurrent' => true ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_room_card(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_query_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
