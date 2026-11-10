/* Sunny Grove – nút "Chat" trên điện thoại: bấm để mở/đóng danh sách Zalo, WhatsApp, Gọi.
   Đóng bằng phím Esc hoặc bấm ra ngoài. Máy tính không dùng nút này (danh sách luôn hiện). */
( function () {
	'use strict';

	function init() {
		var root = document.querySelector( '.sg-chat' );
		var btn = root && root.querySelector( '.sg-chat__toggle' );
		if ( ! btn ) {
			return;
		}

		function isOpen() {
			return btn.getAttribute( 'aria-expanded' ) === 'true';
		}
		function setOpen( open ) {
			btn.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			root.classList.toggle( 'is-open', open );
		}

		btn.addEventListener( 'click', function () {
			setOpen( ! isOpen() );
		} );
		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' && isOpen() ) {
				setOpen( false );
				btn.focus();
			}
		} );
		document.addEventListener( 'click', function ( e ) {
			if ( isOpen() && ! root.contains( e.target ) ) {
				setOpen( false );
			}
		} );
		// Tab ra khỏi danh sách → đóng lại.
		root.addEventListener( 'focusout', function ( e ) {
			if ( isOpen() && e.relatedTarget && ! root.contains( e.relatedTarget ) ) {
				setOpen( false );
			}
		} );
		// Chuyển sang màn hình rộng (xoay máy, đổi cỡ cửa sổ) → bỏ trạng thái mở.
		if ( window.matchMedia ) {
			var wide = window.matchMedia( '(min-width: 600px)' );
			var reset = function () {
				if ( wide.matches ) {
					setOpen( false );
				}
			};
			if ( wide.addEventListener ) {
				wide.addEventListener( 'change', reset );
			}
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
