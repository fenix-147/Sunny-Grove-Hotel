/* global wp, sgcBindingPreview */
( function () {
	'use strict';

	// Bản dịch tiếng Việt của WordPress dịch nhầm mã cách đếm chữ "words" (chuỗi được ghi chú "Do not translate!")
	// → khối Trích đoạn trong trình soạn thảo cắt sai, chỉ còn "…". Giữ nguyên giá trị gốc cho chuỗi này.
	if ( wp.hooks ) {
		wp.hooks.addFilter( 'i18n.gettext_with_context', 'sunny-grove/word-count-type', function ( translation, text, context ) {
			return context === 'Word count type. Do not translate!' ? text : translation;
		} );
	}

	if ( ! wp.blocks || ! wp.blocks.registerBlockBindingsSource ) {
		return;
	}
	var data = window.sgcBindingPreview || { labels: {}, hotel: {} };

	// Ngôn ngữ đang gạt VI | EN (editor-i18n.js). Đọc qua select để block tự cập nhật khi gạt.
	function currentLang( select ) {
		return window.sgcEditorStore && select ? select( window.sgcEditorStore ).getMode() : data.default;
	}
	function forLang( group, select ) {
		var lang = currentLang( select );
		return group[ lang ] || group[ data.default ] || {};
	}

	function values( bindings, resolve ) {
		var out = {};
		Object.keys( bindings ).forEach( function ( attr ) {
			var key = ( bindings[ attr ].args || {} ).key || '';
			out[ attr ] = resolve( key, attr );
		} );
		return out;
	}

	wp.blocks.registerBlockBindingsSource( {
		name: 'sunny-grove/label',
		getValues: function ( args ) {
			var labels = forLang( data.labels, args.select );
			return values( args.bindings, function ( key, attr ) {
				return attr === 'url' ? '#' : ( labels[ key ] || key ).replace( '{nam}', new Date().getFullYear() );
			} );
		}
	} );

	wp.blocks.registerBlockBindingsSource( {
		name: 'sunny-grove/hotel',
		getValues: function ( args ) {
			var hotel = forLang( data.hotel, args.select );
			return values( args.bindings, function ( key, attr ) {
				return attr === 'url' ? '#' : ( hotel[ key ] || key );
			} );
		}
	} );

	// Trường của phòng/địa điểm/ưu đãi: máy chủ trả sẵn giá trị đã định dạng (trường REST sg_display, cùng hàm với
	// ngoài website) nên trình soạn thảo hiển thị giống hệt: "Từ 1.290.000đ / đêm", "Đến 30/04/2027"…
	var fields = data.fields || {};

	function escapeHtml( text ) {
		return String( text ).replace( /&/g, '&amp;' ).replace( /</g, '&lt;' ).replace( />/g, '&gt;' );
	}
	function displayValues( select, context ) {
		var ctx = context || {};
		if ( ! ctx.postId || ! ctx.postType ) {
			return {};
		}
		var record = select( 'core' ).getEntityRecord( 'postType', ctx.postType, ctx.postId );
		var all = ( record && record.sg_display ) || {};
		return all[ currentLang( select ) ] || all[ data.default ] || {};
	}

	wp.blocks.registerBlockBindingsSource( {
		name: 'sunny-grove/meta',
		usesContext: [ 'postId', 'postType' ],
		getValues: function ( args ) {
			var shown = displayValues( args.select, args.context );
			return values( args.bindings, function ( key, attr ) {
				if ( attr === 'url' ) {
					return '#';
				}
				// Ô trống → chuỗi rỗng; WordPress hiện gợi ý chữ xám lấy từ getFieldsList bên dưới.
				return shown[ key ] ? escapeHtml( shown[ key ] ) : '';
			} );
		},
		// Tên trường; khi trường còn trống thì tên là gợi ý "Chưa nhập …" (WordPress dùng làm chữ xám giữ chỗ).
		getFieldsList: function ( args ) {
			var shown = displayValues( args.select, args.context );
			return Object.keys( fields ).map( function ( key ) {
				return {
					label: shown[ key ] ? fields[ key ][ 0 ] : fields[ key ][ 1 ],
					type: 'string',
					args: { key: key }
				};
			} );
		}
	} );
}() );
