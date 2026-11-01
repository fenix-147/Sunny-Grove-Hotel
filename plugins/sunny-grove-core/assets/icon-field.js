/*
 * Ô chọn icon của tiện nghi phòng: lọc lưới icon theo chữ gõ (tiếng Việt có/không dấu, tiếng Anh).
 */
( function () {
	'use strict';
	function fold( text ) {
		return String( text || '' ).toLowerCase().normalize( 'NFD' ).replace( /[\u0300-\u036f]/g, '' ).replace( /đ/g, 'd' );
	}
	function setup( field ) {
		var input = field.querySelector( '.sgc-icon-field__search' );
		var none = field.querySelector( '.sgc-icon-field__none' );
		var items = Array.prototype.slice.call( field.querySelectorAll( '.sgc-icon-field__item' ) );
		items.forEach( function ( item ) {
			item.sgSearch = fold( item.getAttribute( 'data-search' ) );
		} );
		input.addEventListener( 'input', function () {
			var words = fold( input.value ).split( /\s+/ ).filter( Boolean );
			var shown = 0;
			items.forEach( function ( item ) {
				var ok = words.every( function ( w ) {
					return item.sgSearch.indexOf( w ) !== -1;
				} );
				item.hidden = ! ok;
				shown += ok ? 1 : 0;
			} );
			none.hidden = shown > 0;
		} );
		// Enter trong ô tìm không gửi form.
		input.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Enter' ) {
				e.preventDefault();
			}
		} );
	}
	document.querySelectorAll( '.sgc-icon-field' ).forEach( setup );
	// Form "Thêm tiện nghi" gửi bằng AJAX rồi xóa trắng: chọn lại icon mặc định.
	if ( window.jQuery ) {
		window.jQuery( document ).ajaxComplete( function ( e, xhr, settings ) {
			if ( settings && typeof settings.data === 'string' && settings.data.indexOf( 'action=add-tag' ) !== -1 ) {
				var first = document.querySelector( '#addtag .sgc-icon-field input[value="leaf"]' );
				if ( first ) {
					first.checked = true;
				}
			}
		} );
	}
}() );
