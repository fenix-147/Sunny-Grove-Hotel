<?php
/**
 * Title: Danh sách bài viết (trang lưu trữ)
 * Slug: sunny-grove/posts-loop
 * Inserter: no
 *
 * @package SunnyGrove
 */

echo sunny_grove_query_open( 'post', 9, array( 'type' => 'default' ), 'sg-grid', array( 'inherit' => true ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_post_card(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_query_close( 'posts_empty', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
