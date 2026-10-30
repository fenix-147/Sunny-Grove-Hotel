/* global jQuery, wp, sgcAdmin */
( function ( $ ) {
	'use strict';

	function thumb( att ) {
		if ( att.type === 'video' ) {
			return '<span class="sgc-thumb sgc-thumb--video" data-id="' + att.id + '"><span class="dashicons dashicons-video-alt3"></span></span>';
		}
		var url = ( att.sizes && att.sizes.thumbnail ) ? att.sizes.thumbnail.url : att.url;
		return '<span class="sgc-thumb" data-id="' + att.id + '"><img src="' + url + '" width="80" height="80" alt=""></span>';
	}

	$( document ).on( 'click', '.sgc-gallery-pick', function ( e ) {
		e.preventDefault();
		var $field = $( this ).closest( '.sgc-field' );
		var $input = $field.find( '.sgc-gallery-input' );
		var frame = wp.media( {
			title: sgcAdmin.galleryTitle,
			button: { text: sgcAdmin.choose },
			library: { type: [ 'image', 'video' ] },
			multiple: 'add'
		} );
		frame.on( 'open', function () {
			var selection = frame.state().get( 'selection' );
			( $input.val() || '' ).split( ',' ).filter( Boolean ).forEach( function ( id ) {
				var att = wp.media.attachment( id );
				att.fetch();
				selection.add( att );
			} );
		} );
		frame.on( 'select', function () {
			var items = frame.state().get( 'selection' ).toJSON();
			$input.val( items.map( function ( a ) { return a.id; } ).join( ',' ) );
			$field.find( '.sgc-gallery-preview' ).html( items.map( thumb ).join( '' ) );
		} );
		frame.open();
	} );

	$( document ).on( 'click', '.sgc-gallery-clear', function ( e ) {
		e.preventDefault();
		var $field = $( this ).closest( '.sgc-field' );
		$field.find( '.sgc-gallery-input' ).val( '' );
		$field.find( '.sgc-gallery-preview' ).empty();
	} );

	$( document ).on( 'click', '.sgc-video-pick', function ( e ) {
		e.preventDefault();
		var $input = $( this ).siblings( '.sgc-video-input' );
		var frame = wp.media( {
			title: sgcAdmin.videoTitle,
			button: { text: sgcAdmin.choose },
			library: { type: 'video' },
			multiple: false
		} );
		frame.on( 'select', function () {
			var att = frame.state().get( 'selection' ).first().toJSON();
			$input.val( att.url );
		} );
		frame.open();
	} );
}( jQuery ) );
