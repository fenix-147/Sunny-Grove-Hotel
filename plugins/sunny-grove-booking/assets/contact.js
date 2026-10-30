/* Sunny Grove – form liên hệ (không cần thư viện). */
( function () {
	'use strict';

	var cfg = window.sgbContact || {};
	var T = cfg.texts || {};

	function setError( form, name, message ) {
		var box = form.querySelector( '[data-error-for="' + name + '"]' );
		var field = form.elements[ name ];
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
		var name = form.elements.name.value.trim();
		var email = form.elements.email.value.trim();
		var message = form.elements.message.value.trim();
		if ( ! name ) {
			errors.name = T.required;
		}
		if ( ! email ) {
			errors.email = T.required;
		} else if ( ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( email ) ) {
			errors.email = T.email;
		}
		if ( ! message ) {
			errors.message = T.required;
		} else if ( message.length < 10 ) {
			errors.message = T.short;
		}
		return errors;
	}
	function focusFirst( form, errors ) {
		var first = Object.keys( errors )[ 0 ];
		if ( first && form.elements[ first ] ) {
			form.elements[ first ].focus();
		}
	}

	function init( form ) {
		var wrap = form.closest( '.sgb-wrap' );
		var success = wrap ? wrap.querySelector( '[data-sgb-success]' ) : null;
		var button = form.querySelector( '[data-sgb-submit]' );
		// Thời điểm mở trang thật (kể cả khi trang được cache) – dùng để chặn bot gửi quá nhanh.
		form.elements.sgb_ts.value = Math.floor( Date.now() / 1000 );
		form.elements.sgb_source.value = window.location.href;

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			clearErrors( form );
			var errors = validate( form );
			if ( Object.keys( errors ).length ) {
				Object.keys( errors ).forEach( function ( k ) {
					setError( form, k, errors[ k ] );
				} );
				showAlert( form, T.generic );
				focusFirst( form, errors );
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
					return fetch( cfg.rest + 'contact', {
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
							var again = success.querySelector( '[data-sgb-again]' );
							if ( again ) {
								again.addEventListener( 'click', function () {
									form.reset();
									form.elements.sgb_ts.value = Math.floor( Date.now() / 1000 );
									success.hidden = true;
									form.hidden = false;
									form.elements.name.focus();
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
					focusFirst( form, errs );
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

	function start() {
		document.querySelectorAll( 'form[data-sgb-contact]' ).forEach( init );
	}
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
}() );
