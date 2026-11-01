<?php
/**
 * Bộ icon SVG nét mảnh (tự host, dữ liệu trong data/icons.json).
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Icon mặc định cho mục danh sách / tiện nghi chưa chọn icon.
 */
const SGC_DEFAULT_ICON = 'leaf';

/**
 * Toàn bộ icon: tên => array( d, vi?, en?, k? ).
 *
 * @return array
 */
function sgc_icons() {
	return (array) ( sgc_data( 'icons' )['icons'] ?? array() );
}

/**
 * Icon chọn được cho danh sách / tiện nghi (có tên VI, EN và từ khóa tìm kiếm).
 *
 * @return array tên => array( vi, en, k, d )
 */
function sgc_icon_choices() {
	return array_filter(
		sgc_icons(),
		static function ( $icon ) {
			return ! empty( $icon['vi'] );
		}
	);
}

/**
 * Tên icon hợp lệ để chọn, hoặc icon mặc định.
 *
 * @param string $name Tên icon.
 * @return string
 */
function sgc_icon_or_default( $name ) {
	$name = sanitize_key( (string) $name );
	return isset( sgc_icon_choices()[ $name ] ) ? $name : SGC_DEFAULT_ICON;
}

/**
 * Trả về mã SVG của icon (chỉ trang trí: aria-hidden).
 *
 * @param string $name  Tên icon.
 * @param int    $size  Kích thước px.
 * @param string $class Class thêm.
 * @return string
 */
function sgc_icon( $name, $size = 20, $class = '' ) {
	$icons = sgc_icons();
	if ( empty( $icons[ $name ]['d'] ) ) {
		return '';
	}
	return sprintf(
		'<svg class="sg-icon sg-icon--%1$s%4$s" width="%2$d" height="%2$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%3$s</svg>',
		esc_attr( $name ),
		(int) $size,
		$icons[ $name ]['d'],
		$class ? ' ' . esc_attr( $class ) : ''
	);
}
