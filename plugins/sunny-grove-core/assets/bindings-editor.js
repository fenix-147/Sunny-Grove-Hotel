/* global wp, sgcBindingPreview */
( function () {
	'use strict';
	if ( ! wp.blocks || ! wp.blocks.registerBlockBindingsSource ) {
		return;
	}
	var data = window.sgcBindingPreview || { labels: {}, hotel: {} };

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
			return values( args.bindings, function ( key, attr ) {
				return attr === 'url' ? '#' : ( data.labels[ key ] || key ).replace( '{nam}', new Date().getFullYear() );
			} );
		}
	} );

	wp.blocks.registerBlockBindingsSource( {
		name: 'sunny-grove/hotel',
		getValues: function ( args ) {
			return values( args.bindings, function ( key, attr ) {
				return attr === 'url' ? '#' : ( data.hotel[ key ] || key );
			} );
		}
	} );

	// Trường của phòng/địa điểm/ưu đãi: hiển thị giá trị thô trong trình soạn thảo, ngoài website sẽ được định dạng đầy đủ.
	var metaMap = {
		price: 'sg_price_from', adults: 'sg_adults', children: 'sg_children', area: 'sg_area', bed: 'sg_bed',
		view: 'sg_view', hours: 'sg_hours', hours_line: 'sg_hours', short: 'sg_short', compare: 'sg_compare',
		distance: 'sg_distance', travel: 'sg_time', offer_price: 'sg_price', old_price: 'sg_old_price',
		badge: 'sg_badge', valid_until: 'sg_valid_to', conditions: 'sg_conditions', map_url: 'sg_map_url'
	};
	wp.blocks.registerBlockBindingsSource( {
		name: 'sunny-grove/meta',
		usesContext: [ 'postId', 'postType' ],
		getValues: function ( args ) {
			var ctx = args.context || {};
			var record = ctx.postId && ctx.postType ?
				args.select( 'core' ).getEditedEntityRecord( 'postType', ctx.postType, ctx.postId ) : null;
			var meta = ( record && record.meta ) || {};
			return values( args.bindings, function ( key, attr ) {
				if ( attr === 'url' ) {
					return '#';
				}
				var v = metaMap[ key ] ? meta[ metaMap[ key ] ] : '';
				return ( v !== undefined && v !== '' && v !== null ) ? String( v ) : '[' + key + ']';
			} );
		}
	} );
}() );
