/* global wp, sgcListIcons */
/*
 * Sunny Grove – chọn icon cho từng mục danh sách (gạch đầu dòng).
 *
 * - Nút icon trên thanh công cụ của mục danh sách → bảng lưới icon, có ô tìm bằng tiếng Việt (có/không dấu) và tiếng Anh.
 * - Icon lưu trong thuộc tính sgIcon, dùng chung cho cả bản VI và EN. Ở chế độ EN nút bị khóa.
 * - Chưa chọn → icon lá (mặc định). Danh sách đánh số không dùng icon.
 */
( function () {
	'use strict';

	var cfg = window.sgcListIcons;
	if ( ! cfg || ! wp.hooks || ! wp.blockEditor ) {
		return;
	}
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useState = wp.element.useState;
	var useSelect = wp.data.useSelect;
	var be = wp.blockEditor;
	var c = wp.components;
	var L = cfg.labels;
	var ITEM = 'core/list-item';

	var byName = {};
	cfg.icons.forEach( function ( icon ) {
		icon.search = fold( icon.vi + ' ' + icon.en + ' ' + icon.k + ' ' + icon.name );
		byName[ icon.name ] = icon;
	} );

	// Bỏ dấu tiếng Việt để gõ "boi" hay "bơi" đều ra hồ bơi.
	function fold( text ) {
		return String( text || '' ).toLowerCase().normalize( 'NFD' ).replace( /[̀-ͯ]/g, '' ).replace( /đ/g, 'd' );
	}
	function current( name ) {
		return byName[ name ] ? name : cfg.def;
	}
	function label( icon ) {
		return cfg.lang === 'vi' ? icon.vi : icon.en;
	}
	function Svg( props ) {
		var icon = byName[ current( props.name ) ];
		return el( 'svg', {
			width: props.size || 24,
			height: props.size || 24,
			viewBox: '0 0 24 24',
			fill: 'none',
			stroke: 'currentColor',
			strokeWidth: 1.8,
			strokeLinecap: 'round',
			strokeLinejoin: 'round',
			'aria-hidden': true,
			focusable: false,
			style: { fill: 'none' }, // Nút của WordPress tô đặc svg – giữ dạng nét.
			dangerouslySetInnerHTML: { __html: icon.d }
		} );
	}
	var maskCache = {};
	function maskUrl( name ) {
		if ( ! maskCache[ name ] ) {
			var svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' + byName[ name ].d + '</svg>';
			maskCache[ name ] = 'url("data:image/svg+xml,' + encodeURIComponent( svg ) + '")';
		}
		return maskCache[ name ];
	}

	/* ---------- Thuộc tính sgIcon ---------- */
	wp.hooks.addFilter( 'blocks.registerBlockType', 'sunny-grove/list-icon-attr', function ( settings, name ) {
		if ( name === ITEM ) {
			settings.attributes = Object.assign( {}, settings.attributes, { sgIcon: { type: 'string' } } );
		}
		return settings;
	} );

	/* ---------- Bảng chọn icon ---------- */
	function Picker( props ) {
		var state = useState( '' );
		var query = state[ 0 ];
		var words = fold( query ).split( /\s+/ ).filter( Boolean );
		var list = cfg.icons.filter( function ( icon ) {
			return words.every( function ( w ) {
				return icon.search.indexOf( w ) !== -1;
			} );
		} );
		var value = current( props.value );
		return el( 'div', { className: 'sgc-icon-picker' },
			el( 'p', { className: 'sgc-icon-picker__title' }, L.title ),
			el( c.SearchControl, {
				value: query,
				onChange: state[ 1 ],
				label: L.search,
				placeholder: L.search,
				__nextHasNoMarginBottom: true
			} ),
			list.length ? el( 'div', { className: 'sgc-icon-picker__grid', role: 'listbox', 'aria-label': L.title },
				list.map( function ( icon ) {
					return el( c.Button, {
						key: icon.name,
						className: 'sgc-icon-picker__item',
						isPressed: icon.name === value,
						role: 'option',
						'aria-selected': icon.name === value,
						label: label( icon ),
						showTooltip: true,
						onClick: function () {
							props.onChange( icon.name );
						}
					}, el( Svg, { name: icon.name } ) );
				} )
			) : el( 'p', { className: 'sgc-icon-picker__none' }, L.none ),
			el( 'div', { className: 'sgc-icon-picker__footer' },
				el( 'span', {}, L.current + ' ', el( 'strong', {}, label( byName[ value ] ) ) ),
				props.value ? el( c.Button, { variant: 'link', onClick: function () {
					props.onChange( undefined );
				} }, L.reset ) : null
			)
		);
	}

	/* ---------- Nút trên thanh công cụ của mục danh sách ---------- */
	var withIconButton = wp.compose.createHigherOrderComponent( function ( BlockEdit ) {
		return function ( props ) {
			if ( props.name !== ITEM ) {
				return el( BlockEdit, props );
			}
			return el( Fragment, {}, el( BlockEdit, props ), el( IconControls, { props: props } ) );
		};
	}, 'withSunnyGroveListIcon' );

	function IconControls( p ) {
		var props = p.props;
		var info = useSelect( function ( select ) {
			var store = select( 'core/block-editor' );
			var parent = store.getBlockRootClientId( props.clientId );
			var attrs = parent ? store.getBlockAttributes( parent ) : null;
			var mode = window.sgcEditorStore && select( window.sgcEditorStore ) ? select( window.sgcEditorStore ).getMode() : '';
			var lang = window.sgI18nEditor ? window.sgI18nEditor.lang : 'en';
			return { ordered: !! ( attrs && attrs.ordered ), locked: mode === lang };
		}, [ props.clientId ] );
		if ( info.ordered ) {
			return null;
		}
		var value = props.attributes.sgIcon;
		return el( be.BlockControls, { group: 'block' },
			el( c.ToolbarGroup, {},
				el( c.Dropdown, {
					popoverProps: { placement: 'bottom-start' },
					renderToggle: function ( t ) {
						return el( c.ToolbarButton, {
							icon: el( Svg, { name: value } ),
							label: info.locked ? L.locked : L.button,
							showTooltip: true,
							disabled: info.locked,
							'aria-expanded': t.isOpen,
							'aria-haspopup': 'true',
							onClick: t.onToggle
						} );
					},
					renderContent: function ( t ) {
						return el( Picker, {
							value: value,
							onChange: function ( name ) {
								props.setAttributes( { sgIcon: name } );
								t.onClose();
							}
						} );
					}
				} )
			)
		);
	}
	wp.hooks.addFilter( 'editor.BlockEdit', 'sunny-grove/list-icon-button', withIconButton, 20 );

	/* ---------- Hiện đúng icon của mục ngay trong khung soạn thảo ---------- */
	var withIconPreview = wp.compose.createHigherOrderComponent( function ( BlockListBlock ) {
		return function ( props ) {
			if ( props.name !== ITEM ) {
				return el( BlockListBlock, props );
			}
			var wrapperProps = Object.assign( {}, props.wrapperProps );
			wrapperProps.style = Object.assign( {}, wrapperProps.style, { '--sg-li-icon': maskUrl( current( props.attributes.sgIcon ) ) } );
			return el( BlockListBlock, Object.assign( {}, props, { wrapperProps: wrapperProps } ) );
		};
	}, 'withSunnyGroveListIconPreview' );
	wp.hooks.addFilter( 'editor.BlockListBlock', 'sunny-grove/list-icon-preview', withIconPreview );
}() );
