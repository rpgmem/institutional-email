import { beforeEach, describe, expect, it } from 'vitest';
import { runScript } from './helpers.js';

function tab( id, active ) {
	return `<li><a class="ie-tabs__tab${ active ? ' is-active' : '' }" href="?page=x&tab=${ id }" data-ie-tab="${ id }" aria-selected="${ active }">${ id }</a></li>`;
}

function panel( id, active, inner = '' ) {
	return `<div class="ie-tabs__panel${ active ? ' is-active' : '' }" id="ie-panel-${ id }">${ inner }</div>`;
}

beforeEach( () => {
	window.ieAdmin = {};
	window.history.replaceState( null, '', '/wp-admin/options-general.php?page=x' );
	document.body.innerHTML = `<form>
		<input type="hidden" name="_wp_http_referer" value="/wp-admin/options-general.php?page=x&amp;settings-updated=true">
		<div class="ie-tabs"><ul>${ tab( 'general', true ) }${ tab( 'logo', false ) }</ul>
		<div>${ panel( 'general', true ) }${ panel( 'logo', false, '<input id="w" type="number" required>' ) }</div></div>
	</form>`;
	runScript( 'assets/js/admin-settings.js' );
} );

describe( 'settings tabs', () => {
	it( 'marks the tabs ready, so the hidden panels hide only with JavaScript', () => {
		expect( document.querySelector( '.ie-tabs' ).classList.contains( 'is-ready' ) ).toBe( true );
	} );

	it( 'switches panel, address and the page a save returns to', () => {
		document.querySelector( '[data-ie-tab="logo"]' ).click();

		expect( document.getElementById( 'ie-panel-logo' ).classList.contains( 'is-active' ) ).toBe( true );
		expect( document.getElementById( 'ie-panel-general' ).classList.contains( 'is-active' ) ).toBe( false );
		expect( document.querySelector( '[data-ie-tab="logo"]' ).getAttribute( 'aria-selected' ) ).toBe( 'true' );
		expect( document.querySelector( '[data-ie-tab="general"]' ).getAttribute( 'aria-selected' ) ).toBe( 'false' );
		expect( window.location.search ).toBe( '?page=x&tab=logo' );
		expect( document.querySelector( 'input[name="_wp_http_referer"]' ).value ).toBe( '/wp-admin/options-general.php?page=x&tab=logo' );
	} );

	it( 'opens the panel of a field the browser refuses', () => {
		document.getElementById( 'w' ).dispatchEvent( new Event( 'invalid', { cancelable: true } ) );
		expect( document.getElementById( 'ie-panel-logo' ).classList.contains( 'is-active' ) ).toBe( true );
	} );

	it( 'ignores a tab with no panel', () => {
		document.querySelector( '[data-ie-tab="general"]' ).setAttribute( 'data-ie-tab', 'nope' );
		document.querySelector( '[data-ie-tab="nope"]' ).click();
		expect( document.getElementById( 'ie-panel-general' ).classList.contains( 'is-active' ) ).toBe( true );
	} );
} );
