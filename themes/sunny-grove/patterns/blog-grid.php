<?php
/**
 * Title: Lưới bài viết blog
 * Slug: sunny-grove/blog-grid
 * Categories: sunny-grove
 * Keywords: blog, bài viết, cẩm nang
 * Description: Bài viết mới nhất, có phân trang.
 *
 * @package SunnyGrove
 */

echo sunny_grove_section_open(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_query_open( 'post', 9, array( 'type' => 'default' ), 'sg-grid' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_post_card(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_query_close( 'posts_empty', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
