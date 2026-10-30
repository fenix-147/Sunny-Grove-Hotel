/* global wp */
/*
 * Sunny Grove – trình soạn thảo chỉ lấy bài cùng ngôn ngữ với trang đang sửa.
 * Polylang bản miễn phí không lọc ngôn ngữ cho request REST, nên Query Loop (phòng, tiện ích, địa điểm, ưu đãi, bài viết)
 * hiện lẫn cả hai thứ tiếng. Script này gắn tham số sg_lang vào các request đó; máy chủ lọc theo tham số này.
 * Khi sửa template trong Site Editor (không thuộc ngôn ngữ nào), dùng ngôn ngữ mặc định.
 */
( function () {
	'use strict';
	var cfg = window.sgcEditorLang;
	if ( ! cfg || ! wp.apiFetch || ! wp.url ) {
		return;
	}
	var collection = new RegExp( '^/?wp/v2/(' + cfg.bases.join( '|' ) + ')/?(\\?|$)' );
	var renderer = /^\/?wp\/v2\/block-renderer\//;

	function editorLang() {
		var editor = wp.data.select( 'core/editor' );
		var type = editor && editor.getCurrentPostType ? editor.getCurrentPostType() : null;
		var id = editor && editor.getCurrentPostId ? editor.getCurrentPostId() : null;
		if ( type && cfg.translated.indexOf( type ) === -1 ) {
			return cfg.default; // Template, template part, menu…
		}
		if ( type && id ) {
			var record = wp.data.select( 'core' ).getEntityRecord( 'postType', type, id );
			if ( record && record.sg_lang ) {
				return record.sg_lang;
			}
			if ( String( id ) === String( cfg.postId ) && cfg.lang ) {
				return cfg.lang;
			}
		}
		return cfg.lang || cfg.default;
	}

	// Cho script khác (bindings-editor.js) biết ngôn ngữ đang sửa.
	window.sgcEditorCurrentLang = editorLang;

	wp.apiFetch.use( function ( options, next ) {
		var path = options.path || '';
		var method = ( options.method || 'GET' ).toUpperCase();
		var wanted = ( method === 'GET' && collection.test( path ) ) || renderer.test( path );
		if ( path && wanted && ! /[?&]sg_lang=/.test( path ) ) {
			options = Object.assign( {}, options, { path: wp.url.addQueryArgs( path, { sg_lang: editorLang() } ) } );
		}
		return next( options );
	} );
}() );
