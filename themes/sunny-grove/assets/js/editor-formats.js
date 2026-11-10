/* Sunny Grove – định dạng "Vế nghiêng" trên thanh công cụ chữ.
   Bôi đen phần chữ (thường là vế thứ hai của tiêu đề) → bấm nút → chữ in nghiêng màu đất nung
   (trên nền tối/ảnh tự đổi sang màu đào nhạt). Trong tiêu đề, vế nghiêng tự xuống dòng riêng.
   Lưu thành <em class="sg-accent">…</em>, dùng được ở cả chế độ VI và EN. */
( function ( wp ) {
	'use strict';
	if ( ! wp || ! wp.richText || ! wp.blockEditor ) {
		return;
	}
	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var NAME = 'sunny-grove/accent';

	var icon = el(
		'svg',
		{ xmlns: 'http://www.w3.org/2000/svg', viewBox: '0 0 24 24', width: 24, height: 24, 'aria-hidden': true },
		el( 'path', { d: 'M10 5h7M7 19h7M14 5l-4 14', fill: 'none', stroke: 'currentColor', strokeWidth: 1.6, strokeLinecap: 'round' } ),
		el( 'path', { d: 'M17 15.5c1.2 0 2-.8 2-2', fill: 'none', stroke: '#9C5A3C', strokeWidth: 1.6, strokeLinecap: 'round' } )
	);

	wp.richText.registerFormatType( NAME, {
		title: __( 'Vế nghiêng', 'sunny-grove' ),
		tagName: 'em',
		className: 'sg-accent',
		edit: function ( props ) {
			return el( wp.blockEditor.RichTextToolbarButton, {
				icon: icon,
				title: __( 'Vế nghiêng', 'sunny-grove' ),
				isActive: props.isActive,
				onClick: function () {
					props.onChange( wp.richText.toggleFormat( props.value, { type: NAME } ) );
				}
			} );
		}
	} );
}( window.wp ) );
