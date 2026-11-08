// The address each form builds for a set of real-shaped inputs, frozen (#103).
//
// The rule tests check one step at a time: a name part, a date, a check
// digit. The address a person signs in with is all of them put together, and
// a change to any step changes it silently for everyone who already has one.
// This table freezes the whole output for names that have bitten before or
// could: particles, hyphens, apostrophes, accents, ligatures, extra spaces,
// single letters, a pasted invisible character. Any change to an address is
// a snapshot diff someone has to read and accept (`npx vitest run -u`).
import { beforeAll, describe, expect, it } from 'vitest';
import { loadConfig, loadGenerator } from './helpers.js';

const TODAY = new Date( 2026, 9, 11 );

const NAMES = [
	'Maria Aparecida da Silva',
	'João dos Santos',
	'Ana Paula Souza',
	'Ana Rui de Souza',
	'PEDRO HENRIQUE  DE   OLIVEIRA',
	'Lúcia Conceição',
	'Ana-Clara Pereira-Lima',
	"Joana D'Ávila",
	'José Maria de Jesus Neto',
	'Bjørn Ærøe Strauß',
	'Wu Li',
	'Ana E Souza',
	'Sil﻿via Mendes',
	'  Carlos   Eduardo  ',
	'Maria',
];

let config;
let gen;

beforeAll( () => {
	config = loadConfig();
	gen = loadGenerator();
} );

/**
 * One line per case: what was typed, then the address or the fields in error.
 *
 * @param {Array} rows [ label, result ] pairs.
 * @return {string} The table.
 */
function table( rows ) {
	return rows.map( ( [ label, r ] ) => label.replace( /\uFEFF/g, '<U+FEFF>' ) + ' → ' + ( r.email || 'errors: ' + r.errors.join( ',' ) ) ).join( '\n' );
}

describe( 'addresses for real-shaped input', () => {
	it( 'students (city network)', () => {
		const rows = NAMES.map( ( name ) => [ name, gen.studentEmail( { name, birthDate: '2012-03-07' }, config, TODAY ) ] );
		expect( table( rows ) ).toMatchSnapshot();
	} );

	it( 'teachers, direct network (RF)', () => {
		const rows = NAMES.map( ( name ) => [ name, gen.teacherEmail( { network: 'direct', name, rf: '518.178-0' }, config ) ] );
		expect( table( rows ) ).toMatchSnapshot();
	} );

	it( 'teachers, partner network and interns (CPF)', () => {
		const rows = [ 'partner', 'intern' ].flatMap( ( network ) => NAMES.slice( 0, 3 ).map( ( name ) => [
			network + ' / ' + name,
			gen.teacherEmail( { network, name, cpf: '123.456.789-09' }, config ),
		] ) );
		expect( table( rows ) ).toMatchSnapshot();
	} );

	it( 'students (state network)', () => {
		const rows = [
			[ 'RA typed whole', { ra: '000123456789', raDigit: '5', birthDate: '2012-03-07' } ],
			[ 'RA without leading zeros', { ra: '123456789', raDigit: 'X', birthDate: '2012-03-07' } ],
			[ 'RA with punctuation', { ra: '123.456.789', raDigit: 'x', birthDate: '2012-03-07' } ],
			[ 'RA too long', { ra: '1234567890123', raDigit: '5', birthDate: '2012-03-07' } ],
			[ 'missing check digit', { ra: '123456789', raDigit: '', birthDate: '2012-03-07' } ],
		].map( ( [ label, values ] ) => {
			const r = gen.stateStudentEmail( values, config, TODAY );
			return [ label, r.email ? { ...r, email: r.email + ' / ' + r.password } : r ];
		} );
		expect( table( rows ) ).toMatchSnapshot();
	} );
} );
