/* Sunny Grove Booking – form yêu cầu đặt phòng (không cần thư viện). */
( function () {
	'use strict';

	var cfg = window.sgbForm || { texts: {} };
	var T = cfg.texts || {};

	function pad( n ) {
		return ( n < 10 ? '0' : '' ) + n;
	}
	function ymd( d ) {
		return d.getFullYear() + '-' + pad( d.getMonth() + 1 ) + '-' + pad( d.getDate() );
	}
	function parse( v ) {
		var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec( v || '' );
		return m ? new Date( +m[ 1 ], +m[ 2 ] - 1, +m[ 3 ] ) : null;
	}
	function addDays( d, n ) {
		var x = new Date( d.getTime() );
		x.setDate( x.getDate() + n );
		return x;
	}
	function nightsBetween( a, b ) {
		return Math.round( ( b - a ) / 86400000 );
	}

	/* Ngày nhận / trả: tự giới hạn và tự chỉnh ngày trả khi chọn ngày nhận. */
	function initDates( form ) {
		var inEl = form.querySelector( '[data-sgb-checkin]' );
		var outEl = form.querySelector( '[data-sgb-checkout]' );
		var nightsEl = form.querySelector( '[data-sgb-nights]' );
		if ( ! inEl || ! outEl ) {
			return;
		}
		var today = new Date();
		today.setHours( 0, 0, 0, 0 );
		inEl.min = ymd( today );

		function sync() {
			var a = parse( inEl.value );
			if ( a ) {
				outEl.min = ymd( addDays( a, 1 ) );
				var b = parse( outEl.value );
				if ( ! b || b <= a ) {
					outEl.value = ymd( addDays( a, 1 ) );
				}
			} else {
				outEl.min = ymd( addDays( today, 1 ) );
			}
			if ( nightsEl ) {
				var x = parse( inEl.value );
				var y = parse( outEl.value );
				nightsEl.textContent = ( x && y && y > x ) ? ( T.nights || '{n}' ).replace( '{n}', nightsBetween( x, y ) ) : '';
			}
		}
		inEl.addEventListener( 'change', sync );
		outEl.addEventListener( 'change', sync );
		sync();
	}

	/* Điền sẵn từ đường dẫn (khung tìm phòng ở trang chủ, nút "Đặt phòng này", ưu đãi). */
	function prefill( form ) {
		var params = new URLSearchParams( window.location.search );
		var map = { checkin: 'checkin', checkout: 'checkout', adults: 'adults', children: 'children', room: 'room', note: 'note' };
		Object.keys( map ).forEach( function ( key ) {
			var value = params.get( key );
			var field = form.elements[ map[ key ] ];
			if ( value === null || ! field ) {
				return;
			}
			if ( field.tagName === 'SELECT' ) {
				var ok = Array.prototype.some.call( field.options, function ( o ) {
					return o.value === value;
				} );
				if ( ok ) {
					field.value = value;
				}
			} else if ( key === 'checkin' || key === 'checkout' ) {
				if ( parse( value ) ) {
					field.value = value;
				}
			} else {
				field.value = value.slice( 0, 1000 );
			}
		} );
	}

	function setError( form, name, message ) {
		var box = form.querySelector( '[data-error-for="' + name + '"]' );
		var field = form.elements[ name ] || ( name === 'room' ? form.elements.room_text : null );
		if ( box ) {
			box.textContent = message || '';
		}
		if ( field && field.setAttribute ) {
			if ( message ) {
				field.setAttribute( 'aria-invalid', 'true' );
			} else {
				field.removeAttribute( 'aria-invalid' );
			}
		}
	}
	function clearErrors( form ) {
		form.querySelectorAll( '[data-error-for]' ).forEach( function ( box ) {
			setError( form, box.getAttribute( 'data-error-for' ), '' );
		} );
		var alert = form.querySelector( '[data-sgb-alert]' );
		if ( alert ) {
			alert.hidden = true;
			alert.textContent = '';
		}
	}
	function showAlert( form, message ) {
		var alert = form.querySelector( '[data-sgb-alert]' );
		if ( alert ) {
			alert.textContent = message;
			alert.hidden = false;
			alert.scrollIntoView( { block: 'center', behavior: 'smooth' } );
		}
	}

	function validate( form ) {
		var errors = {};
		[ 'checkin', 'checkout', 'name', 'phone' ].forEach( function ( n ) {
			if ( form.elements[ n ] && ! form.elements[ n ].value.trim() ) {
				errors[ n ] = T.required;
			}
		} );
		var email = form.elements.email;
		if ( email ) {
			if ( email.required && ! email.value.trim() ) {
				errors.email = T.required;
			} else if ( email.value.trim() && ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( email.value.trim() ) ) {
				errors.email = T.email;
			}
		}
		var a = parse( form.elements.checkin && form.elements.checkin.value );
		var b = parse( form.elements.checkout && form.elements.checkout.value );
		var today = new Date();
		today.setHours( 0, 0, 0, 0 );
		if ( a && a < today ) {
			errors.checkin = T.past;
		}
		if ( a && b && b <= a ) {
			errors.checkout = T.dates;
		}
		var digits = ( form.elements.phone && form.elements.phone.value || '' ).replace( /\D+/g, '' );
		if ( ! errors.phone && ( digits.length < 8 || digits.length > 15 ) ) {
			errors.phone = T.phone;
		}
		return errors;
	}

	function focusFirstError( form, errors ) {
		var first = Object.keys( errors )[ 0 ];
		var field = first && ( form.elements[ first ] || form.elements.room_text );
		if ( field && field.focus ) {
			field.focus();
		}
	}

	function initFull( form ) {
		var wrap = form.closest( '.sgb-wrap' );
		var success = wrap ? wrap.querySelector( '[data-sgb-success]' ) : null;
		var button = form.querySelector( '[data-sgb-submit]' );
		var tsField = form.elements.sgb_ts;
		if ( tsField ) {
			tsField.value = Math.floor( Date.now() / 1000 ); // Thời điểm mở trang thật (kể cả khi trang được cache).
		}
		prefill( form );
		initDates( form );

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			clearErrors( form );
			var errors = validate( form );
			if ( Object.keys( errors ).length ) {
				Object.keys( errors ).forEach( function ( k ) {
					setError( form, k, errors[ k ] );
				} );
				showAlert( form, T.generic );
				focusFirstError( form, errors );
				return;
			}

			button.disabled = true;
			button.textContent = T.submitting;

			fetch( cfg.rest + 'token', { credentials: 'same-origin', cache: 'no-store' } )
				.then( function ( r ) {
					return r.json();
				} )
				.then( function ( tok ) {
					var data = {};
					new FormData( form ).forEach( function ( v, k ) {
						data[ k ] = v;
					} );
					data.sgb_nonce = tok.nonce;
					return fetch( cfg.rest + 'request', {
						method: 'POST',
						credentials: 'same-origin',
						headers: { 'Content-Type': 'application/json' },
						body: JSON.stringify( data )
					} );
				} )
				.then( function ( r ) {
					return r.json();
				} )
				.then( function ( res ) {
					if ( res && res.ok ) {
						form.hidden = true;
						if ( success ) {
							success.innerHTML = res.html;
							success.hidden = false;
							success.focus();
							success.scrollIntoView( { block: 'center', behavior: 'smooth' } );
							var again = success.querySelector( '[data-sgb-again]' );
							if ( again ) {
								again.addEventListener( 'click', function () {
									success.hidden = true;
									form.reset();
									form.hidden = false;
									if ( tsField ) {
										tsField.value = Math.floor( Date.now() / 1000 );
									}
									form.elements.checkin.focus();
								} );
							}
						}
						return;
					}
					var errs = ( res && res.errors ) || {};
					Object.keys( errs ).forEach( function ( k ) {
						setError( form, k, errs[ k ] );
					} );
					showAlert( form, ( res && res.message ) || T.generic );
					focusFirstError( form, errs );
				} )
				.catch( function () {
					showAlert( form, T.network );
				} )
				.then( function () {
					button.disabled = false;
					button.textContent = T.submit;
				} );
		} );
	}

	function initCompact( form ) {
		initDates( form );
		form.addEventListener( 'submit', function ( e ) {
			var a = parse( form.elements.checkin.value );
			var b = parse( form.elements.checkout.value );
			if ( ! a || ! b || b <= a ) {
				e.preventDefault();
				( a ? form.elements.checkout : form.elements.checkin ).focus();
			}
		} );
	}

	function init() {
		document.querySelectorAll( 'form[data-sgb="full"]' ).forEach( initFull );
		document.querySelectorAll( 'form[data-sgb="compact"]' ).forEach( initCompact );
	}
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
