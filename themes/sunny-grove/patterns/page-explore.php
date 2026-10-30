<?php
/**
 * Title: Trang mẫu – Khám phá Phú Quốc
 * Slug: sunny-grove/page-explore
 * Categories: sunny-grove-pages
 * Block Types: core/post-content
 * Post Types: page
 * Viewport Width: 1400
 * Description: Banner video, băng chuyền địa điểm có bộ lọc và mẹo đi chơi.
 *
 * @package SunnyGrove
 */

echo sunny_grove_banner( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	array(
		'media'   => 'banner-explore',
		'video'   => 'video:explore',
		'eyebrow' => __( 'Khám phá Phú Quốc', 'sunny-grove' ),
		'title'   => __( 'Cả hòn đảo là sân chơi', 'sunny-grove' ),
		'text'    => __( 'Biển xanh, cáp treo vượt biển, safari và phố đêm – tất cả chỉ cách Sunny Grove một chuyến xe ngắn.', 'sunny-grove' ),
	)
);
echo sunny_grove_pattern( 'places-carousel' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_pattern( 'explore-tips' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_pattern( 'cta-booking' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
