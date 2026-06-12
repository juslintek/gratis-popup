/**
 * Gratis Popup — accessible modal controller.
 *
 * Handles trigger detection (time / scroll / exit-intent / click), an
 * accessible dialog (focus trap, ESC to close, focus restore, aria state),
 * and a cookie-based frequency cap.
 */
( function () {
	'use strict';

	var FOCUSABLE = [
		'a[href]',
		'button:not([disabled])',
		'input:not([disabled]):not([type="hidden"])',
		'select:not([disabled])',
		'textarea:not([disabled])',
		'[tabindex]:not([tabindex="-1"])'
	].join( ',' );

	function readCookie( name ) {
		var match = document.cookie.match( '(?:^|; )' + name.replace( /([.*+?^${}()|[\]\\])/g, '\\$1' ) + '=([^;]*)' );
		return match ? decodeURIComponent( match[ 1 ] ) : null;
	}

	function writeCookie( name, value, days ) {
		var expires = '';
		if ( days > 0 ) {
			var date = new Date();
			date.setTime( date.getTime() + days * 864e5 );
			expires = '; expires=' + date.toUTCString();
		}
		document.cookie = name + '=' + encodeURIComponent( value ) + expires + '; path=/; SameSite=Lax';
	}

	function PopupController( root ) {
		this.root = root;
		this.id = root.getAttribute( 'data-popup-id' );
		this.dialog = root.querySelector( '.gratis-popup' );
		this.trigger = root.getAttribute( 'data-trigger' ) || 'time';
		this.delay = parseInt( root.getAttribute( 'data-delay' ), 10 ) || 0;
		this.scroll = parseInt( root.getAttribute( 'data-scroll' ), 10 ) || 0;
		this.selector = root.getAttribute( 'data-selector' ) || '';
		this.frequency = parseInt( root.getAttribute( 'data-frequency' ), 10 ) || 0;
		this.overlay = root.getAttribute( 'data-overlay' ) === '1';
		this.cookieName = 'gratis_popup_' + this.id;
		this.lastFocus = null;
		this.open = false;
		this.onKeydown = this.handleKeydown.bind( this );
	}

	PopupController.prototype.capped = function () {
		return readCookie( this.cookieName ) === '1';
	};

	PopupController.prototype.init = function () {
		if ( this.capped() || ! this.dialog ) {
			return;
		}

		var self = this;
		var closeButtons = this.root.querySelectorAll( '[data-popup-close]' );
		Array.prototype.forEach.call( closeButtons, function ( btn ) {
			btn.addEventListener( 'click', function () {
				self.close();
			} );
		} );

		// Click on the dimmed overlay (outside the dialog) closes it.
		if ( this.overlay ) {
			this.root.addEventListener( 'click', function ( e ) {
				if ( e.target === self.root ) {
					self.close();
				}
			} );
		}

		this.bindTrigger();
	};

	PopupController.prototype.bindTrigger = function () {
		var self = this;

		switch ( this.trigger ) {
			case 'scroll':
				var onScroll = function () {
					var max = document.documentElement.scrollHeight - window.innerHeight;
					var pct = max > 0 ? ( window.pageYOffset / max ) * 100 : 100;
					if ( pct >= self.scroll ) {
						window.removeEventListener( 'scroll', onScroll );
						self.show();
					}
				};
				window.addEventListener( 'scroll', onScroll, { passive: true } );
				break;

			case 'exit':
				var onLeave = function ( e ) {
					if ( e.clientY <= 0 ) {
						document.removeEventListener( 'mouseleave', onLeave );
						self.show();
					}
				};
				document.addEventListener( 'mouseleave', onLeave );
				break;

			case 'click':
				if ( this.selector ) {
					document.addEventListener( 'click', function ( e ) {
						var target = e.target.closest ? e.target.closest( self.selector ) : null;
						if ( target ) {
							e.preventDefault();
							self.show();
						}
					} );
				}
				break;

			case 'time':
			default:
				window.setTimeout( function () {
					self.show();
				}, this.delay * 1000 );
				break;
		}
	};

	PopupController.prototype.focusable = function () {
		return Array.prototype.filter.call(
			this.dialog.querySelectorAll( FOCUSABLE ),
			function ( el ) {
				return el.offsetWidth > 0 || el.offsetHeight > 0 || el === document.activeElement;
			}
		);
	};

	PopupController.prototype.handleKeydown = function ( e ) {
		if ( e.key === 'Escape' || e.keyCode === 27 ) {
			e.preventDefault();
			this.close();
			return;
		}

		if ( e.key !== 'Tab' && e.keyCode !== 9 ) {
			return;
		}

		var items = this.focusable();
		if ( ! items.length ) {
			e.preventDefault();
			this.dialog.focus();
			return;
		}

		var first = items[ 0 ];
		var last = items[ items.length - 1 ];

		if ( e.shiftKey && document.activeElement === first ) {
			e.preventDefault();
			last.focus();
		} else if ( ! e.shiftKey && document.activeElement === last ) {
			e.preventDefault();
			first.focus();
		}
	};

	PopupController.prototype.show = function () {
		if ( this.open || this.capped() ) {
			return;
		}
		this.open = true;
		this.lastFocus = document.activeElement;
		this.root.hidden = false;
		// Force reflow so the opening transition runs.
		void this.root.offsetWidth;
		this.root.classList.add( 'is-open' );

		var items = this.focusable();
		( items.length ? items[ 0 ] : this.dialog ).focus();

		document.addEventListener( 'keydown', this.onKeydown, true );
	};

	PopupController.prototype.close = function () {
		if ( ! this.open ) {
			return;
		}
		this.open = false;
		this.root.classList.remove( 'is-open' );
		this.root.hidden = true;
		document.removeEventListener( 'keydown', this.onKeydown, true );

		// Frequency cap: remember the dismissal. days = 0 -> session cookie.
		writeCookie( this.cookieName, '1', this.frequency );

		if ( this.lastFocus && typeof this.lastFocus.focus === 'function' ) {
			this.lastFocus.focus();
		}
	};

	function boot() {
		var roots = document.querySelectorAll( '.gratis-popup-root' );
		Array.prototype.forEach.call( roots, function ( root ) {
			new PopupController( root ).init();
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
