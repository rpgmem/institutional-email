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

	it( 'formats an RF as far as the digits go, as Free Form Certificate does', () => {
		expect( gen.formatRf( '518' ) ).toBe( '518' );
		expect( gen.formatRf( '5181' ) ).toBe( '518.1' );
		expect( gen.formatRf( '518178' ) ).toBe( '518.178' );
		expect( gen.formatRf( '5181780999' ) ).toBe( '518.178-0' );
	} );

	it( 'formats as far as the digits go', () => {
		expect( gen.formatCpf( '529' ) ).toBe( '529' );
		expect( gen.formatCpf( '5299' ) ).toBe( '529.9' );
		expect( gen.formatCpf( '5299822' ) ).toBe( '529.982.2' );
		expect( gen.formatCpf( '52998224725999' ) ).toBe( '529.982.247-25' );
	} );
} );

describe( 'RF check digit', () => {
	it( 'is the rule Free Form Certificate uses', () => {
		// Free Form Certificate's example RF, 518.178-0:
		// 5*7 + 1*6 + 8*5 + 1*4 + 7*3 + 8*2 = 122; 122 mod 11 = 1; (11 - 1) mod 10 = 0.
		expect( gen.rfCheckDigit( '123456' ) ).toBe( null );
		expect( gen.rfCheckDigit( '5181780' ) ).toBe( 0 );
		expect( gen.isRf( '518.178-0' ) ).toBe( true );
		expect( gen.isRf( '1234567' ) ).toBe( false );
		expect( gen.isRf( '12345' ) ).toBe( false );
		// 1*7 + 6*2 = 19; 19 mod 11 = 8; (11 - 8) mod 10 = 3.
		expect( gen.rfCheckDigit( '1000060' ) ).toBe( 3 );
	} );

	it( 'reports a wrong check digit apart, and can be turned off', () => {
		const wrong = gen.teacherEmail( { network: 'direct', name: 'A B', rf: '1234567' }, config );
		expect( wrong.errors ).toEqual( [ 'rf' ] );
		expect( wrong.reasons ).toEqual( { rf: 'checkDigit' } );
		expect( gen.teacherEmail( { network: 'direct', name: 'A B', rf: '123' }, config ).reasons ).toEqual( {} );
		expect( gen.teacherEmail( { network: 'direct', name: 'A B', rf: '1234567' }, Object.assign( {}, config, { checkRfDigit: false } ) ).email )
			.toBe( 'ab.1234567@edu.sme.prefeitura.sp.gov.br' );
	} );
} );

describe( 'state students', () => {
	it( 'build the address from the RA padded to 12 digits and its check digit', () => {
		expect( gen.stateStudentEmail( { ra: '123456789', raDigit: 'X', birthDate: '2012-03-07' }, config, TODAY ) )
			.toEqual( { email: '000123456789xsp@al.educacao.sp.gov.br', password: '07032012', errors: [] } );
		expect( gen.stateStudentEmail( { ra: '000123456789', raDigit: '5', birthDate: '2012-03-07' }, config, TODAY ).email )
			.toBe( '0001234567895sp@al.educacao.sp.gov.br' );
	} );

	it( 'report every field in error', () => {
		expect( gen.stateStudentEmail( { ra: '', raDigit: 'Y', birthDate: '' }, config, TODAY ).errors ).toEqual( [ 'ra', 'raDigit', 'birthDate' ] );
		expect( gen.stateStudentEmail( { ra: '1234567890123', raDigit: '1', birthDate: '2012-03-07' }, config, TODAY ).errors ).toEqual( [ 'ra' ] );
		expect( gen.stateStudentEmail( { ra: '1', raDigit: '12', birthDate: '2012-03-07' }, config, TODAY ).errors ).toEqual( [ 'raDigit' ] );
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
		expect( gen.teacherEmail( { network: 'direct', name: 'Maria da Silva', rf: '5181780' }, config ).email )
			.toBe( 'mariasilva.5181780@edu.sme.prefeitura.sp.gov.br' );
	} );

	it( 'puts an "e" before an intern\'s CPF', () => {
		expect( gen.teacherEmail( { network: 'intern', name: 'Maria da Silva', cpf: '529.982.247-25' }, config ).email )
			.toBe( 'mariasilva.e52998224725@edu.sme.prefeitura.sp.gov.br' );
		expect( gen.teacherEmail( { network: 'intern', name: 'A B', cpf: '123' }, config ).errors ).toEqual( [ 'cpf' ] );
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
		expect( gen.teacherEmail( { network: 'direct', name: 'A B', rf: '5181780' }, { domain: 'example.org' } ).email )
			.toBe( 'ab.5181780@example.org' );
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

describe( 'networkOf', () => {
	it( 'reads data-ie-network, then the configuration, and falls back to the city', () => {
		expect( gen.networkOf( container( { 'data-ie-network': 'state' } ) ) ).toBe( 'state' );
		expect( gen.networkOf( container(), { network: 'state' } ) ).toBe( 'state' );
		expect( gen.networkOf( container( { 'data-ie-network': 'nope' } ) ) ).toBe( 'city' );
		expect( gen.networkOf( container() ) ).toBe( 'city' );
	} );

	it( 'lets the address choose in the standalone page', () => {
		window.history.replaceState( null, '', '/?network=state' );
		expect( gen.networkOf( container( { 'data-ie-network': 'city', 'data-ie-network-param': 'network' } ) ) ).toBe( 'state' );
		expect( gen.networkOf( container( { 'data-ie-network': 'city' } ) ) ).toBe( 'city' );
	} );
} );

describe( 'state student form', () => {
	it( 'asks for the RA, its check digit and the date of birth, and shows the password', () => {
		const div = container();
		const ui = gen.mount( div, config, 'students', 'state' );
		expect( ui.network ).toBe( 'state' );
		expect( Object.keys( ui.inputs ) ).toEqual( [ 'ra', 'raDigit', 'birthDate' ] );
		expect( div.querySelector( '.ie-lookup a' ).getAttribute( 'href' ) ).toBe( config.state.raUrl );

		submit( div );
		expect( div.querySelector( '[data-ie-field="raDigit"] .ie-field-error' ).textContent ).toBe( config.strings.error_raDigit );

		type( ui.inputs.ra, '12a3456789012345' );
		expect( ui.inputs.ra.value ).toBe( '123456789012' );
		type( ui.inputs.ra, '123456789' );
		type( ui.inputs.raDigit, 'x9' );
		expect( ui.inputs.raDigit.value ).toBe( 'X' );
		type( ui.inputs.birthDate, '2012-03-07' );
		submit( div );

		expect( ui.email() ).toBe( '000123456789xsp@al.educacao.sp.gov.br' );
		expect( div.querySelectorAll( '.ie-value' )[ 1 ].textContent ).toBe( '07032012' );
		expect( div.querySelectorAll( '.ie-result-label' )[ 1 ].textContent ).toBe( config.strings.statePasswordResult );
	} );
} );

describe( 'state teachers', () => {
	it( 'see an explanation and the SED link, no form', () => {
		const div = container( { 'data-ie-network': 'state' } );
		const ui = gen.mount( div, config, 'teachers' );
		expect( ui.network ).toBe( 'state' );
		expect( div.querySelector( 'form' ) ).toBeNull();
		expect( div.textContent ).toContain( config.strings.stateTeacherIntro );
		const link = div.querySelector( '.ie-info a' );
		expect( link.getAttribute( 'href' ) ).toBe( config.state.teacherUrl );
		expect( link.getAttribute( 'rel' ) ).toBe( 'noopener noreferrer' );
		expect( ui.generate() ).toBe( false );
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
		expect( div.querySelectorAll( '.ie-value wbr' ) ).toHaveLength( 7 );
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
		expect( ui.inputs.rf.value ).toBe( '123.456-7' );
		submit( div );
		expect( ui.email() ).toBe( '' );
		expect( div.querySelector( '[data-ie-field="rf"] .ie-field-error' ).textContent ).toBe( config.strings.error_rfCheck );

		type( ui.inputs.rf, '518.178-0' );
		submit( div );
		expect( ui.email() ).toBe( 'mariasilva.5181780@edu.sme.prefeitura.sp.gov.br' );
	} );

	it( 'offers interns, with the CPF field', () => {
		const div = container();
		const ui = gen.mount( div, config, 'teachers' );
		const intern = div.querySelector( 'input[value="intern"]' );
		intern.checked = true;
		intern.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		expect( div.querySelector( '[data-ie-field="rf"]' ).hidden ).toBe( true );
		expect( div.querySelector( '[data-ie-field="cpf"]' ).hidden ).toBe( false );

		expect( div.querySelector( '[data-ie-field="name"] label' ).textContent ).toBe( config.strings.internName );
		type( ui.inputs.name, 'Maria da Silva' );
		type( ui.inputs.cpf, '52998224725' );
		submit( div );
		expect( ui.email() ).toBe( 'mariasilva.e52998224725@edu.sme.prefeitura.sp.gov.br' );
		expect( div.querySelector( '.ie-result-label' ).textContent ).toBe( config.strings.internResult );

		const partner = div.querySelector( 'input[value="partner"]' );
		partner.checked = true;
		partner.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		expect( div.querySelector( '[data-ie-field="name"] label' ).textContent ).toBe( config.strings.teacherName );
	} );

	it( 'offers only the options the site keeps', () => {
		const div = container();
		gen.mount( div, Object.assign( {}, config, { teacherNetworks: [ 'partner', 'intern' ] } ), 'teachers' );
		expect( div.querySelector( 'input[value="direct"]' ) ).toBeNull();
		expect( div.querySelector( '.ie-network' ).hidden ).toBe( false );
		expect( div.querySelector( 'input[value="partner"]' ).checked ).toBe( true );
	} );

	it( 'asks for the one option left without a choice', () => {
		const div = container();
		const ui = gen.mount( div, Object.assign( {}, config, { teacherNetworks: [ 'direct' ] } ), 'teachers' );
		expect( div.querySelector( '.ie-network' ).hidden ).toBe( true );
		expect( div.querySelector( '[data-ie-field="rf"]' ).hidden ).toBe( false );
		expect( div.querySelector( '[data-ie-field="cpf"]' ).hidden ).toBe( true );
		type( ui.inputs.name, 'Maria da Silva' );
		type( ui.inputs.rf, '5181780' );
		submit( div );
		expect( ui.email() ).toBe( 'mariasilva.5181780@edu.sme.prefeitura.sp.gov.br' );
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
