/* Sunny Grove – hành vi phía khách cho các block (không cần thư viện). */
( function () {
	'use strict';

	var strings = window.sgcView || {};
	var PREF_KEY = 'sg-motion-off';
	var reduce = window.matchMedia ? window.matchMedia( '(prefers-reduced-motion: reduce)' ) : { matches: false };

	function motionOffByUser() {
		try {
			return window.localStorage.getItem( PREF_KEY ) === '1';
		} catch ( e ) {
			return false;
		}
	}
	function setMotionOff( off ) {
		try {
			if ( off ) {
				window.localStorage.setItem( PREF_KEY, '1' );
			} else {
				window.localStorage.removeItem( PREF_KEY );
			}
		} catch ( e ) {}
	}

	// Chỉ chạy video khi: không bật giảm chuyển động, không tiết kiệm dữ liệu, mạng không quá chậm, người dùng chưa tắt.
	function canPlayVideo() {
		if ( reduce.matches || motionOffByUser() ) {
			return false;
		}
		var conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
		if ( conn && ( conn.saveData || /(^|-)(2g|3g)$/.test( conn.effectiveType || '' ) ) ) {
			return false;
		}
		return true;
	}

	function afterLoad( fn ) {
		var run = function () {
			if ( 'requestIdleCallback' in window ) {
				window.requestIdleCallback( fn, { timeout: 2500 } );
			} else {
				setTimeout( fn, 300 );
			}
		};
		if ( document.readyState === 'complete' ) {
			run();
		} else {
			window.addEventListener( 'load', run, { once: true } );
		}
	}

	function startVideo( video, src, onPlaying ) {
		if ( ! video.getAttribute( 'src' ) ) {
			video.src = src;
		}
		video.muted = true;
		var p = video.play();
		video.addEventListener( 'playing', function () {
			video.classList.add( 'is-playing' );
			if ( onPlaying ) {
				onPlaying();
			}
		}, { once: true } );
		if ( p && p.catch ) {
			p.catch( function () {} );
		}
	}

	var observer = 'IntersectionObserver' in window ? new IntersectionObserver( function ( entries ) {
		entries.forEach( function ( entry ) {
			var v = entry.target;
			if ( entry.isIntersecting ) {
				if ( v.dataset.userPaused !== '1' && canPlayVideo() ) {
					startVideo( v, v.dataset.activeSrc || v.dataset.src, v._sgOnPlaying );
				}
			} else if ( ! v.paused ) {
				v.pause();
			}
		} );
	}, { rootMargin: '150px 0px' } ) : null;

	/* ---------- Banner video ---------- */
	function initBanner( banner ) {
		var video = banner.querySelector( '.sg-banner__video' );
		var toggle = banner.querySelector( '.sg-banner__toggle' );
		if ( ! video ) {
			return;
		}
		var src = ( window.innerWidth < 768 && video.dataset.srcMobile ) ? video.dataset.srcMobile : video.dataset.src;
		video.dataset.activeSrc = src;

		function updateToggle( playing ) {
			if ( ! toggle ) {
				return;
			}
			toggle.hidden = false;
			toggle.setAttribute( 'aria-pressed', playing ? 'false' : 'true' );
			toggle.classList.toggle( 'is-paused', ! playing );
			var sr = toggle.querySelector( '.screen-reader-text' );
			if ( sr ) {
				sr.textContent = playing ? ( strings.pause || '' ) : ( strings.play || '' );
			}
		}
		video._sgOnPlaying = function () {
			updateToggle( true );
		};

		if ( toggle ) {
			toggle.addEventListener( 'click', function () {
				if ( video.paused ) {
					video.dataset.userPaused = '0';
					setMotionOff( false );
					startVideo( video, src, video._sgOnPlaying );
					updateToggle( true );
				} else {
					video.pause();
					video.dataset.userPaused = '1';
					setMotionOff( true );
					updateToggle( false );
				}
			} );
		}

		if ( ! canPlayVideo() ) {
			// Vẫn cho phép người dùng chủ động bật video nếu họ đã tắt trước đó (không áp dụng khi hệ thống yêu cầu giảm chuyển động).
			if ( motionOffByUser() && ! reduce.matches ) {
				updateToggle( false );
			}
			return;
		}
		afterLoad( function () {
			if ( observer ) {
				observer.observe( video );
			} else {
				startVideo( video, src, video._sgOnPlaying );
			}
		} );
	}

	/* ---------- Ảnh động trên thẻ ---------- */
	function initCardVideo( video ) {
		if ( ! observer || ! canPlayVideo() ) {
			return;
		}
		afterLoad( function () {
			observer.observe( video );
		} );
	}

	/* ---------- Bộ lọc nhanh ---------- */
	function findTargetQuery( filter ) {
		var node = filter.nextElementSibling;
		while ( node ) {
			if ( node.classList.contains( 'wp-block-query' ) ) {
				return node;
			}
			var inner = node.querySelector && node.querySelector( '.wp-block-query' );
			if ( inner ) {
				return inner;
			}
			node = node.nextElementSibling;
		}
		var parent = filter.parentElement;
		return parent ? parent.querySelector( '.wp-block-query' ) : null;
	}

	function initFilter( filter ) {
		var tax = filter.dataset.taxonomy;
		var query = findTargetQuery( filter );
		if ( ! query ) {
			return;
		}
		var chips = filter.querySelectorAll( '.sg-filter__chip' );
		chips.forEach( function ( chip ) {
			chip.addEventListener( 'click', function () {
				var term = chip.dataset.term;
				chips.forEach( function ( c ) {
					var on = c === chip;
					c.classList.toggle( 'is-active', on );
					c.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
				} );
				var shown = 0;
				query.querySelectorAll( '.wp-block-post' ).forEach( function ( item ) {
					var match = ! term || item.classList.contains( tax + '-' + term );
					item.hidden = ! match;
					if ( match ) {
						shown++;
					}
				} );
				var status = filter.querySelector( '.sg-filter__status' );
				if ( status ) {
					status.textContent = chip.textContent + ': ' + shown;
				}
				query.dispatchEvent( new CustomEvent( 'sg:filtered', { bubbles: true } ) );
			} );
		} );
	}

	/* ---------- Menu trên điện thoại ---------- */
	function initMenu( nav ) {
		var btn = nav.querySelector( '.sg-menu__toggle' );
		if ( ! btn ) {
			return;
		}
		var text = btn.querySelector( '.sg-menu__toggle-text' );
		function setOpen( open ) {
			nav.classList.toggle( 'is-open', open );
			btn.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			document.documentElement.classList.toggle( 'sg-menu-open', open );
			if ( text ) {
				text.textContent = open ? ( strings.menuClose || text.textContent ) : ( strings.menuOpen || text.textContent );
			}
		}
		btn.addEventListener( 'click', function () {
			setOpen( btn.getAttribute( 'aria-expanded' ) !== 'true' );
		} );
		nav.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' && nav.classList.contains( 'is-open' ) ) {
				setOpen( false );
				btn.focus();
			}
		} );
		nav.querySelectorAll( 'a' ).forEach( function ( a ) {
			a.addEventListener( 'click', function () {
				setOpen( false );
			} );
		} );
		window.addEventListener( 'resize', function () {
			if ( window.innerWidth >= 960 && nav.classList.contains( 'is-open' ) ) {
				setOpen( false );
			}
		} );
	}

	function init() {
		document.querySelectorAll( '.sg-banner' ).forEach( initBanner );
		document.querySelectorAll( '.sg-media__video' ).forEach( initCardVideo );
		document.querySelectorAll( '.sg-filter' ).forEach( initFilter );
		document.querySelectorAll( '.sg-menu.has-toggle' ).forEach( initMenu );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
