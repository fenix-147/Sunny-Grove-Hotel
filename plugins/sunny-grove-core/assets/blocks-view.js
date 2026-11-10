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
		// Bộ lọc nằm trong hàng tiêu đề: tìm danh sách trong khối cha gần nhất (dừng ở ranh giới khối .sg-section).
		for ( var parent = filter.parentElement; parent && parent !== document.body; parent = parent.parentElement ) {
			var found = parent.querySelector( '.wp-block-query' );
			if ( found ) {
				return found;
			}
			if ( parent.classList.contains( 'sg-section' ) ) {
				break;
			}
		}
		return null;
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

	/* ---------- Thư viện ảnh: nút trượt + khung phóng to ---------- */
	function icon( d ) {
		return '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' + d + '"/></svg>';
	}
	var ICON_PREV = icon( 'M15 5l-7 7 7 7' );
	var ICON_NEXT = icon( 'M9 5l7 7-7 7' );
	var ICON_CLOSE = icon( 'M6 6l12 12M18 6L6 18' );

	/* Vùng cuộn ngang: kéo bằng chuột (vẫn bấm được ảnh/link khi không kéo), phím ←/→. Thanh cuộn ẩn bằng CSS. */
	function dragScroll( el, stepFn ) {
		var down = false;
		var moved = false;
		var startX = 0;
		var startLeft = 0;
		el.addEventListener( 'pointerdown', function ( e ) {
			if ( e.pointerType !== 'mouse' || e.button !== 0 || el.scrollWidth <= el.clientWidth ) {
				return;
			}
			down = true;
			moved = false;
			startX = e.clientX;
			startLeft = el.scrollLeft;
		} );
		window.addEventListener( 'pointermove', function ( e ) {
			if ( ! down ) {
				return;
			}
			var dx = e.clientX - startX;
			if ( ! moved && Math.abs( dx ) > 5 ) {
				moved = true;
				el.classList.add( 'is-dragging' );
			}
			if ( moved ) {
				el.scrollLeft = startLeft - dx;
			}
		} );
		window.addEventListener( 'pointerup', function () {
			if ( down ) {
				down = false;
				el.classList.remove( 'is-dragging' );
			}
		} );
		// Vừa kéo xong thì không tính là bấm (không mở ảnh / link).
		el.addEventListener( 'click', function ( e ) {
			if ( moved ) {
				e.preventDefault();
				e.stopPropagation();
				moved = false;
			}
		}, true );
		el.addEventListener( 'dragstart', function ( e ) {
			e.preventDefault();
		} );
		el.addEventListener( 'keydown', function ( e ) {
			if ( e.key !== 'ArrowRight' && e.key !== 'ArrowLeft' ) {
				return;
			}
			e.preventDefault();
			var s = stepFn ? stepFn() : el.clientWidth * 0.8;
			el.scrollBy( { left: e.key === 'ArrowRight' ? s : -s, behavior: reduce.matches ? 'auto' : 'smooth' } );
		} );
	}

	function initScroller( gallery ) {
		var list = gallery.querySelector( '.sg-gallery__list' );
		if ( ! list || list.children.length < 2 ) {
			return;
		}
		var prev = document.createElement( 'button' );
		var next = document.createElement( 'button' );
		prev.type = next.type = 'button';
		prev.className = 'sg-gallery__nav sg-gallery__nav--prev';
		next.className = 'sg-gallery__nav sg-gallery__nav--next';
		prev.setAttribute( 'aria-label', strings.galPrev || '' );
		next.setAttribute( 'aria-label', strings.galNext || '' );
		prev.innerHTML = ICON_PREV;
		next.innerHTML = ICON_NEXT;
		gallery.classList.add( 'has-nav' );
		gallery.appendChild( prev );
		gallery.appendChild( next );

		function step() {
			var item = list.children[ 0 ];
			var gap = parseFloat( getComputedStyle( list ).columnGap ) || 0;
			return item ? item.getBoundingClientRect().width + gap : list.clientWidth;
		}
		function update() {
			var max = list.scrollWidth - list.clientWidth - 2;
			prev.disabled = list.scrollLeft <= 2;
			next.disabled = list.scrollLeft >= max;
		}
		var behavior = reduce.matches ? 'auto' : 'smooth';
		prev.addEventListener( 'click', function () {
			list.scrollBy( { left: -step(), behavior: behavior } );
		} );
		next.addEventListener( 'click', function () {
			list.scrollBy( { left: step(), behavior: behavior } );
		} );
		// Chấm nhỏ báo vị trí ảnh (bấm để tới ảnh đó).
		var dots = document.createElement( 'div' );
		dots.className = 'sg-gallery__dots';
		Array.prototype.forEach.call( list.children, function ( item, i ) {
			var d = document.createElement( 'button' );
			d.type = 'button';
			d.className = 'sg-gallery__dot';
			d.setAttribute( 'aria-label', ( strings.galCount || '{n} / {tong}' ).replace( '{n}', i + 1 ).replace( '{tong}', list.children.length ) );
			d.addEventListener( 'click', function () {
				list.scrollTo( { left: item.offsetLeft - list.offsetLeft, behavior: behavior } );
			} );
			dots.appendChild( d );
		} );
		gallery.appendChild( dots );
		function updateDots() {
			var max = list.scrollWidth - list.clientWidth;
			var idx = list.scrollLeft >= max - 2 ? list.children.length - 1 : Math.round( list.scrollLeft / step() );
			Array.prototype.forEach.call( dots.children, function ( d, i ) {
				d.classList.toggle( 'is-active', i === idx );
				if ( i === idx ) {
					d.setAttribute( 'aria-current', 'true' );
				} else {
					d.removeAttribute( 'aria-current' );
				}
			} );
		}
		dragScroll( list, step );
		list.addEventListener( 'scroll', function () {
			update();
			updateDots();
		}, { passive: true } );
		window.addEventListener( 'resize', update );
		update();
		updateDots();
	}

	var lightbox = null;

	function buildLightbox() {
		var dlg = document.createElement( 'dialog' );
		dlg.className = 'sg-lightbox';
		dlg.innerHTML =
			'<figure class="sg-lightbox__stage"><img class="sg-lightbox__img" alt="" decoding="async"><figcaption class="sg-lightbox__count" aria-live="polite"></figcaption></figure>' +
			'<button type="button" class="sg-lightbox__btn sg-lightbox__close">' + ICON_CLOSE + '</button>' +
			'<button type="button" class="sg-lightbox__btn sg-lightbox__prev">' + ICON_PREV + '</button>' +
			'<button type="button" class="sg-lightbox__btn sg-lightbox__next">' + ICON_NEXT + '</button>';
		document.body.appendChild( dlg );

		var img = dlg.querySelector( '.sg-lightbox__img' );
		var count = dlg.querySelector( '.sg-lightbox__count' );
		var btnPrev = dlg.querySelector( '.sg-lightbox__prev' );
		var btnNext = dlg.querySelector( '.sg-lightbox__next' );
		var btnClose = dlg.querySelector( '.sg-lightbox__close' );
		btnPrev.setAttribute( 'aria-label', strings.galPrev || '' );
		btnNext.setAttribute( 'aria-label', strings.galNext || '' );
		btnClose.setAttribute( 'aria-label', strings.galClose || '' );

		var state = { links: [], index: 0, opener: null };

		function show( i ) {
			var total = state.links.length;
			state.index = ( i + total ) % total;
			var link = state.links[ state.index ];
			var thumb = link.querySelector( 'img' );
			img.src = link.href;
			img.alt = thumb ? thumb.alt : '';
			count.textContent = ( strings.galCount || '{n} / {tong}' ).replace( '{n}', state.index + 1 ).replace( '{tong}', total );
			btnPrev.hidden = btnNext.hidden = total < 2;
			// Tải trước ảnh kế tiếp để lật nhanh.
			if ( total > 1 ) {
				( new Image() ).src = state.links[ ( state.index + 1 ) % total ].href;
			}
		}
		function close() {
			if ( dlg.open ) {
				dlg.close();
			}
		}

		btnPrev.addEventListener( 'click', function () {
			show( state.index - 1 );
		} );
		btnNext.addEventListener( 'click', function () {
			show( state.index + 1 );
		} );
		btnClose.addEventListener( 'click', close );
		// Bấm ra vùng tối bên ngoài ảnh để đóng.
		dlg.addEventListener( 'click', function ( e ) {
			if ( e.target === dlg || e.target.classList.contains( 'sg-lightbox__stage' ) ) {
				close();
			}
		} );
		dlg.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'ArrowLeft' ) {
				show( state.index - 1 );
			} else if ( e.key === 'ArrowRight' ) {
				show( state.index + 1 );
			}
		} );
		// Vuốt trái/phải trên điện thoại.
		var startX = null;
		dlg.addEventListener( 'touchstart', function ( e ) {
			startX = e.touches.length === 1 ? e.touches[ 0 ].clientX : null;
		}, { passive: true } );
		dlg.addEventListener( 'touchend', function ( e ) {
			if ( startX === null ) {
				return;
			}
			var dx = e.changedTouches[ 0 ].clientX - startX;
			startX = null;
			if ( Math.abs( dx ) > 40 ) {
				show( state.index + ( dx < 0 ? 1 : -1 ) );
			}
		}, { passive: true } );
		dlg.addEventListener( 'close', function () {
			document.documentElement.classList.remove( 'sg-lightbox-open' );
			img.removeAttribute( 'src' );
			if ( state.opener ) {
				state.opener.focus();
			}
		} );

		return {
			open: function ( links, index, opener ) {
				state.links = links;
				state.opener = opener;
				show( index );
				document.documentElement.classList.add( 'sg-lightbox-open' );
				dlg.showModal();
				btnClose.focus();
			},
		};
	}

	function initLightbox( gallery ) {
		if ( typeof HTMLDialogElement !== 'function' ) {
			return; // Trình duyệt quá cũ: liên kết vẫn mở ảnh lớn như bình thường.
		}
		var links = Array.prototype.slice.call( gallery.querySelectorAll( '.sg-gallery__open' ) );
		links.forEach( function ( link, i ) {
			link.addEventListener( 'click', function ( e ) {
				if ( e.metaKey || e.ctrlKey || e.shiftKey ) {
					return;
				}
				e.preventDefault();
				lightbox = lightbox || buildLightbox();
				lightbox.open( links, i, link );
			} );
		} );
	}

	/* ---------- Đánh giá trình chiếu ----------
	   Tự chuyển (≥ 6 giây), dừng khi rê chuột / focus bên trong, nút Tạm dừng/Phát (WCAG 2.2.2),
	   chấm chuyển, vuốt trái/phải trên điện thoại. Bật giảm chuyển động → không tự chạy. */
	function initReviews( root ) {
		var slides = root.querySelectorAll( '.sg-review--slide' );
		var dots = root.querySelectorAll( '.sg-reviews__dot' );
		var toggle = root.querySelector( '.sg-reviews__toggle' );
		var track = root.querySelector( '.sg-reviews__track' );
		if ( slides.length < 2 || ! track ) {
			return;
		}
		root.classList.add( 'is-ready' );
		var current = 0;
		var timer = null;
		var userPaused = reduce.matches; // Giảm chuyển động: bắt đầu ở trạng thái dừng.
		var hover = false;
		var interval = Math.max( 6000, parseInt( root.getAttribute( 'data-interval' ), 10 ) || 7000 );

		function show( i ) {
			current = ( i + slides.length ) % slides.length;
			slides.forEach( function ( s, k ) {
				var on = k === current;
				s.classList.toggle( 'is-active', on );
				if ( on ) {
					s.removeAttribute( 'aria-hidden' );
				} else {
					s.setAttribute( 'aria-hidden', 'true' );
				}
			} );
			dots.forEach( function ( d, k ) {
				d.classList.toggle( 'is-active', k === current );
				if ( k === current ) {
					d.setAttribute( 'aria-current', 'true' );
				} else {
					d.removeAttribute( 'aria-current' );
				}
			} );
		}
		function stop() {
			window.clearInterval( timer );
			timer = null;
		}
		function sync() {
			var running = ! userPaused && ! hover && ! document.hidden;
			stop();
			if ( running ) {
				timer = window.setInterval( function () {
					show( current + 1 );
				}, interval );
			}
			// Đang tự chạy: không đọc to mỗi lần chuyển; đã dừng: đọc khi người dùng tự chuyển.
			track.setAttribute( 'aria-live', running ? 'off' : 'polite' );
			if ( toggle ) {
				toggle.classList.toggle( 'is-paused', userPaused );
				toggle.setAttribute( 'aria-label', toggle.getAttribute( userPaused ? 'data-label-play' : 'data-label-pause' ) );
			}
		}
		dots.forEach( function ( d ) {
			d.addEventListener( 'click', function () {
				show( parseInt( d.getAttribute( 'data-index' ), 10 ) || 0 );
				sync();
			} );
		} );
		if ( toggle ) {
			toggle.addEventListener( 'click', function () {
				userPaused = ! userPaused;
				sync();
			} );
		}
		root.addEventListener( 'mouseenter', function () {
			hover = true;
			sync();
		} );
		root.addEventListener( 'mouseleave', function () {
			hover = false;
			sync();
		} );
		root.addEventListener( 'focusin', function () {
			hover = true;
			sync();
		} );
		root.addEventListener( 'focusout', function ( e ) {
			if ( ! root.contains( e.relatedTarget ) ) {
				hover = false;
				sync();
			}
		} );
		document.addEventListener( 'visibilitychange', sync );
		// Vuốt trên điện thoại
		var startX = null;
		var startY = 0;
		track.addEventListener( 'touchstart', function ( e ) {
			startX = e.touches[ 0 ].clientX;
			startY = e.touches[ 0 ].clientY;
		}, { passive: true } );
		track.addEventListener( 'touchend', function ( e ) {
			if ( null === startX ) {
				return;
			}
			var dx = e.changedTouches[ 0 ].clientX - startX;
			var dy = e.changedTouches[ 0 ].clientY - startY;
			startX = null;
			if ( Math.abs( dx ) > 40 && Math.abs( dx ) > Math.abs( dy ) ) {
				show( current + ( dx < 0 ? 1 : -1 ) );
				sync();
			}
		} );
		root.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'ArrowRight' || e.key === 'ArrowLeft' ) {
				show( current + ( e.key === 'ArrowRight' ? 1 : -1 ) );
				var dot = dots[ current ];
				if ( dot && root.contains( document.activeElement ) && document.activeElement.classList.contains( 'sg-reviews__dot' ) ) {
					dot.focus();
				}
				sync();
			}
		} );
		show( 0 );
		sync();
	}

	function init() {
		document.querySelectorAll( '.sg-reviews--slider' ).forEach( initReviews );
		document.querySelectorAll( '.sg-gallery--scroller' ).forEach( initScroller );
		document.querySelectorAll( '.sg-gallery' ).forEach( initLightbox );
		document.querySelectorAll( '.sg-banner' ).forEach( initBanner );
		document.querySelectorAll( '.sg-media__video' ).forEach( initCardVideo );
		document.querySelectorAll( '.sg-filter' ).forEach( initFilter );
		document.querySelectorAll( '.sg-filter__chips' ).forEach( function ( el ) {
			dragScroll( el );
		} );
		document.querySelectorAll( '.sg-menu.has-toggle' ).forEach( initMenu );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
