/* global wp, sgI18nEditor */
/*
 * Sunny Grove – dịch ngay trong trình soạn thảo ("1 bài – 2 ngôn ngữ").
 *
 * - Nút gạt VI | EN trên thanh công cụ (và trong thanh bên "Tiếng Anh").
 * - Chế độ EN: mỗi block chữ hiển thị và cho sửa bản tiếng Anh tại chỗ (lưu trong thuộc tính sgEn của block,
 *   kèm mã băm chữ VI lúc dịch để biết khi nào bản EN đã cũ). Bố cục và ảnh dùng chung nên bị khóa.
 * - Tiêu đề bài, tóm tắt, slug tiếng Anh lưu trong meta _sg_en_*.
 * - Query Loop ở chế độ EN hiển thị tiêu đề/tóm tắt/trường chữ tiếng Anh của từng bài.
 */
( function () {
	'use strict';

	var cfg = window.sgI18nEditor;
	if ( ! cfg || ! wp.data || ! wp.hooks ) {
		return;
	}
	var el = wp.element.createElement;
	var useSelect = wp.data.useSelect;
	var LANG = cfg.lang;
	var DEF = cfg.defaultLang;
	var ATTR = 'sg' + LANG.charAt( 0 ).toUpperCase() + LANG.slice( 1 );
	var L = cfg.labels;
	var STORE = 'sunny-grove/i18n';

	/* ---------- Trạng thái chế độ (VI | EN) ---------- */
	var savedMode = DEF;
	try {
		savedMode = window.sessionStorage.getItem( 'sg-i18n-mode' ) === LANG ? LANG : DEF;
	} catch ( e ) {}
	wp.data.register( wp.data.createReduxStore( STORE, {
		reducer: function ( state, action ) {
			state = state || { mode: savedMode };
			return action.type === 'SG_SET_MODE' ? { mode: action.mode } : state;
		},
		actions: {
			setMode: function ( mode ) {
				return { type: 'SG_SET_MODE', mode: mode };
			}
		},
		selectors: {
			getMode: function ( state ) {
				return state.mode;
			}
		}
	} ) );
	function getMode() {
		return wp.data.select( STORE ).getMode();
	}
	// Cho bindings-editor.js / blocks-editor.js biết ngôn ngữ đang xem.
	window.sgcEditorCurrentLang = getMode;
	window.sgcEditorStore = STORE;

	/* ---------- Băm & chuẩn hóa (cùng thuật toán với sg_i18n_hash / sg_i18n_normalize trong PHP) ---------- */
	var scratch = document.createElement( 'div' );
	function normalize( html ) {
		scratch.innerHTML = String( html === undefined || html === null ? '' : html );
		return ( scratch.textContent || '' ).replace( /[\s ]+/g, ' ' ).trim();
	}
	function hash( values ) {
		var parts = [];
		values.forEach( function ( v ) {
			( Array.isArray( v ) ? v : [ v ] ).forEach( function ( p ) {
				parts.push( normalize( p ) );
			} );
		} );
		var bytes = new TextEncoder().encode( parts.join( '\x1f' ) );
		var h = 0x811c9dc5;
		for ( var i = 0; i < bytes.length; i++ ) {
			h = ( h ^ bytes[ i ] ) >>> 0;
			h = Math.imul( h, 0x01000193 ) >>> 0;
		}
		return ( '0000000' + h.toString( 16 ) ).slice( -8 );
	}
	function str( v ) {
		if ( v === undefined || v === null ) {
			return '';
		}
		if ( typeof v === 'object' && typeof v.toHTMLString === 'function' ) {
			return v.toHTMLString();
		}
		return String( v );
	}

	/* ---------- Block dịch được: trường bản dịch → thuộc tính block (khớp sg_i18n_block_spec trong PHP) ---------- */
	var SPEC = cfg.spec;

	function cellsOf( attrs ) {
		var out = [];
		[ 'head', 'body', 'foot' ].forEach( function ( part ) {
			( attrs[ part ] || [] ).forEach( function ( row ) {
				( row.cells || [] ).forEach( function ( cell ) {
					out.push( cell );
				} );
			} );
		} );
		return out;
	}
	function withCells( attrs, values ) {
		var i = 0;
		var next = Object.assign( {}, attrs );
		[ 'head', 'body', 'foot' ].forEach( function ( part ) {
			next[ part ] = ( attrs[ part ] || [] ).map( function ( row ) {
				return Object.assign( {}, row, {
					cells: ( row.cells || [] ).map( function ( cell ) {
						var v = values[ i++ ];
						return v ? Object.assign( {}, cell, { content: v } ) : cell;
					} )
				} );
			} );
		} );
		return next;
	}
	function boundOf( attrs ) {
		return ( attrs.metadata && attrs.metadata.bindings ) || {};
	}
	function viValues( name, attrs ) {
		var s = SPEC[ name ];
		var bound = boundOf( attrs );
		var out = {};
		Object.keys( s.fields ).forEach( function ( f ) {
			var a = s.fields[ f ];
			if ( ! bound[ a ] ) {
				out[ f ] = str( attrs[ a ] );
			}
		} );
		if ( s.cells ) {
			out.cells = cellsOf( attrs ).map( function ( c ) {
				return str( c.content );
			} );
		}
		return out;
	}
	function textFields( name ) {
		var s = SPEC[ name ];
		var t = s.text ? s.text.slice() : Object.keys( s.fields );
		if ( s.cells ) {
			t.push( 'cells' );
		}
		return t;
	}
	function hasText( values, fields ) {
		return fields.some( function ( f ) {
			return f in values && normalize( [].concat( values[ f ] ).join( ' ' ) ) !== '';
		} );
	}
	function viHash( name, attrs ) {
		var v = viValues( name, attrs );
		return hash( textFields( name ).filter( function ( f ) {
			return f in v;
		} ).map( function ( f ) {
			return v[ f ];
		} ) );
	}
	// none | missing | outdated | done
	function blockStatus( name, attrs ) {
		if ( ! SPEC[ name ] ) {
			return 'none';
		}
		var fields = textFields( name );
		if ( ! hasText( viValues( name, attrs ), fields ) ) {
			return 'none';
		}
		var tr = attrs[ ATTR ];
		if ( ! tr || ! hasText( tr, fields ) ) {
			return 'missing';
		}
		return tr.h === viHash( name, attrs ) ? 'done' : 'outdated';
	}

	/* ---------- Thuộc tính sgEn cho block ---------- */
	wp.hooks.addFilter( 'blocks.registerBlockType', 'sunny-grove/i18n-attr', function ( settings, name ) {
		if ( SPEC[ name ] ) {
			settings.attributes = Object.assign( {}, settings.attributes );
			settings.attributes[ ATTR ] = { type: 'object' };
		}
		return settings;
	} );

	/* ---------- Sửa bản EN tại chỗ ---------- */
	var withEnglish = wp.compose.createHigherOrderComponent( function ( BlockEdit ) {
		return function ( props ) {
			var mode = useSelect( function ( select ) {
				return select( STORE ).getMode();
			}, [] );
			if ( mode !== LANG || ! SPEC[ props.name ] ) {
				return el( BlockEdit, props );
			}
			var s = SPEC[ props.name ];
			var attrs = props.attributes;
			var tr = attrs[ ATTR ] || {};
			var bound = boundOf( attrs );
			var proxy = Object.assign( {}, attrs );
			Object.keys( s.fields ).forEach( function ( f ) {
				var a = s.fields[ f ];
				if ( ! bound[ a ] && tr[ f ] !== undefined && tr[ f ] !== '' ) {
					proxy[ a ] = tr[ f ];
				}
			} );
			if ( s.cells && Array.isArray( tr.cells ) ) {
				proxy = withCells( proxy, tr.cells );
			}
			function setAttributes( changes ) {
				// Chữ nối dữ liệu (VD: Slogan): chuyển thẳng cho Block Bindings – nguồn tự lưu vào ô EN của cài đặt.
				var boundChanges = {};
				Object.keys( changes ).forEach( function ( a ) {
					if ( bound[ a ] ) {
						boundChanges[ a ] = changes[ a ];
					}
				} );
				if ( Object.keys( boundChanges ).length ) {
					props.setAttributes( boundChanges );
				}
				var next = Object.assign( {}, attrs[ ATTR ] || {} );
				var touched = false;
				Object.keys( s.fields ).forEach( function ( f ) {
					var a = s.fields[ f ];
					if ( a in changes && ! bound[ a ] ) {
						next[ f ] = str( changes[ a ] );
						touched = true;
					}
				} );
				if ( s.cells && ( changes.head || changes.body || changes.foot ) ) {
					next.cells = cellsOf( Object.assign( {}, proxy, changes ) ).map( function ( c ) {
						return str( c.content );
					} );
					touched = true;
				}
				// Ở chế độ EN chỉ nhận thay đổi chữ; bố cục, màu, ảnh… dùng chung, sửa ở chế độ VI.
				if ( ! touched ) {
					return;
				}
				next.h = viHash( props.name, attrs );
				var out = {};
				out[ ATTR ] = next;
				props.setAttributes( out );
			}
			return el( BlockEdit, Object.assign( {}, props, { attributes: proxy, setAttributes: setAttributes } ) );
		};
	}, 'withSunnyGroveEnglish' );
	wp.hooks.addFilter( 'editor.BlockEdit', 'sunny-grove/i18n-edit', withEnglish );

	/* ---------- Nhãn "Chưa dịch" / "Cần cập nhật bản EN" ---------- */
	var withBadge = wp.compose.createHigherOrderComponent( function ( BlockListBlock ) {
		return function ( props ) {
			var mode = useSelect( function ( select ) {
				return select( STORE ).getMode();
			}, [] );
			if ( mode !== LANG || ! SPEC[ props.name ] ) {
				return el( BlockListBlock, props );
			}
			var st = blockStatus( props.name, props.attributes );
			if ( st !== 'missing' && st !== 'outdated' ) {
				return el( BlockListBlock, props );
			}
			var wrapperProps = Object.assign( {}, props.wrapperProps, { 'data-sg-tr': st === 'missing' ? L.missing : L.outdated } );
			return el( BlockListBlock, Object.assign( {}, props, {
				wrapperProps: wrapperProps,
				className: ( props.className ? props.className + ' ' : '' ) + 'sg-tr-' + st
			} ) );
		};
	}, 'withSunnyGroveBadge' );
	wp.hooks.addFilter( 'editor.BlockListBlock', 'sunny-grove/i18n-badge', withBadge );

	/* ---------- Query Loop ở chế độ EN: tiêu đề / tóm tắt tiếng Anh của từng bài ---------- */
	function currentPostId() {
		var ed = wp.data.select( 'core/editor' );
		return ed && ed.getCurrentPostId ? ed.getCurrentPostId() : 0;
	}
	function EnglishLoopField( p ) {
		var ctx = p.props.context || {};
		var record = useSelect( function ( select ) {
			return select( 'core' ).getEntityRecord( 'postType', ctx.postType, ctx.postId );
		}, [ ctx.postType, ctx.postId ] );
		var shown = ( record && record.sg_display && record.sg_display[ LANG ] ) || {};
		var attrs = p.props.attributes;
		var blockProps = wp.blockEditor.useBlockProps();
		if ( p.props.name === 'core/post-title' ) {
			var tag = attrs.level === 0 ? 'p' : 'h' + ( attrs.level || 2 );
			var text = shown.title || ( record && record.title && record.title.rendered ) || '';
			return el( tag, blockProps, attrs.isLink ? el( 'a', { href: '#', onClick: function ( e ) {
				e.preventDefault();
			} }, text ) : text );
		}
		var words = ( shown.excerpt || '' ).split( /\s+/ );
		var limit = attrs.excerptLength || 55;
		var excerpt = words.slice( 0, limit ).join( ' ' ) + ( words.length > limit ? '…' : '' );
		return el( 'div', blockProps, el( 'p', { className: 'wp-block-post-excerpt__excerpt' }, excerpt ) );
	}
	var withLoopEnglish = wp.compose.createHigherOrderComponent( function ( BlockEdit ) {
		return function ( props ) {
			var mode = useSelect( function ( select ) {
				return select( STORE ).getMode();
			}, [] );
			var ctx = props.context || {};
			var inLoop = ( props.name === 'core/post-title' || props.name === 'core/post-excerpt' ) &&
				ctx.postId && ctx.postType && ( ctx.queryId !== undefined || ctx.postId !== currentPostId() );
			if ( mode !== LANG || ! inLoop ) {
				return el( BlockEdit, props );
			}
			return el( EnglishLoopField, { props: props } );
		};
	}, 'withSunnyGroveLoopEnglish' );
	wp.hooks.addFilter( 'editor.BlockEdit', 'sunny-grove/i18n-loop', withLoopEnglish );

	/* ---------- Áp dụng chế độ: khóa bố cục, thông báo, class cho giao diện ---------- */
	var lockBefore = null;
	function canvasDoc() {
		var frame = document.querySelector( 'iframe[name="editor-canvas"]' );
		return frame && frame.contentDocument ? frame.contentDocument : null;
	}
	function applyMode( mode ) {
		var en = mode === LANG;
		try {
			window.sessionStorage.setItem( 'sg-i18n-mode', mode );
		} catch ( e ) {}
		var be = wp.data.dispatch( 'core/block-editor' );
		var settings = wp.data.select( 'core/block-editor' ).getSettings();
		if ( en ) {
			if ( lockBefore === null ) {
				lockBefore = settings.templateLock === undefined ? false : settings.templateLock;
			}
			be.updateSettings( { templateLock: 'all' } );
			wp.data.dispatch( 'core/notices' ).createNotice( 'info', L.notice, { id: 'sg-i18n-mode', isDismissible: false } );
		} else {
			if ( lockBefore !== null ) {
				be.updateSettings( { templateLock: lockBefore } );
				lockBefore = null;
			}
			wp.data.dispatch( 'core/notices' ).removeNotice( 'sg-i18n-mode' );
		}
		if ( document.body ) {
			document.body.classList.toggle( 'sg-mode-en', en );
		}
		var doc = canvasDoc();
		if ( doc && doc.body ) {
			doc.body.classList.toggle( 'sg-mode-en', en );
		}
		syncTitle();
	}
	var lastMode = null;
	wp.data.subscribe( function () {
		var mode = getMode();
		if ( mode !== lastMode && wp.data.select( 'core/block-editor' ) ) {
			lastMode = mode;
			applyMode( mode );
		}
	} );

	/* ---------- Tiêu đề bài ở chế độ EN ---------- */
	function editorMeta() {
		var ed = wp.data.select( 'core/editor' );
		return ( ed && ed.getEditedPostAttribute && ed.getEditedPostAttribute( 'meta' ) ) || {};
	}
	function editMeta( changes ) {
		wp.data.dispatch( 'core/editor' ).editPost( { meta: changes } );
	}
	function setEnTitle( value ) {
		var ed = wp.data.select( 'core/editor' );
		var changes = {};
		changes[ cfg.keys.title ] = value;
		changes[ cfg.keys.titleHash ] = hash( [ ed.getEditedPostAttribute( 'title' ) || '' ] );
		editMeta( changes );
	}
	function syncTitle() {
		var doc = canvasDoc() || document;
		var ed = wp.data.select( 'core/editor' );
		if ( ! ed || ! ed.getCurrentPostType || ! doc ) {
			return;
		}
		var original = doc.querySelector( '.editor-post-title:not(.sg-en-title)' );
		var mine = doc.querySelector( '.sg-en-title' );
		if ( getMode() !== LANG || ! original ) {
			if ( mine ) {
				mine.remove();
			}
			return;
		}
		var value = editorMeta()[ cfg.keys.title ] || '';
		if ( ! mine ) {
			mine = doc.createElement( original.tagName );
			mine.className = original.className.replace( /\bis-selected\b/, '' ) + ' sg-en-title';
			mine.setAttribute( 'contenteditable', 'true' );
			mine.setAttribute( 'role', 'textbox' );
			mine.setAttribute( 'aria-label', L.titleLabel );
			mine.setAttribute( 'lang', cfg.locale );
			mine.addEventListener( 'input', function () {
				mine.classList.remove( 'is-untranslated' );
				setEnTitle( mine.textContent.trim() );
			} );
			mine.addEventListener( 'keydown', function ( e ) {
				if ( e.key === 'Enter' ) {
					e.preventDefault();
				}
			} );
			mine.addEventListener( 'focus', function () {
				if ( mine.classList.contains( 'is-untranslated' ) ) {
					mine.textContent = '';
					mine.classList.remove( 'is-untranslated' );
				}
			} );
			original.parentNode.insertBefore( mine, original );
		}
		if ( doc.activeElement !== mine ) {
			if ( value ) {
				mine.textContent = value;
				mine.classList.remove( 'is-untranslated' );
			} else {
				mine.textContent = ed.getEditedPostAttribute( 'title' ) || '';
				mine.classList.add( 'is-untranslated' );
			}
		}
		var titleHash = editorMeta()[ cfg.keys.titleHash ];
		var outdated = value && titleHash && titleHash !== hash( [ ed.getEditedPostAttribute( 'title' ) || '' ] );
		mine.setAttribute( 'data-sg-tr', ! value ? L.missing : ( outdated ? L.outdated : '' ) );
	}
	// Khung soạn thảo có thể được dựng lại → kiểm tra định kỳ cho chắc.
	window.setInterval( function () {
		if ( getMode() === LANG ) {
			var doc = canvasDoc();
			if ( doc && doc.body && ! doc.body.classList.contains( 'sg-mode-en' ) ) {
				doc.body.classList.add( 'sg-mode-en' );
			}
			syncTitle();
		}
	}, 800 );

	/* ---------- Nút gạt VI | EN ---------- */
	function ModeSwitch() {
		var mode = useSelect( function ( select ) {
			return select( STORE ).getMode();
		}, [] );
		var set = wp.data.dispatch( STORE ).setMode;
		return el( 'div', { className: 'sg-mode-switch', role: 'group', 'aria-label': L.switchLabel },
			[ DEF, LANG ].map( function ( code ) {
				return el( wp.components.Button, {
					key: code,
					className: 'sg-mode-switch__btn',
					isPressed: mode === code,
					'aria-pressed': mode === code,
					onClick: function () {
						set( code );
					}
				}, code.toUpperCase() );
			} )
		);
	}
	function mountToolbarSwitch() {
		if ( document.querySelector( '.sg-mode-switch-host' ) ) {
			return;
		}
		var bar = document.querySelector( '.editor-header__toolbar, .edit-post-header-toolbar, .edit-site-header-edit-mode__start' );
		if ( ! bar ) {
			return;
		}
		var host = document.createElement( 'div' );
		host.className = 'sg-mode-switch-host';
		bar.appendChild( host );
		if ( wp.element.createRoot ) {
			wp.element.createRoot( host ).render( el( ModeSwitch ) );
		} else {
			wp.element.render( el( ModeSwitch ), host );
		}
	}
	wp.domReady( function () {
		mountToolbarSwitch();
		new window.MutationObserver( mountToolbarSwitch ).observe( document.body, { childList: true, subtree: true } );
		applyMode( getMode() );
	} );

	/* ---------- Thanh bên "Tiếng Anh" ---------- */
	function blockLabel( block ) {
		var type = wp.blocks.getBlockType( block.name );
		var text = normalize( [].concat.apply( [], Object.values( viValues( block.name, block.attributes ) ) ).join( ' ' ) );
		return ( type ? type.title : block.name ) + ': ' + ( text.length > 42 ? text.slice( 0, 42 ) + '…' : text );
	}
	function I18nPanel() {
		var mode = useSelect( function ( select ) {
			return select( STORE ).getMode();
		}, [] );
		var data = useSelect( function ( select ) {
			var be = select( 'core/block-editor' );
			var list = { done: [], missing: [], outdated: [] };
			be.getClientIdsWithDescendants().forEach( function ( id ) {
				var block = be.getBlock( id );
				if ( ! block ) {
					return;
				}
				var st = blockStatus( block.name, block.attributes );
				if ( list[ st ] ) {
					list[ st ].push( { id: id, label: blockLabel( block ) } );
				}
			} );
			var ed = select( 'core/editor' );
			var meta = ed.getEditedPostAttribute( 'meta' ) || {};
			[ [ 'title', 'titleHash', L.title ], [ 'excerpt', 'excerptHash', L.excerpt ] ].forEach( function ( f ) {
				var vi = ed.getEditedPostAttribute( f[ 0 ] ) || '';
				if ( ! normalize( vi ) ) {
					return;
				}
				var tr = meta[ cfg.keys[ f[ 0 ] ] ] || '';
				var h = meta[ cfg.keys[ f[ 1 ] ] ] || '';
				var st = ! tr ? 'missing' : ( h && h !== hash( [ vi ] ) ? 'outdated' : 'done' );
				list[ st ].push( { id: '', label: f[ 2 ] } );
			} );
			return { list: list, meta: meta };
		}, [] );
		var c = wp.components;
		var select = function ( id ) {
			if ( ! id ) {
				return;
			}
			wp.data.dispatch( STORE ).setMode( LANG );
			wp.data.dispatch( 'core/block-editor' ).selectBlock( id );
		};
		function group( key, title ) {
			var items = data.list[ key ];
			if ( ! items.length || key === 'done' ) {
				return null;
			}
			return el( 'div', { className: 'sg-i18n-group sg-i18n-group--' + key },
				el( 'p', { className: 'sg-i18n-group__title' }, title + ' (' + items.length + ')' ),
				el( 'ul', {}, items.map( function ( item, i ) {
					return el( 'li', { key: i }, item.id ? el( c.Button, { variant: 'link', onClick: function () {
						select( item.id );
					} }, item.label ) : item.label );
				} ) )
			);
		}
		var meta = data.meta;
		var ed = wp.data.select( 'core/editor' );
		var setField = function ( key, hashKey, viAttr ) {
			return function ( v ) {
				var ch = {};
				ch[ key ] = v;
				if ( hashKey ) {
					ch[ hashKey ] = hash( [ ed.getEditedPostAttribute( viAttr ) || '' ] );
				}
				editMeta( ch );
			};
		};
		var Panel = ( wp.editor && wp.editor.PluginDocumentSettingPanel ) || ( wp.editPost && wp.editPost.PluginDocumentSettingPanel );
		return el( Panel, { name: 'sg-i18n', title: L.panelTitle, className: 'sg-i18n-panel' },
			el( ModeSwitch ),
			el( 'p', { className: 'sg-i18n-help' }, mode === LANG ? L.notice : L.help ),
			el( 'p', { className: 'sg-i18n-stats' },
				el( 'span', { className: 'is-done' }, '✓ ' + L.statDone + ': ' + data.list.done.length ), ' · ',
				el( 'span', { className: 'is-missing' }, '✗ ' + L.statMissing + ': ' + data.list.missing.length ), ' · ',
				el( 'span', { className: 'is-outdated' }, '⚠ ' + L.statOutdated + ': ' + data.list.outdated.length )
			),
			group( 'outdated', L.outdated ),
			group( 'missing', L.missing ),
			el( c.TextControl, { label: L.title, value: meta[ cfg.keys.title ] || '', onChange: setField( cfg.keys.title, cfg.keys.titleHash, 'title' ), placeholder: ed.getEditedPostAttribute( 'title' ) || '' } ),
			cfg.supportsExcerpt ? el( c.TextareaControl, { label: L.excerpt, value: meta[ cfg.keys.excerpt ] || '', onChange: setField( cfg.keys.excerpt, cfg.keys.excerptHash, 'excerpt' ), rows: 3 } ) : null,
			el( c.TextControl, { label: L.slug, help: L.slugHelp, value: meta[ cfg.keys.slug ] || '', onChange: setField( cfg.keys.slug ), placeholder: ed.getEditedPostAttribute( 'slug' ) || '' } )
		);
	}
	if ( wp.plugins && ( ( wp.editor && wp.editor.PluginDocumentSettingPanel ) || ( wp.editPost && wp.editPost.PluginDocumentSettingPanel ) ) && cfg.postEditor ) {
		wp.plugins.registerPlugin( 'sunny-grove-i18n', { render: I18nPanel } );
	}

	/* ---------- Đánh dấu request của trình soạn thảo (ẩn ưu đãi hết hạn giống ngoài website) ---------- */
	if ( wp.apiFetch && wp.url && cfg.bases.length ) {
		var collection = new RegExp( '^/?wp/v2/(' + cfg.bases.join( '|' ) + ')/?(\\?|$)' );
		wp.apiFetch.use( function ( options, next ) {
			var path = options.path || '';
			if ( path && ( options.method || 'GET' ).toUpperCase() === 'GET' && collection.test( path ) && path.indexOf( 'sg_editor=' ) === -1 ) {
				options = Object.assign( {}, options, { path: wp.url.addQueryArgs( path, { sg_editor: 1 } ) } );
			}
			return next( options );
		} );
	}
}() );
