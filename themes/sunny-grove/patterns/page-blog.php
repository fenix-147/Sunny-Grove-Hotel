<?php
/**
 * Title: Trang mẫu – Blog
 * Slug: sunny-grove/page-blog
 * Categories: sunny-grove-pages
 * Block Types: core/post-content
 * Post Types: page
 * Viewport Width: 1400
 * Description: Banner và lưới bài viết có phân trang.
 *
 * @package SunnyGrove
 */

echo sunny_grove_banner( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	array(
		'media'   => 'banner-blog',
		'video'   => 'video:blog',
		'height'  => 'small',
		'eyebrow' => __( 'Blog', 'sunny-grove' ),
		'title'   => __( 'Cẩm nang Phú Quốc cho gia đình', 'sunny-grove' ),
		'text'    => __( 'Lịch trình gợi ý, món ngon cho bé và những góc chụp ảnh đẹp nhất đảo.', 'sunny-grove' ),
	)
);
echo sunny_grove_pattern( 'blog-grid' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
