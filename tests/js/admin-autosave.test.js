import { readFileSync } from 'node:fs';
import path from 'node:path';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { root } from './helpers.js';

const script = readFileSync( path.join( root, 'assets/js/admin-settings.js' ), 'utf8' );

function toggle( key, value = '1', checked = true ) {
	return `<label class="ie-toggle"><input type="checkbox" value="${ value }"${ checked ? ' checked' : '' } data-ie-autosave="${ key }"><span class="ie-toggle-track"></span></label>`;
}

function boot( answer ) {
	window.fetch = vi.fn( () => Promise.resolve( { json: () => Promise.resolve( answer ) } ) );
	new Function( script )();
}

function flush() {
	return new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
}

function posted() {
	return Object.fromEntries( [ ...window.fetch.mock.calls[ 0 ][ 1 ].body.entries() ].reduce( ( map, [ k, v ] ) => {
		map.set( k, map.has( k ) ? [].concat( map.get( k ), v ) : v );
		return map;
	}, new Map() ) );
}

beforeEach( () => {
	window.ieAdmin = { ajaxUrl: '/ajax', action: 'ie_autosave', nonce: 'n', saving: 'Saving', saved: 'Saved', failed: 'Not saved' };
} );

describe( 'settings toggles', () => {
	it( 'posts the flipped toggle and shows it saved', async () => {
		document.body.innerHTML = `<p>${ toggle( 'some_toggle' ) }</p>`;
		boot( { success: true, data: { value: false } } );
		const box = document.querySelector( 'input' );
		box.checked = false;
		box.dispatchEvent( new Event( 'change' ) );

		expect( posted() ).toEqual( { action: 'ie_autosave', nonce: 'n', key: 'some_toggle', value: '0' } );
		expect( document.querySelector( '.ie-autosave-badge' ).textContent ).toBe( 'Saving' );
		await flush();
		expect( document.querySelector( '.ie-autosave-badge' ).className ).toContain( '--saved' );
		expect( box.checked ).toBe( false );
	} );

	it( 'shows the rows a toggle governs only while it is on, and again after a failed save', async () => {
		document.body.innerHTML = `<table><tr><td>${ toggle( 'some_toggle', '1', false ) }</td></tr><tr data-ie-shown-by="some_toggle"><td>domains</td></tr><tr data-ie-hidden-by="some_toggle"><td>refused</td></tr></table>`;
		boot( { success: false, data: { message: 'No' } } );
		const box = document.querySelector( 'input' );
		const row = document.querySelector( '[data-ie-shown-by]' );
		const other = document.querySelector( '[data-ie-hidden-by]' );
		expect( row.hidden ).toBe( true );
		expect( other.hidden ).toBe( false );

		box.checked = true;
		box.dispatchEvent( new Event( 'change' ) );
		expect( row.hidden ).toBe( false );
		expect( other.hidden ).toBe( true );

		await flush();
		expect( box.checked ).toBe( false );
		expect( row.hidden ).toBe( true );
		expect( other.hidden ).toBe( false );
	} );

	it( 'copies the shortcode and says so', async () => {
		window.ieAdmin.copied = 'Copied';
		document.body.innerHTML = '<input id="sc" value="[institutional_email]" readonly><button type="button" data-ie-copy="sc">Copy</button>';
		const writeText = vi.fn( () => Promise.resolve() );
		Object.defineProperty( window.navigator, 'clipboard', { value: { writeText }, configurable: true } );
		boot( {} );
		const button = document.querySelector( 'button' );
		button.click();
		expect( writeText ).toHaveBeenCalledWith( '[institutional_email]' );
		await flush();
		expect( button.textContent ).toBe( 'Copied' );
	} );

	it( 'posts every ticked form and shows the stored list', async () => {
		document.body.innerHTML = `<p>${ toggle( 'forms', 'a' ) }</p><p>${ toggle( 'forms', 'b', false ) }</p>`;
		boot( { success: true, data: { value: [ 'a', 'b' ] } } );
		const [ a, b ] = document.querySelectorAll( 'input' );
		a.checked = false;
		a.dispatchEvent( new Event( 'change' ) );

		expect( posted()[ 'value[]' ] ).toBeUndefined();
		await flush();
		// The server offers every form when none is ticked.
		expect( a.checked ).toBe( true );
		expect( b.checked ).toBe( true );
	} );

	it( 'reverts the toggle and shows the server message on refusal', async () => {
		document.body.innerHTML = `<p>${ toggle( 'some_toggle', '1', false ) }</p>`;
		boot( { success: false, data: { message: 'Type a default first.' } } );
		const box = document.querySelector( 'input' );
		box.checked = true;
		box.dispatchEvent( new Event( 'change' ) );
		await flush();

		expect( box.checked ).toBe( false );
		const badge = document.querySelector( '.ie-autosave-badge' );
		expect( badge.className ).toContain( '--error' );
		expect( badge.textContent ).toBe( 'Type a default first.' );
	} );

	it( 'falls back to a generic message when the answer is not JSON', async () => {
		document.body.innerHTML = `<p>${ toggle( 'some_toggle' ) }</p>`;
		window.fetch = vi.fn( () => Promise.resolve( { json: () => Promise.reject( new Error( 'html' ) ) } ) );
		new Function( script )();
		const box = document.querySelector( 'input' );
		box.checked = false;
		box.dispatchEvent( new Event( 'change' ) );
		await flush();

		expect( box.checked ).toBe( true );
		expect( document.querySelector( '.ie-autosave-badge' ).textContent ).toBe( 'Not saved' );
	} );

	it( 'binds nothing without a nonce', () => {
		document.body.innerHTML = `<p>${ toggle( 'some_toggle' ) }</p>`;
		window.ieAdmin = {};
		boot( { success: true } );
		document.querySelector( 'input' ).dispatchEvent( new Event( 'change' ) );
		expect( window.fetch ).not.toHaveBeenCalled();
	} );
} );
