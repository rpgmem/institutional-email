import { beforeAll, beforeEach, describe, expect, it, vi } from 'vitest';
import { loadConfig, loadGenerator } from './helpers.js';

let config;
let gen;

const TODAY = new Date( 2026, 9, 11 );

beforeAll( () => {
	config = loadConfig();
	gen = loadGenerator();
} );

describe( 'nameParts', () => {
	it( 'lowercases, drops accents and keeps only a to z', () => {
		expect( gen.nameParts( '  José  D\'Ávila-Conceição   Æsir ' ) ).toEqual( [ 'jose', 'davilaconceicao', 'aesir' ] );
	} );

	it( 'is empty for an empty or missing name', () => {
		expect( gen.nameParts( '' ) ).toEqual( [] );
		expect( gen.nameParts( undefined ) ).toEqual( [] );
		expect( gen.nameParts( '  -- \' ' ) ).toEqual( [] );
	} );
} );

describe( 'studentName', () => {
	it.each( [
		[ 'Maria Aparecida da Silva', 'mariaasilva' ],
		[ 'João dos Santos', 'joaosantos' ],
		[ 'Ana Paula Souza', 'anapsouza' ],
		[ 'Ana Luiz Souza', 'analsouza' ],
		[ 'Ana Rui de Souza', 'anasouza' ],
		[ 'PEDRO HENRIQUE  DE   OLIVEIRA', 'pedroholiveira' ],
		[ 'Lúcia Conceição', 'luciaconceicao' ],
	] )( '%s → %s', ( name, expected ) => {
		expect( gen.studentName( name ) ).toBe( expected );
	} );

	it( 'needs a first and a last name', () => {
		expect( gen.studentName( 'Maria' ) ).toBe( '' );
	} );

	it( 'takes the middle-name length it is given', () => {
		expect( gen.studentName( 'Ana Rui de Souza', 3 ) ).toBe( 'anarsouza' );
		expect( gen.studentName( 'Ana Rui de Souza', 2 ) ).toBe( 'anardsouza' );
		expect( gen.studentName( 'Ana Paula Souza', 6 ) ).toBe( 'anasouza' );
		expect( gen.studentName( 'Ana Paula Souza', 0 ) ).toBe( 'anapsouza' );
	} );
} );

describe( 'teacherName', () => {
	it( 'takes the first and the last name', () => {
		expect( gen.teacherName( 'Maria Aparecida da Silva' ) ).toBe( 'mariasilva' );
		expect( gen.teacherName( 'Maria' ) ).toBe( '' );
	} );
} );

describe( 'birthDate', () => {
	it( 'writes a real past date as DDMMYYYY', () => {
		expect( gen.birthDate( '2012-03-07', TODAY ) ).toBe( '07032012' );
		expect( gen.birthDate( '2026-10-11', TODAY ) ).toBe( '11102026' );
	} );

	it.each( [ '', 'x', '07/03/2012', '2013-02-29', '2012-13-01', '1899-12-31', '2026-10-12' ] )( 'refuses %j', ( value ) => {
		expect( gen.birthDate( value, TODAY ) ).toBe( '' );
	} );
} );

describe( 'CPF', () => {
	it( 'validates the check digits', () => {
		expect( gen.isCpf( '529.982.247-25' ) ).toBe( true );
		expect( gen.isCpf( '52998224725' ) ).toBe( true );
		expect( gen.isCpf( '529.982.247-24' ) ).toBe( false );
		expect( gen.isCpf( '111.111.111-11' ) ).toBe( false );
		expect( gen.isCpf( '1234' ) ).toBe( false );
	} );

	it( 'formats as far as the digits go', () => {
		expect( gen.formatCpf( '529' ) ).toBe( '529' );
		expect( gen.formatCpf( '5299' ) ).toBe( '529.9' );
		expect( gen.formatCpf( '5299822' ) ).toBe( '529.982.2' );
		expect( gen.formatCpf( '52998224725999' ) ).toBe( '529.982.247-25' );
	} );
} );

describe( 'addresses', () => {
	it( 'builds a student address', () => {
		expect( gen.studentEmail( { name: 'Maria Aparecida da Silva', birthDate: '2012-03-07' }, config, TODAY ) )
			.toEqual( { email: 'mariaasilva.07032012@edu.sme.prefeitura.sp.gov.br', errors: [] } );
	} );

	it( 'reports every student field in error', () => {
		expect( gen.studentEmail( { name: 'Maria', birthDate: '' }, config, TODAY ) )
			.toEqual( { email: '', errors: [ 'name', 'birthDate' ] } );
	} );

	it( 'builds a teacher address from the CPF or the RF', () => {
		expect( gen.teacherEmail( { network: 'partner', name: 'Maria da Silva', cpf: '529.982.247-25' }, config ).email )
			.toBe( 'mariasilva.52998224725@edu.sme.prefeitura.sp.gov.br' );
		expect( gen.teacherEmail( { network: 'direct', name: 'Maria da Silva', rf: '1234567' }, config ).email )
			.toBe( 'mariasilva.1234567@edu.sme.prefeitura.sp.gov.br' );
	} );

	it( 'reports the teacher fields in error', () => {
		expect( gen.teacherEmail( { network: 'direct', name: '', rf: '123' }, config ).errors ).toEqual( [ 'name', 'rf' ] );
		expect( gen.teacherEmail( { network: 'partner', name: 'A B', cpf: '123' }, config ).errors ).toEqual( [ 'cpf' ] );
	} );

	it( 'uses the configured middle-name length', () => {
		expect( gen.studentEmail( { name: 'Ana Rui Souza', birthDate: '2012-03-07' }, Object.assign( {}, config, { middleMin: 3 } ), TODAY ).email )
			.toBe( 'anarsouza.07032012@edu.sme.prefeitura.sp.gov.br' );
	} );

	it( 'uses the configured domain', () => {
		expect( gen.teacherEmail( { network: 'direct', name: 'A B', rf: '1234567' }, { domain: 'example.org' } ).email )
			.toBe( 'ab.1234567@example.org' );
	} );
} );

describe( 'configuration', () => {
	it( 'has a string for every error key the form can report', () => {
		[ 'name', 'birthDate', 'rf', 'cpf' ].forEach( ( key ) => {
			expect( config.strings[ 'error_' + key ] ).toBeTruthy();
		} );
	} );

	it( 'is translated by the catalogue', () => {
		expect( loadConfig( 'pt_BR' ).strings.submit ).not.toBe( config.strings.submit );
	} );
} );

function container( attrs = {} ) {
	const div = document.createElement( 'div' );
	Object.entries( attrs ).forEach( ( [ k, v ] ) => div.setAttribute( k, v ) );
	document.body.appendChild( div );
	return div;
}

function type( input, value ) {
	input.value = value;
	input.dispatchEvent( new Event( 'input', { bubbles: true } ) );
}

function submit( div ) {
	div.querySelector( 'form' ).dispatchEvent( new Event( 'submit', { cancelable: true } ) );
}

function flush() {
	return new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
}

beforeEach( () => {
	document.body.innerHTML = '';
	window.history.replaceState( null, '', '/' );
} );

describe( 'formOf', () => {
	it( 'reads data-ie-form and falls back to students', () => {
		expect( gen.formOf( container( { 'data-ie-form': 'teachers' } ) ) ).toBe( 'teachers' );
		expect( gen.formOf( container( { 'data-ie-form': 'nope' } ) ) ).toBe( 'students' );
		expect( gen.formOf( container() ) ).toBe( 'students' );
	} );

	it( 'lets the address choose in the standalone page', () => {
		window.history.replaceState( null, '', '/?form=teachers' );
		expect( gen.formOf( container( { 'data-ie-form': 'students', 'data-ie-form-param': 'form' } ) ) ).toBe( 'teachers' );
		expect( gen.formOf( container( { 'data-ie-form': 'students' } ) ) ).toBe( 'students' );
		window.history.replaceState( null, '', '/?form=bogus' );
		expect( gen.formOf( container( { 'data-ie-form': 'students', 'data-ie-form-param': 'form' } ) ) ).toBe( 'students' );
	} );
} );

describe( 'student form', () => {
	it( 'shows the errors, then the address and the password', () => {
		const div = container();
		const ui = gen.mount( div, config, 'students' );
		submit( div );

		expect( ui.inputs.name.getAttribute( 'aria-invalid' ) ).toBe( 'true' );
		expect( div.querySelector( '[data-ie-field="name"] .ie-field-error' ).textContent ).toBe( config.strings.error_name );
		expect( div.querySelector( '.ie-status' ).textContent ).toBe( config.strings.fixErrors );
		expect( div.querySelector( '.ie-result' ).hidden ).toBe( true );
		expect( document.activeElement ).toBe( ui.inputs.name );

		type( ui.inputs.name, 'Maria Aparecida da Silva' );
		type( ui.inputs.birthDate, '2012-03-07' );
		submit( div );

		expect( ui.email() ).toBe( 'mariaasilva.07032012@edu.sme.prefeitura.sp.gov.br' );
		expect( ui.inputs.name.getAttribute( 'aria-invalid' ) ).toBe( 'false' );
		expect( div.querySelector( '.ie-result' ).hidden ).toBe( false );
		expect( div.querySelectorAll( '.ie-value' )[ 1 ].textContent ).toBe( config.password );
		expect( div.querySelector( '.ie-status' ).textContent ).toBe( config.strings.ready );

		const link = div.querySelector( '.ie-next a' );
		expect( link.getAttribute( 'href' ) ).toBe( config.platformUrl );
		expect( link.getAttribute( 'rel' ) ).toBe( 'noopener noreferrer' );

		type( ui.inputs.name, 'Maria' );
		expect( div.querySelector( '.ie-result' ).hidden ).toBe( true );
		expect( ui.email() ).toBe( '' );
	} );

	it( 'leaves out the next step when no platform is configured', () => {
		const div = container();
		gen.mount( div, Object.assign( {}, config, { platformUrl: '' } ), 'students' );
		expect( div.querySelector( '.ie-next' ) ).toBeNull();
	} );

	it( 'prints the typed name as text, never as markup', () => {
		const div = container();
		const ui = gen.mount( div, Object.assign( {}, config, { domain: '<b>x</b>' } ), 'students' );
		type( ui.inputs.name, 'Ana Silva' );
		type( ui.inputs.birthDate, '2012-03-07' );
		submit( div );
		expect( div.querySelector( 'b' ) ).toBeNull();
	} );
} );

describe( 'teacher form', () => {
	it( 'switches between CPF and RF', () => {
		const div = container();
		const ui = gen.mount( div, config, 'teachers' );
		const rfField = div.querySelector( '[data-ie-field="rf"]' );
		const cpfField = div.querySelector( '[data-ie-field="cpf"]' );

		expect( rfField.hidden ).toBe( true );
		expect( cpfField.hidden ).toBe( false );

		type( ui.inputs.name, 'Maria da Silva' );
		type( ui.inputs.cpf, '52998224725' );
		expect( ui.inputs.cpf.value ).toBe( '529.982.247-25' );
		submit( div );
		expect( ui.email() ).toBe( 'mariasilva.52998224725@edu.sme.prefeitura.sp.gov.br' );

		const direct = div.querySelector( 'input[value="direct"]' );
		direct.checked = true;
		direct.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		expect( rfField.hidden ).toBe( false );
		expect( cpfField.hidden ).toBe( true );
		expect( ui.email() ).toBe( '' );

		type( ui.inputs.rf, '12a34567890' );
		expect( ui.inputs.rf.value ).toBe( '1234567' );
		submit( div );
		expect( ui.email() ).toBe( 'mariasilva.1234567@edu.sme.prefeitura.sp.gov.br' );
	} );

	it( 'marks a wrong CPF', () => {
		const div = container();
		const ui = gen.mount( div, config, 'teachers' );
		type( ui.inputs.name, 'Maria da Silva' );
		type( ui.inputs.cpf, '529.982.247-24' );
		expect( ui.generate() ).toBe( false );
		expect( ui.inputs.cpf.getAttribute( 'aria-invalid' ) ).toBe( 'true' );
		expect( div.querySelector( '[data-ie-field="cpf"] .ie-field-error' ).textContent ).toBe( config.strings.error_cpf );
	} );
} );

describe( 'copy buttons', () => {
	function ready() {
		const div = container();
		const ui = gen.mount( div, config, 'teachers' );
		type( ui.inputs.name, 'Maria da Silva' );
		type( ui.inputs.cpf, '52998224725' );
		submit( div );
		return div;
	}

	it( 'copies through the Clipboard API', async () => {
		const writeText = vi.fn( () => Promise.resolve() );
		Object.defineProperty( navigator, 'clipboard', { value: { writeText }, configurable: true } );
		const div = ready();
		const [ copyEmail, copyPassword ] = div.querySelectorAll( '.ie-copy' );

		copyEmail.click();
		await flush();
		expect( writeText ).toHaveBeenCalledWith( 'mariasilva.52998224725@edu.sme.prefeitura.sp.gov.br' );
		expect( div.querySelector( '.ie-status' ).textContent ).toBe( config.strings.emailCopied );

		copyPassword.click();
		await flush();
		expect( writeText ).toHaveBeenLastCalledWith( config.password );
		expect( div.querySelector( '.ie-status' ).textContent ).toBe( config.strings.passwordCopied );
		delete navigator.clipboard;
	} );

	it( 'falls back to the selection, and says when copying fails', async () => {
		Object.defineProperty( navigator, 'clipboard', { value: undefined, configurable: true } );
		document.execCommand = vi.fn( () => true );
		const div = ready();
		div.querySelector( '.ie-copy' ).click();
		await flush();
		expect( document.execCommand ).toHaveBeenCalledWith( 'copy' );
		expect( div.querySelector( '.ie-status' ).textContent ).toBe( config.strings.emailCopied );
		expect( document.querySelector( 'textarea' ) ).toBeNull();

		document.execCommand = vi.fn( () => {
			throw new Error( 'no' );
		} );
		div.querySelector( '.ie-copy' ).click();
		await flush();
		expect( div.querySelector( '.ie-status' ).textContent ).toBe( config.strings.copyFailed );
		delete navigator.clipboard;
	} );
} );

describe( 'boot', () => {
	it( 'mounts every container once, with its own configuration', () => {
		const div = container( { 'data-ie-form': 'teachers', 'data-ie-config': JSON.stringify( config ) } );
		gen.boot( document );
		gen.boot( document );
		expect( div.querySelectorAll( 'form' ) ).toHaveLength( 1 );
		expect( div.querySelector( '.ie-network' ) ).not.toBeNull();
	} );
} );
