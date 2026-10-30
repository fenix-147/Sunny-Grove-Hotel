<?php
/**
 * Title: Trang mẫu – Ưu đãi
 * Slug: sunny-grove/page-offers
 * Categories: sunny-grove-pages
 * Block Types: core/post-content
 * Post Types: page
 * Viewport Width: 1400
 * Description: Banner video và các ưu đãi/combo còn hạn.
 *
 * @package SunnyGrove
 */

echo sunny_grove_banner( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	array(
		'media'   => 'banner-offers',
		'video'   => 'video:offers',
		'eyebrow' => __( 'Ưu đãi & Combo', 'sunny-grove' ),
		'title'   => __( 'Nhiều niềm vui hơn, chi phí nhẹ hơn', 'sunny-grove' ),
		'text'    => __( 'Combo gia đình, ở dài ngày và đặt sớm – áp dụng khi đặt trực tiếp với Sunny Grove.', 'sunny-grove' ),
	)
);
echo sunny_grove_pattern( 'offer-grid' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_pattern( 'cta-booking' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
