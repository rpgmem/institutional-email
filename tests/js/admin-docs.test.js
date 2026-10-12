import { beforeEach, describe, expect, it } from 'vitest';
import { runScript } from './helpers.js';

function card( id, title, text ) {
	return `<div class="card ie-doc"><h2 id="ie-doc-${ id }"><span>${ title }</span></h2><p>${ text }</p></div>`;
}

beforeEach( () => {
	window.ieAdmin = {};
	document.body.innerHTML = `<div class="ie-doc-layout">
		<nav class="card ie-doc-contents"><p class="ie-doc-search"><input type="search" id="ie-doc-search"></p><ul>
			<li><a href="#ie-doc-general">General</a></li>
			<li><a href="#ie-doc-logo">Logo</a></li>
			<li><a href="#ie-doc-gone">Gone</a></li>
		</ul><p class="ie-doc-search-empty" hidden>None</p></nav>
		<div class="ie-doc-topics">${ card( 'general', 'General', 'Which models are offered.' ) }${ card( 'logo', 'Logo', 'Alt text for screen readers.' ) }</div>
	</div>`;
	runScript( 'assets/js/admin-settings.js' );
} );

function search( text ) {
	const input = document.getElementById( 'ie-doc-search' );
	input.value = text;
	input.dispatchEvent( new Event( 'input' ) );
}

const rows = () => [ ...document.querySelectorAll( '.ie-doc-contents li' ) ].map( ( li ) => li.hidden );
const cards = () => [ ...document.querySelectorAll( '.ie-doc' ) ].map( ( c ) => c.hidden );

describe( 'documentation search', () => {
	it( 'keeps a topic whose card text matches, row and card alike', () => {
		search( 'SCREEN readers' );
		expect( cards() ).toEqual( [ true, false ] );
		expect( rows() ).toEqual( [ true, false, true ] );
		expect( document.querySelector( '.ie-doc-search-empty' ).hidden ).toBe( true );
	} );

	it( 'matches a row with no card by its own title', () => {
		search( 'gone' );
		expect( rows() ).toEqual( [ true, true, false ] );
	} );

	it( 'says so when nothing matches, and an empty search shows everything again', () => {
		search( 'nothing like this' );
		expect( rows() ).toEqual( [ true, true, true ] );
		expect( document.querySelector( '.ie-doc-search-empty' ).hidden ).toBe( false );

		search( '  ' );
		expect( rows() ).toEqual( [ false, false, false ] );
		expect( cards() ).toEqual( [ false, false ] );
		expect( document.querySelector( '.ie-doc-search-empty' ).hidden ).toBe( true );
	} );
} );
