/**
 * Settings → Institutional Email.
 *
 * Saves each on/off toggle the moment it is flipped. Every toggle is also a
 * field of the form, so "Save Changes" posts its current state; text fields
 * still wait for that button.
 */
( function () {
	'use strict';

	var settings = window.ieAdmin || {};
	var LINGER = 1800;

	/**
	 * The keys whose toggles are a group of boxes saved as one list.
	 */
	var GROUPS = [ 'forms', 'teacher_networks' ];

	/**
	 * The value a toggle saves: '1' / '0', or every ticked box of its group.
	 *
	 * @param {HTMLInputElement} input The flipped toggle.
	 * @return {string|string[]} Value to post.
	 */
	function valueOf( input ) {
		var key = input.getAttribute( 'data-ie-autosave' );
		if ( -1 === GROUPS.indexOf( key ) ) {
			return input.checked ? '1' : '0';
		}
		return Array.prototype.map.call(
			document.querySelectorAll( 'input[data-ie-autosave="' + key + '"]:checked' ),
			function ( box ) {
				return box.value;
			}
		);
	}

	/**
	 * Show the stored value again: one box, or its whole group.
	 *
	 * @param {HTMLInputElement} input The flipped toggle.
	 * @param {boolean|string[]} value Stored value.
	 */
	function show( input, value ) {
		if ( Array.isArray( value ) ) {
			document.querySelectorAll( 'input[data-ie-autosave="' + input.getAttribute( 'data-ie-autosave' ) + '"]' ).forEach( function ( box ) {
				box.checked = -1 !== value.indexOf( box.value );
			} );
			return;
		}
		input.checked = !! value;
	}

	/**
	 * The status badge beside a toggle, created on first use.
	 *
	 * @param {HTMLInputElement} input Toggle.
	 * @return {HTMLElement} Badge.
	 */
	function badgeFor( input ) {
		var anchor = input.closest( '.ie-toggle' ) || input;
		var badge = anchor.nextElementSibling;
		if ( ! badge || ! badge.classList.contains( 'ie-autosave-badge' ) ) {
			badge = document.createElement( 'span' );
			badge.className = 'ie-autosave-badge';
			badge.setAttribute( 'role', 'status' );
			anchor.parentNode.insertBefore( badge, anchor.nextSibling );
		}
		return badge;
	}

	function setBadge( badge, state, text ) {
		badge.className = 'ie-autosave-badge ie-autosave-badge--' + state;
		badge.textContent = text;
		badge.hidden = false;
	}

	/**
	 * Post one toggle.
	 *
	 * @param {HTMLInputElement} input The flipped toggle.
	 */
	function save( input ) {
		var key = input.getAttribute( 'data-ie-autosave' );
		var before = ! input.checked;
		var value = valueOf( input );
		var badge = badgeFor( input );
		var body = new window.FormData();
		body.append( 'action', settings.action );
		body.append( 'nonce', settings.nonce );
		body.append( 'key', key );
		if ( Array.isArray( value ) ) {
			value.forEach( function ( v ) {
				body.append( 'value[]', v );
			} );
		} else {
			body.append( 'value', value );
		}

		window.clearTimeout( badge.ieTimer );
		setBadge( badge, 'saving', settings.saving );
		window.fetch( settings.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
			.then( function ( response ) {
				return response.json().catch( function () {
					return {};
				} );
			} )
			.then( function ( json ) {
				if ( ! json || ! json.success ) {
					throw new Error( json && json.data && json.data.message ? json.data.message : settings.failed );
				}
				show( input, json.data.value );
				setBadge( badge, 'saved', settings.saved );
				badge.ieTimer = window.setTimeout( function () {
					badge.hidden = true;
				}, LINGER );
			} )
			.catch( function ( error ) {
				input.checked = before;
				syncShown();
				setBadge( badge, 'error', error && error.message ? error.message : settings.failed );
			} );
	}

	/**
	 * Rows that only matter while a toggle is on (or off) follow it, at
	 * once and again if a failed save puts the toggle back.
	 */
	function syncShown() {
		function on( key ) {
			var toggle = document.querySelector( 'input[type="checkbox"][data-ie-autosave="' + key + '"]' );
			return !! ( toggle && toggle.checked );
		}
		document.querySelectorAll( '[data-ie-shown-by]' ).forEach( function ( row ) {
			row.hidden = ! on( row.getAttribute( 'data-ie-shown-by' ) );
		} );
		document.querySelectorAll( '[data-ie-hidden-by]' ).forEach( function ( row ) {
			row.hidden = on( row.getAttribute( 'data-ie-hidden-by' ) );
		} );
	}
	document.querySelectorAll( 'input[data-ie-autosave]' ).forEach( function ( input ) {
		input.addEventListener( 'change', syncShown );
	} );
	syncShown();

	/**
	 * Switching the design off clears it: the fields it shows go back to
	 * their defaults, as the server stores them, so switching it on again
	 * starts from the defaults rather than from what was set before.
	 */
	var design = document.querySelector( 'input[type="checkbox"][data-ie-autosave="custom_style"]' );
	if ( design ) {
		design.addEventListener( 'change', function () {
			if ( design.checked ) {
				return;
			}
			document.querySelectorAll( '[data-ie-shown-by="custom_style"] [data-ie-default]' ).forEach( function ( field ) {
				var initial = field.getAttribute( 'data-ie-default' );
				if ( 'radio' === field.type ) {
					field.checked = field.value === initial;
				} else {
					field.value = initial;
				}
			} );
		} );
	}

	/**
	 * Copy buttons: the field named by `data-ie-copy` goes to the
	 * clipboard, and the button says so for a moment.
	 */
	document.querySelectorAll( '[data-ie-copy]' ).forEach( function ( button ) {
		var label = button.textContent;
		button.addEventListener( 'click', function () {
			var field = document.getElementById( button.getAttribute( 'data-ie-copy' ) );
			if ( ! field ) {
				return;
			}
			function done() {
				button.textContent = settings.copied || label;
				window.setTimeout( function () {
					button.textContent = label;
				}, LINGER );
			}
			field.select();
			if ( window.navigator.clipboard && window.navigator.clipboard.writeText ) {
				window.navigator.clipboard.writeText( field.value ).then( done, function () {
					// The text stays selected for a manual copy.
				} );
			} else if ( document.execCommand && document.execCommand( 'copy' ) ) {
				done();
			}
		} );
	} );

	if ( settings.action && settings.nonce && window.fetch ) {
		document.querySelectorAll( 'input[data-ie-autosave]' ).forEach( function ( input ) {
			input.addEventListener( 'change', function () {
				save( input );
			} );
		} );
	}

	/**
	 * Vertical tabs. Every panel stays in the one form, so switching never
	 * loses an edit; the tab only chooses what shows. The address and the
	 * page "Save Changes" returns to carry it, so a save lands on the same tab.
	 */
	var tabs = document.querySelector( '.ie-tabs' );
	if ( tabs ) {
		var activate = function ( id ) {
			var panel = document.getElementById( 'ie-panel-' + id );
			if ( ! panel ) {
				return;
			}
			tabs.querySelectorAll( '[data-ie-tab]' ).forEach( function ( tab ) {
				var on = tab.getAttribute( 'data-ie-tab' ) === id;
				tab.classList.toggle( 'is-active', on );
				tab.setAttribute( 'aria-selected', on ? 'true' : 'false' );
			} );
			tabs.querySelectorAll( '.ie-tabs__panel' ).forEach( function ( p ) {
				p.classList.toggle( 'is-active', p === panel );
			} );
			// The documentation has nothing to save.
			var submit = document.querySelector( '.ie-settings .ie-submit' );
			if ( submit ) {
				submit.hidden = 'documentation' === id;
			}
			var url = new window.URL( window.location.href );
			url.searchParams.set( 'tab', id );
			if ( window.history && window.history.replaceState ) {
				window.history.replaceState( null, '', url.toString() );
			}
			var referer = document.querySelector( 'input[name="_wp_http_referer"]' );
			if ( referer ) {
				var back = new window.URL( referer.value, window.location.href );
				back.searchParams.set( 'tab', id );
				back.searchParams.delete( 'settings-updated' );
				referer.value = back.pathname + back.search;
			}
		};

		tabs.querySelectorAll( '[data-ie-tab]' ).forEach( function ( tab ) {
			tab.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				activate( tab.getAttribute( 'data-ie-tab' ) );
				tab.focus();
			} );
		} );

		// A link inside a panel that points at another tab.
		tabs.querySelectorAll( '[data-ie-goto]' ).forEach( function ( link ) {
			link.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				activate( link.getAttribute( 'data-ie-goto' ) );
			} );
		} );

		// A required field in a hidden panel would block "Save Changes" with
		// no visible reason; show its panel so the browser can point at it.
		tabs.addEventListener( 'invalid', function ( event ) {
			var panel = event.target.closest( '.ie-tabs__panel' );
			if ( panel && ! panel.classList.contains( 'is-active' ) ) {
				activate( panel.id.replace( 'ie-panel-', '' ) );
			}
		}, true );

		tabs.classList.add( 'is-ready' );
	}
}() );
