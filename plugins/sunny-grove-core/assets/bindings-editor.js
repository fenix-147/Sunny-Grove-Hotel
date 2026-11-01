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
				return attr === 'url' ? '#' : keepTogether( ( labels[ key ] || key ).replace( '{nam}', new Date().getFullYear() ), currentLang( args.select ) );
			} );
		}
	} );

	/* ---------- Thông tin khách sạn: người có quyền gõ thẳng vào block, bấm Lưu thì lưu về cài đặt ---------- */
	var bound = data.bound || { canEdit: false, editable: [], names: { hotel: {}, label: {} }, alias: {}, urls: {}, labels: {} };
	var ENTITY = [ 'sunny-grove', 'hotel-text' ];
	if ( bound.canEdit && wp.data.dispatch( 'core' ) && wp.data.dispatch( 'core' ).addEntities ) {
		wp.data.dispatch( 'core' ).addEntities( [ {
			kind: ENTITY[ 0 ],
			name: ENTITY[ 1 ],
			baseURL: '/sunny-grove/v1/hotel-text',
			label: bound.labels.entity,
			getTitle: function () {
				return bound.labels.entity;
			}
		} ] );
	}
	function isEditable( key ) {
		return bound.canEdit && bound.editable.indexOf( key ) !== -1;
	}
	// Chữ trong RichText là HTML → chuyển về chữ thường để lưu vào ô cài đặt.
	var scratch = document.createElement( 'div' );
	function htmlToText( html ) {
		scratch.innerHTML = String( html === undefined || html === null ? '' : html ).replace( /<br\s*\/?>/gi, ' ' );
		return ( scratch.textContent || '' ).replace( /[\s ]+/g, ' ' ).trim();
	}

	// Giữ liền cụm từ (ô "Giữ liền cụm từ" trong Thông tin khách sạn) – giống ngoài website.
	var keepCache = {};
	function keepRegex( lang ) {
		if ( ! ( lang in keepCache ) ) {
			var parts = ( ( data.keep || {} )[ lang ] || [] ).map( function ( p ) {
				return p.split( /\s+/ ).map( function ( w ) {
					return w.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
				} ).join( '[\\s\\u00a0]+' );
			} );
			keepCache[ lang ] = parts.length ? new RegExp( '(?<![\\p{L}\\p{N}])(?:' + parts.join( '|' ) + ')(?![\\p{L}\\p{N}])', 'giu' ) : null;
		}
		return keepCache[ lang ];
	}
	function keepTogether( text, lang ) {
		var re = keepRegex( lang );
		var out = re ? String( text ).replace( re, function ( m ) {
			return m.replace( /\s+/g, '\u00a0' );
		} ) : String( text );
		return out.replace( /[ \t]+([–—])(?=\s)/g, '\u00a0$1' );
	}
	// Tiêu đề H1 của banner: "Vế 1 – Vế 2" → xuống dòng sau dấu gạch (giống ngoài website).
	function isHeroTitle( select, clientId ) {
		if ( ! clientId ) {
			return false;
		}
		var store = select( 'core/block-editor' );
		var block = store.getBlock( clientId );
		var parent = store.getBlockRootClientId( clientId );
		return !! block && block.name === 'core/heading' && block.attributes.level === 1 && !! parent && store.getBlockName( parent ) === 'sunny-grove/video-banner';
	}
	function showText( text, lang, hero ) {
		var html = escapeHtml( keepTogether( text, lang ) );
		return hero ? html.replace( /(\u00a0[–—])\s+/, '$1<br>' ) : html;
	}

	wp.blocks.registerBlockBindingsSource( {
		name: 'sunny-grove/hotel',
		getValues: function ( args ) {
			var hotel = forLang( data.hotel, args.select );
			var lang = currentLang( args.select );
			var record = bound.canEdit ? args.select( 'core' ).getEditedEntityRecord( ENTITY[ 0 ], ENTITY[ 1 ] ) : null;
			var hero = isHeroTitle( args.select, args.clientId );
			return values( args.bindings, function ( key, attr ) {
				if ( attr === 'url' ) {
					return '#';
				}
				if ( isEditable( key ) && record && record[ key + '__' + lang ] !== undefined ) {
					return showText( record[ key + '__' + lang ], lang, hero );
				}
				return hotel[ key ] ? showText( hotel[ key ], lang, hero ) : key;
			} );
		},
		setValues: function ( args ) {
			var lang = currentLang( args.select );
			var edits = {};
			Object.keys( args.bindings ).forEach( function ( attr ) {
				var b = args.bindings[ attr ];
				var key = ( b.args || {} ).key || '';
				if ( isEditable( key ) && b.newValue !== undefined ) {
					edits[ key + '__' + lang ] = htmlToText( b.newValue );
				}
			} );
			if ( Object.keys( edits ).length ) {
				args.dispatch( 'core' ).editEntityRecord( ENTITY[ 0 ], ENTITY[ 1 ], undefined, edits );
			}
		},
		canUserEditValue: function ( args ) {
			return isEditable( ( args.args || {} ).key || '' );
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

	/* ---------- Khung "Nội dung này lấy từ … – sửa tại đây", nút trên thanh công cụ, viền nét đứt ---------- */
	var el = wp.element.createElement;
	var useSelect = wp.data.useSelect;
	var be = wp.blockEditor;
	var c = wp.components;
	var L = bound.labels;

	function sgBindings( attrs ) {
		var all = ( attrs && attrs.metadata && attrs.metadata.bindings ) || {};
		return Object.keys( all ).map( function ( attr ) {
			return { attr: attr, source: all[ attr ].source, key: ( all[ attr ].args || {} ).key || '' };
		} ).filter( function ( b ) {
			return /^sunny-grove\//.test( b.source || '' );
		} );
	}
	function fill( text, a, b ) {
		return String( text ).replace( '%1$s', a ).replace( '%2$s', b );
	}
	// Tên trường, nơi sửa và link tới đúng ô của một liên kết dữ liệu.
	function describe( b, ctx ) {
		var type = b.source.split( '/' )[ 1 ];
		if ( type === 'hotel' ) {
			var key = bound.alias[ b.key ] || b.key;
			return { name: bound.names.hotel[ key ] || key, place: L.hotelPlace, url: bound.urls.hotel + '#sgc-field-' + key, editable: isEditable( b.key ), canOpen: bound.canEdit };
		}
		if ( type === 'label' ) {
			return { name: bound.names.label[ b.key ] || b.key, place: L.labelPlace, url: bound.urls.label + '#sgc-label-' + b.key, editable: false, canOpen: bound.canEdit };
		}
		var f = fields[ b.key ];
		var here = ! ctx.postId || ctx.postId === ctx.currentId;
		var id = ctx.postId || ctx.currentId;
		return { name: f ? f[ 0 ] : b.key, place: here ? L.metaHere : L.metaPlace, url: bound.urls.post + id, editable: false, canOpen: true };
	}

	function BoundNotice( p ) {
		var info = useSelect( function ( select ) {
			var ed = select( 'core/editor' );
			return { lang: currentLang( select ), currentId: ed && ed.getCurrentPostId ? ed.getCurrentPostId() : 0 };
		}, [] );
		var ctx = { postId: ( p.context || {} ).postId, currentId: info.currentId };
		var items = p.items.map( function ( b ) {
			return describe( b, ctx );
		} );
		var editable = items.filter( function ( d ) {
			return d.editable;
		} );
		var hotelLocked = ! bound.canEdit && p.items.some( function ( b ) {
			return b.source === 'sunny-grove/hotel' && bound.editable.indexOf( b.key ) !== -1;
		} );
		var panel = el( be.InspectorControls, {},
			el( c.PanelBody, { title: L.panel, initialOpen: true, className: 'sgc-bound-panel' },
				items.map( function ( d, i ) {
					return el( 'p', { key: 'n' + i, className: 'sgc-bound-panel__from' }, fill( L.from, d.name, d.place ) );
				} ),
				editable.length ? el( c.Notice, { status: 'info', isDismissible: false }, fill( L.editable, editable[ 0 ].name, L[ info.lang ] || info.lang ) ) : null,
				hotelLocked ? el( c.Notice, { status: 'warning', isDismissible: false }, L.noRight ) : null,
				items.filter( function ( d ) {
					return d.canOpen;
				} ).map( function ( d, i ) {
					return el( c.Button, { key: 'b' + i, variant: 'secondary', href: d.url, target: '_blank', className: 'sgc-bound-panel__open' }, L.open + ': ' + d.name );
				} )
			)
		);
		var toolbar = el( be.BlockControls, { group: 'other' },
			el( c.ToolbarButton, Object.assign( {
				icon: 'database',
				label: fill( L.from, items[ 0 ].name, items[ 0 ].place ),
				showTooltip: true,
				className: 'sgc-bound-toolbar'
			}, items[ 0 ].canOpen ? { href: items[ 0 ].url, target: '_blank' } : {} ) )
		);
		return el( wp.element.Fragment, {}, toolbar, panel );
	}

	var withBoundNotice = wp.compose.createHigherOrderComponent( function ( BlockEdit ) {
		return function ( props ) {
			var items = sgBindings( props.attributes );
			if ( ! items.length || ! props.isSelected ) {
				return el( BlockEdit, props );
			}
			return el( wp.element.Fragment, {}, el( BlockEdit, props ), el( BoundNotice, { items: items, context: props.context } ) );
		};
	}, 'withSunnyGroveBoundNotice' );
	wp.hooks.addFilter( 'editor.BlockEdit', 'sunny-grove/bound-notice', withBoundNotice );

	var withBoundOutline = wp.compose.createHigherOrderComponent( function ( BlockListBlock ) {
		return function ( props ) {
			var items = sgBindings( props.attributes );
			if ( ! items.length ) {
				return el( BlockListBlock, props );
			}
			var canType = items.some( function ( b ) {
				return b.source === 'sunny-grove/hotel' && isEditable( b.key );
			} );
			var cls = 'sg-bound ' + ( canType ? 'sg-bound--editable' : 'sg-bound--locked' );
			return el( BlockListBlock, Object.assign( {}, props, { className: ( props.className ? props.className + ' ' : '' ) + cls } ) );
		};
	}, 'withSunnyGroveBoundOutline' );
	wp.hooks.addFilter( 'editor.BlockListBlock', 'sunny-grove/bound-outline', withBoundOutline );
}() );
