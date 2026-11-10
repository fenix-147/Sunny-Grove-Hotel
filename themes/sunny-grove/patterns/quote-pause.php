<?php
/**
 * Title: Khoảng lặng (ảnh tràn viền + câu trích dẫn)
 * Slug: sunny-grove/quote-pause
 * Categories: sunny-grove
 * Keywords: trích dẫn, quote, ảnh tràn viền, khoảng lặng
 * Description: Ảnh tràn viền cao 560px với một câu trích dẫn chữ nghiêng ở giữa. Bấm vào chữ để sửa, đổi ảnh trong thanh bên.
 *
 * @package SunnyGrove
 */

echo sunny_grove_banner( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	array(
		'media'   => 'pause',
		'height'  => 'medium',
		'overlay' => 38,
		'class'   => 'sg-pause',
		'inner'   => sunny_grove_paragraph( esc_html__( '“Ở đây, một ngày bắt đầu bằng tiếng sóng và kết thúc bằng tiếng cười của bọn trẻ.”', 'sunny-grove' ), 'sg-pause__quote', true )
			. sunny_grove_eyebrow( __( 'Sunny Grove · Phú Quốc', 'sunny-grove' ), true ),
	)
);
