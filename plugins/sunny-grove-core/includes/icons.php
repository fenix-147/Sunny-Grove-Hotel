<?php
/**
 * Bộ icon SVG nét mảnh dùng trong các block.
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Trả về mã SVG của icon.
 *
 * @param string $name Tên icon.
 * @param int    $size Kích thước px.
 * @return string
 */
function sgc_icon( $name, $size = 20 ) {
	$paths = array(
		'adult'     => '<circle cx="12" cy="7" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>',
		'child'     => '<circle cx="12" cy="6" r="3"/><path d="M8 21v-5l-2-3 4-2h4l4 2-2 3v5"/><path d="M12 16v5"/>',
		'area'      => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>',
		'bed'       => '<path d="M3 18V6M3 13h18v5M21 18v-5a3 3 0 0 0-3-3h-7v3"/><circle cx="7" cy="10" r="2"/>',
		'view'      => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
		'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'pin'       => '<path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
		'walk'      => '<circle cx="13" cy="4" r="2"/><path d="M9 21l2-6 3 3v3M7 12l2-4 4 1 3 3M11 15l1-5"/>',
		'car'       => '<path d="M5 16V11l2-5h10l2 5v5M3 16h18v3H3zM5 11h14"/><circle cx="7.5" cy="16" r="1"/><circle cx="16.5" cy="16" r="1"/>',
		'motorbike' => '<circle cx="5.5" cy="16.5" r="3.5"/><circle cx="18.5" cy="16.5" r="3.5"/><path d="M5.5 16.5h6l3-6h3M14 6h3l1.5 4.5M9 10.5h5"/>',
		'phone'     => '<path d="M5 3h4l2 5-2.5 1.5a11 11 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 5a2 2 0 0 1 2-2z"/>',
		'mail'      => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
		'chat'      => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/>',
		'zalo'      => '<path d="M21 11.5a8.5 8.5 0 0 1-12.4 7.5L4 20l1.2-4.1A8.5 8.5 0 1 1 21 11.5z"/><path d="M8.5 9h4l-4 5h4M15 9v5"/>',
		'whatsapp'  => '<path d="M20.5 11.7a8.5 8.5 0 0 1-12.6 7.4L3.5 20.5l1.4-4.2a8.5 8.5 0 1 1 15.6-4.6z"/><path d="M9 8.5c0 3.5 2.6 6.5 6.5 6.5l1-1.6-2-1-1 .9a5 5 0 0 1-2.8-2.8l.9-1-1-2z"/>',
		'check'     => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
		'prev'      => '<path d="M15 5l-7 7 7 7"/>',
		'next'      => '<path d="M9 5l7 7-7 7"/>',
		'menu'      => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'close'     => '<path d="M6 6l12 12M18 6L6 18"/>',
		'play'      => '<path d="M8 5v14l11-7z"/>',
		'pause'     => '<path d="M8 5v14M16 5v14"/>',
		'calendar'  => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
		'tag'       => '<path d="M3 12V4h8l10 10-8 8z"/><circle cx="7.5" cy="8.5" r="1.5"/>',
		'external'  => '<path d="M14 4h6v6M20 4l-9 9M18 14v6H4V6h6"/>',
		'globe'     => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return sprintf(
		'<svg class="sg-icon sg-icon--%1$s" width="%2$d" height="%2$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%3$s</svg>',
		esc_attr( $name ),
		(int) $size,
		$paths[ $name ]
	);
}
