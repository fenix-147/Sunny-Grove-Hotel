/* global wp, sgcEditor */
( function () {
	'use strict';

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var be = wp.blockEditor;
	var c = wp.components;
	var SSR = wp.serverSideRender;
	var __ = wp.i18n.__;
	var taxonomies = ( window.sgcEditor && window.sgcEditor.taxonomies ) || {};

	function options( map ) {
		return Object.keys( map ).map( function ( k ) {
			return { value: k, label: map[ k ] };
		} );
	}

	// Xem trước bằng server (truyền ID bài trong Query Loop để hiển thị đúng dữ liệu).
	function preview( name, props ) {
		var ctx = props.context || {};
		return el( 'div', be.useBlockProps(),
			el( SSR, {
				block: name,
				attributes: props.attributes,
				urlQueryArgs: ctx.postId ? { post_id: ctx.postId } : {},
				EmptyResponsePlaceholder: function () {
					return el( 'div', { className: 'sgc-empty-preview' }, __( 'Chưa có dữ liệu để hiển thị (xem ngoài website để kiểm tra).', 'sunny-grove-core' ) );
				}
			} )
		);
	}

	function panel( title, children ) {
		return el( be.InspectorControls, {}, el( c.PanelBody, { title: title, initialOpen: true }, children ) );
	}

	function ssrBlock( name, controls ) {
		wp.blocks.registerBlockType( name, {
			edit: function ( props ) {
				return el( Fragment, {},
					controls ? panel( __( 'Cài đặt', 'sunny-grove-core' ), controls( props ) ) : null,
					preview( name, props )
				);
			},
			save: function () {
				return null;
			}
		} );
	}

	/* ---------- Banner video ---------- */
	function mediaButton( label, type, onSelect, current ) {
		return el( be.MediaUploadCheck, {},
			el( be.MediaUpload, {
				allowedTypes: [ type ],
				value: current,
				onSelect: onSelect,
				render: function ( o ) {
					return el( c.Button, { variant: 'secondary', onClick: o.open, style: { marginBottom: '12px' } }, label );
				}
			} )
		);
	}

	wp.blocks.registerBlockType( 'sunny-grove/video-banner', {
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;
			var blockProps = be.useBlockProps( {
				className: 'sg-banner sg-banner--' + a.height + ' sg-banner--' + a.contentPosition,
				style: { '--sg-overlay': a.overlay / 100 }
			} );
			var inner = be.useInnerBlocksProps( { className: 'sg-banner__content' }, {
				template: [
					[ 'core/heading', { level: 1, placeholder: __( 'Tiêu đề lớn…', 'sunny-grove-core' ) } ],
					[ 'core/paragraph', { placeholder: __( 'Một câu mô tả ngắn…', 'sunny-grove-core' ) } ]
				]
			} );
			return el( Fragment, {},
				el( be.InspectorControls, {},
					el( c.PanelBody, { title: __( 'Video & ảnh nền', 'sunny-grove-core' ), initialOpen: true },
						el( 'p', {}, __( 'Ảnh poster hiện ngay khi tải trang (giúp trang nhanh), video sẽ tự chạy sau đó. Video nên là MP4, 10–20 giây, dưới 5MB.', 'sunny-grove-core' ) ),
						mediaButton( a.posterId ? __( 'Đổi ảnh poster', 'sunny-grove-core' ) : __( 'Chọn ảnh poster', 'sunny-grove-core' ), 'image', function ( m ) {
							set( { posterId: m.id, posterUrl: m.url } );
						}, a.posterId ),
						el( c.TextControl, { label: __( 'Video cho máy tính (link MP4)', 'sunny-grove-core' ), value: a.videoUrl, onChange: function ( v ) { set( { videoUrl: v } ); } } ),
						mediaButton( __( 'Chọn video từ Thư viện', 'sunny-grove-core' ), 'video', function ( m ) { set( { videoUrl: m.url } ); }, null ),
						el( c.TextControl, { label: __( 'Video nhẹ cho điện thoại (tùy chọn)', 'sunny-grove-core' ), value: a.videoMobileUrl, onChange: function ( v ) { set( { videoMobileUrl: v } ); } } ),
						mediaButton( __( 'Chọn video điện thoại', 'sunny-grove-core' ), 'video', function ( m ) { set( { videoMobileUrl: m.url } ); }, null )
					),
					el( c.PanelBody, { title: __( 'Bố cục', 'sunny-grove-core' ), initialOpen: false },
						el( c.RangeControl, { label: __( 'Độ tối lớp phủ (giúp chữ dễ đọc)', 'sunny-grove-core' ), min: 0, max: 90, value: a.overlay, onChange: function ( v ) { set( { overlay: v } ); } } ),
						el( c.SelectControl, {
							label: __( 'Chiều cao', 'sunny-grove-core' ), value: a.height,
							options: options( { full: __( 'Toàn màn hình', 'sunny-grove-core' ), large: __( 'Lớn', 'sunny-grove-core' ), medium: __( 'Vừa', 'sunny-grove-core' ), small: __( 'Thấp', 'sunny-grove-core' ) } ),
							onChange: function ( v ) { set( { height: v } ); }
						} ),
						el( c.SelectControl, {
							label: __( 'Vị trí chữ', 'sunny-grove-core' ), value: a.contentPosition,
							options: options( { center: __( 'Giữa', 'sunny-grove-core' ), bottom: __( 'Dưới', 'sunny-grove-core' ) } ),
							onChange: function ( v ) { set( { contentPosition: v } ); }
						} )
					)
				),
				el( 'section', blockProps,
					el( 'div', { className: 'sg-banner__media', 'aria-hidden': true },
						a.posterUrl ? el( 'img', { className: 'sg-banner__poster', src: a.posterUrl, alt: '' } ) : null
					),
					el( 'div', inner )
				)
			);
		},
		save: function () {
			return el( be.InnerBlocks.Content );
		}
	} );

	/* ---------- Các block hiển thị dữ liệu ---------- */
	ssrBlock( 'sunny-grove/motion-media', function ( p ) {
		var a = p.attributes;
		return [
			el( c.SelectControl, {
				key: 'r', label: __( 'Tỉ lệ khung', 'sunny-grove-core' ), value: a.aspectRatio,
				options: options( { '4/3': '4:3', '3/2': '3:2', '16/9': '16:9', '1/1': '1:1', '3/4': '3:4' } ),
				onChange: function ( v ) { p.setAttributes( { aspectRatio: v } ); }
			} ),
			el( c.ToggleControl, { key: 'l', label: __( 'Bấm vào ảnh để mở chi tiết', 'sunny-grove-core' ), checked: a.isLink, onChange: function ( v ) { p.setAttributes( { isLink: v } ); } } ),
			el( c.ToggleControl, { key: 'v', label: __( 'Tự chạy video ngắn (nếu có)', 'sunny-grove-core' ), checked: a.showVideo, onChange: function ( v ) { p.setAttributes( { showVideo: v } ); } } ),
			el( c.ToggleControl, { key: 'b', label: __( 'Hiện nhãn nổi bật (ưu đãi)', 'sunny-grove-core' ), checked: a.showBadge, onChange: function ( v ) { p.setAttributes( { showBadge: v } ); } } )
		];
	} );

	ssrBlock( 'sunny-grove/media-gallery', function ( p ) {
		var a = p.attributes;
		return [
			el( c.SelectControl, {
				key: 'l', label: __( 'Kiểu hiển thị', 'sunny-grove-core' ), value: a.layout,
				options: options( { grid: __( 'Lưới', 'sunny-grove-core' ), scroller: __( 'Trượt ngang', 'sunny-grove-core' ) } ),
				onChange: function ( v ) { p.setAttributes( { layout: v } ); }
			} ),
			el( c.RangeControl, { key: 'm', label: __( 'Số ảnh tối đa', 'sunny-grove-core' ), min: 1, max: 24, value: a.max, onChange: function ( v ) { p.setAttributes( { max: v } ); } } ),
			el( c.ToggleControl, { key: 't', label: __( 'Hiện tiêu đề “Hình ảnh thực tế”', 'sunny-grove-core' ), checked: a.showTitle, onChange: function ( v ) { p.setAttributes( { showTitle: v } ); } } )
		];
	} );

	function taxSelect( p, only ) {
		var map = {};
		Object.keys( taxonomies ).forEach( function ( k ) {
			if ( ! only || only.indexOf( k ) > -1 ) {
				map[ k ] = taxonomies[ k ];
			}
		} );
		return el( c.SelectControl, {
			key: 'tax', label: __( 'Nhóm phân loại', 'sunny-grove-core' ), value: p.attributes.taxonomy,
			options: options( map ), onChange: function ( v ) { p.setAttributes( { taxonomy: v } ); }
		} );
	}

	ssrBlock( 'sunny-grove/term-filter', function ( p ) {
		return [
			taxSelect( p, [ 'sg_guest_type', 'sg_place_tag' ] ),
			el( 'p', { key: 'h' }, __( 'Đặt block này ngay phía trên Query Loop cần lọc.', 'sunny-grove-core' ) )
		];
	} );

	ssrBlock( 'sunny-grove/post-terms-list', function ( p ) {
		var a = p.attributes;
		return [
			taxSelect( p ),
			el( c.SelectControl, {
				key: 's', label: __( 'Kiểu', 'sunny-grove-core' ), value: a.variant,
				options: options( { checks: __( 'Danh sách có dấu tích', 'sunny-grove-core' ), chips: __( 'Thẻ nhỏ', 'sunny-grove-core' ) } ),
				onChange: function ( v ) { p.setAttributes( { variant: v } ); }
			} ),
			el( c.ToggleControl, { key: 't', label: __( 'Hiện tiêu đề', 'sunny-grove-core' ), checked: a.showTitle, onChange: function ( v ) { p.setAttributes( { showTitle: v } ); } } )
		];
	} );

	var contactFields = {
		name: __( 'Tên khách sạn', 'sunny-grove-core' ),
		about: __( 'Giới thiệu ngắn', 'sunny-grove-core' ),
		phone: __( 'Hotline', 'sunny-grove-core' ),
		zalo: 'Zalo',
		whatsapp: 'WhatsApp',
		email: 'Email',
		address: __( 'Địa chỉ', 'sunny-grove-core' ),
		checkin: __( 'Giờ nhận / trả phòng', 'sunny-grove-core' )
	};
	ssrBlock( 'sunny-grove/hotel-info', function ( p ) {
		var a = p.attributes;
		var list = [ el( 'p', { key: 'h' }, __( 'Sửa nội dung tại menu Sunny Grove → Thông tin khách sạn.', 'sunny-grove-core' ) ) ];
		Object.keys( contactFields ).forEach( function ( k ) {
			list.push( el( c.CheckboxControl, {
				key: k, label: contactFields[ k ], checked: a.fields.indexOf( k ) > -1,
				onChange: function ( on ) {
					var next = a.fields.filter( function ( f ) { return f !== k; } );
					if ( on ) {
						next.push( k );
					}
					p.setAttributes( { fields: next } );
				}
			} ) );
		} );
		list.push( el( c.SelectControl, {
			key: 'l', label: __( 'Kiểu hiển thị', 'sunny-grove-core' ), value: a.layout,
			options: options( { list: __( 'Danh sách', 'sunny-grove-core' ), inline: __( 'Một hàng', 'sunny-grove-core' ), buttons: __( 'Nút bấm', 'sunny-grove-core' ) } ),
			onChange: function ( v ) { p.setAttributes( { layout: v } ); }
		} ) );
		list.push( el( c.ToggleControl, { key: 'm', label: __( 'Hiện bản đồ Google Maps', 'sunny-grove-core' ), checked: a.showMap, onChange: function ( v ) { p.setAttributes( { showMap: v } ); } } ) );
		return list;
	} );

	ssrBlock( 'sunny-grove/menu', function ( p ) {
		var a = p.attributes;
		return [
			el( c.SelectControl, {
				key: 'loc', label: __( 'Vị trí menu', 'sunny-grove-core' ), value: a.location,
				options: options( { primary: __( 'Menu chính (đầu trang)', 'sunny-grove-core' ), footer: __( 'Menu chân trang', 'sunny-grove-core' ) } ),
				onChange: function ( v ) { p.setAttributes( { location: v } ); }
			} ),
			el( c.ToggleControl, { key: 't', label: __( 'Nút mở menu trên điện thoại', 'sunny-grove-core' ), checked: a.mobileToggle, onChange: function ( v ) { p.setAttributes( { mobileToggle: v } ); } } ),
			el( 'p', { key: 'h' }, __( 'Sửa mục menu tại Giao diện → Menu (mỗi ngôn ngữ một menu).', 'sunny-grove-core' ) )
		];
	} );

	ssrBlock( 'sunny-grove/language-switcher', function ( p ) {
		return [ el( c.SelectControl, {
			key: 'd', label: __( 'Hiển thị', 'sunny-grove-core' ), value: p.attributes.display,
			options: options( { code: 'VI | EN', name: 'Tiếng Việt | English' } ),
			onChange: function ( v ) { p.setAttributes( { display: v } ); }
		} ) ];
	} );

	ssrBlock( 'sunny-grove/room-facts', function ( p ) {
		return [ el( c.ToggleControl, { key: 'c', label: __( 'Gọn (dùng cho thẻ phòng)', 'sunny-grove-core' ), checked: p.attributes.compact, onChange: function ( v ) { p.setAttributes( { compact: v } ); } } ) ];
	} );

	ssrBlock( 'sunny-grove/place-facts', function ( p ) {
		var a = p.attributes;
		return [
			el( c.ToggleControl, { key: 'c', label: __( 'Hiện dòng so sánh vui', 'sunny-grove-core' ), checked: a.showCompare, onChange: function ( v ) { p.setAttributes( { showCompare: v } ); } } ),
			el( c.ToggleControl, { key: 'm', label: __( 'Hiện nút chỉ đường', 'sunny-grove-core' ), checked: a.showMap, onChange: function ( v ) { p.setAttributes( { showMap: v } ); } } )
		];
	} );
}() );
