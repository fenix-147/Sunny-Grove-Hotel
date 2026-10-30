/* global wp */
( function () {
	'use strict';
	var el = wp.element.createElement;
	var be = wp.blockEditor;
	var c = wp.components;
	var __ = wp.i18n.__;

	wp.blocks.registerBlockType( 'sunny-grove-booking/booking-form', {
		edit: function ( props ) {
			var a = props.attributes;
			return el( wp.element.Fragment, {},
				el( be.InspectorControls, {},
					el( c.PanelBody, { title: __( 'Kiểu form', 'sunny-grove-booking' ), initialOpen: true },
						el( c.SelectControl, {
							label: __( 'Kiểu', 'sunny-grove-booking' ),
							value: a.variant,
							options: [
								{ value: 'full', label: __( 'Đầy đủ (trang Đặt phòng)', 'sunny-grove-booking' ) },
								{ value: 'compact', label: __( 'Gọn – chọn ngày & số khách (trang chủ)', 'sunny-grove-booking' ) }
							],
							onChange: function ( v ) { props.setAttributes( { variant: v } ); }
						} ),
						el( 'p', {}, __( 'Sửa chữ trên form và nội dung email tại menu Đặt phòng → Cài đặt.', 'sunny-grove-booking' ) )
					)
				),
				el( 'div', be.useBlockProps(), el( wp.serverSideRender, { block: 'sunny-grove-booking/booking-form', attributes: a } ) )
			);
		},
		save: function () {
			return null;
		}
	} );
}() );
