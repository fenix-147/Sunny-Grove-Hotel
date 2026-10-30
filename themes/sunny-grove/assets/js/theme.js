/* Sunny Grove – hiệu ứng nhẹ: đầu trang khi cuộn, hiện dần khi cuộn, nút trượt cho băng chuyền. */
( function () {
	'use strict';

	var strings = window.sunnyGrove || {};
	var reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	/* Đầu trang có bóng nhẹ khi cuộn */
	function initHeader() {
		var header = document.querySelector( '.sg-header' );
		if ( ! header ) {
			return;
		}
		var update = function () {
			header.classList.toggle( 'is-scrolled', window.scrollY > 8 );
		};
		update();
		window.addEventListener( 'scroll', update, { passive: true } );
		document.documentElement.style.setProperty( '--sg-header-height', header.offsetHeight + 'px' );
	}

	/* Hiện dần khi cuộn tới */
	function initReveal() {
		document.querySelectorAll( '.wp-block-post-template' ).forEach( function ( list ) {
			Array.prototype.forEach.call( list.children, function ( item, i ) {
				item.classList.add( 'sg-reveal-item' );
				item.style.setProperty( '--sg-delay', Math.min( i, 5 ) * 0.08 + 's' );
			} );
		} );
		var targets = document.querySelectorAll( '.sg-reveal, .is-style-sg-reveal, .sg-reveal-item' );
		if ( reduce || ! ( 'IntersectionObserver' in window ) ) {
			targets.forEach( function ( el ) {
				el.classList.add( 'is-visible' );
			} );
			return;
		}
		var io = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( entry.isIntersecting ) {
					entry.target.classList.add( 'is-visible' );
					io.unobserve( entry.target );
				}
			} );
		}, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 } );
		targets.forEach( function ( el ) {
			io.observe( el );
		} );
		// Mục bị bộ lọc ẩn rồi hiện lại: hiện ngay.
		document.addEventListener( 'sg:filtered', function ( e ) {
			e.target.querySelectorAll( '.sg-reveal-item' ).forEach( function ( el ) {
				el.classList.add( 'is-visible' );
			} );
		} );
	}

	/* Băng chuyền: thêm nút trái/phải, vẫn vuốt được trên điện thoại */
	var icon = function ( d ) {
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="' + d + '"/></svg>';
	};
	function initCarousel( query ) {
		var track = query.querySelector( '.wp-block-post-template' );
		if ( ! track || query.querySelector( '.sg-carousel-nav' ) ) {
			return;
		}
		var nav = document.createElement( 'div' );
		nav.className = 'sg-carousel-nav';
		nav.innerHTML =
			'<button type="button" class="sg-carousel-prev" aria-label="' + ( strings.prev || '' ) + '">' + icon( 'M15 5l-7 7 7 7' ) + '</button>' +
			'<button type="button" class="sg-carousel-next" aria-label="' + ( strings.next || '' ) + '">' + icon( 'M9 5l7 7-7 7' ) + '</button>';
		query.insertBefore( nav, track );
		var prev = nav.querySelector( '.sg-carousel-prev' );
		var next = nav.querySelector( '.sg-carousel-next' );

		function step() {
			var item = Array.prototype.find.call( track.children, function ( li ) {
				return ! li.hidden;
			} );
			var gap = parseFloat( getComputedStyle( track ).columnGap ) || 16;
			return item ? item.getBoundingClientRect().width + gap : track.clientWidth * 0.8;
		}
		function update() {
			var max = track.scrollWidth - track.clientWidth - 2;
			prev.disabled = track.scrollLeft <= 2;
			next.disabled = track.scrollLeft >= max;
			nav.hidden = track.scrollWidth <= track.clientWidth + 2;
		}
		prev.addEventListener( 'click', function () {
			track.scrollBy( { left: -step(), behavior: reduce ? 'auto' : 'smooth' } );
		} );
		next.addEventListener( 'click', function () {
			track.scrollBy( { left: step(), behavior: reduce ? 'auto' : 'smooth' } );
		} );
		track.addEventListener( 'scroll', update, { passive: true } );
		window.addEventListener( 'resize', update );
		query.addEventListener( 'sg:filtered', function () {
			track.scrollTo( { left: 0 } );
			update();
		} );
		update();
	}

	function init() {
		initHeader();
		initReveal();
		document.querySelectorAll( '.is-style-sg-carousel' ).forEach( initCarousel );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
