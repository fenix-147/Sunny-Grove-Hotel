<?php
/**
 * Title: Bài viết khác (trang bài viết)
 * Slug: sunny-grove/posts-related
 * Inserter: no
 *
 * @package SunnyGrove
 */

echo sunny_grove_section_open( '', 'sg-section--other' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_bound_heading( 'related_posts' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_query_open( 'post', 3, array( 'type' => 'default' ), 'sg-grid', array( 'sgExcludeCurrent' => true ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_post_card(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_query_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
