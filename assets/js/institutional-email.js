/**
 * Institutional Email: works out the institutional address of a São Paulo
 * student or teacher. City schools (RME/SP): a student from name + date of
 * birth, a teacher from name + RF or CPF. State schools (SEDUC-SP): a student
 * from the RA + check digit, with the date of birth as the password; a
 * teacher creates the address on the SED, so the form only points there.
 *
 * Runs in the WordPress plugin (the configuration comes from the shortcode's
 * data-ie-config attribute) and in the standalone HTML (the same attribute,
 * with the default configuration embedded). Nothing typed leaves the
 * browser: the address is computed here and never sent anywhere.
 *
 * The rules are plain functions, exported for the Vitest suite.
 */
( function ( root ) {
	'use strict';

	var FORMS = [ 'students', 'teachers' ];

	var NETWORKS = [ 'city', 'state' ];

	/**
	 * Digits in a state student's RA, as the SED writes it.
	 */
	var RA_LENGTH = 12;

	/**
	 * The options of the city teachers' form, in the order they are shown.
	 */
	var TEACHER_NETWORKS = [ 'partner', 'direct', 'intern' ];

	/**
	 * Letters NFD does not decompose into a base letter plus a mark.
	 */
	var LIGATURES = { 'æ': 'ae', 'œ': 'oe', 'ø': 'o', 'ß': 's', 'ł': 'l', 'đ': 'd', 'ð': 'd', 'þ': 'th', 'ı': 'i' };

	/**
	 * The name as the address spells it: lower case, no accents, and only the
	 * letters a to z in each part.
	 *
	 * @param {string} name Full name as typed.
	 * @return {string[]} The parts, in order, none empty.
	 */
	function nameParts( name ) {
		var plain = String( name || '' )
			.toLowerCase()
			.replace( /[æœøßłđðþı]/g, function ( c ) {
				return LIGATURES[ c ];
			} )
			.normalize( 'NFD' )
			.replace( /[̀-ͯ]/g, '' );
		return plain.split( /\s+/ ).map( function ( part ) {
			return part.replace( /[^a-z]/g, '' );
		} ).filter( Boolean );
	}

	/**
	 * A student's name part of the address: the first name, the initial of
	 * every middle name of at least `min` letters (by default 4, so "da",
	 * "dos", "de" drop out), and the last name.
	 *
	 * @param {string} name  Full name.
	 * @param {number} [min] Letters a middle name needs to count.
	 * @return {string} e.g. "mariaasilva" for "Maria Aparecida da Silva".
	 */
	function studentName( name, min ) {
		var parts = nameParts( name );
		var least = min > 0 ? min : 4;
		if ( parts.length < 2 ) {
			return '';
		}
		var middle = parts.slice( 1, -1 ).filter( function ( part ) {
			return part.length >= least;
		} ).map( function ( part ) {
			return part.charAt( 0 );
		} );
		return parts[ 0 ] + middle.join( '' ) + parts[ parts.length - 1 ];
	}

	/**
	 * A teacher's name part of the address: the first and the last name.
	 *
	 * @param {string} name Full name.
	 * @return {string} e.g. "mariasilva".
	 */
	function teacherName( name ) {
		var parts = nameParts( name );
		return parts.length < 2 ? '' : parts[ 0 ] + parts[ parts.length - 1 ];
	}

	/**
	 * A date of birth as the address writes it.
	 *
	 * @param {string} iso Date from an <input type="date">, YYYY-MM-DD.
	 * @param {Date}   [today] Reference day, for the tests.
	 * @return {string} DDMMYYYY, or '' when it is not a real past date.
	 */
	function birthDate( iso, today ) {
		var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec( String( iso || '' ) );
		if ( ! m ) {
			return '';
		}
		var y = +m[ 1 ];
		var mo = +m[ 2 ];
		var d = +m[ 3 ];
		var date = new Date( Date.UTC( y, mo - 1, d ) );
		var now = today || new Date();
		if ( date.getUTCFullYear() !== y || date.getUTCMonth() !== mo - 1 || date.getUTCDate() !== d ) {
			return '';
		}
		if ( y < 1900 || date.getTime() > Date.UTC( now.getFullYear(), now.getMonth(), now.getDate() ) ) {
			return '';
		}
		return m[ 3 ] + m[ 2 ] + m[ 1 ];
	}

	/**
	 * Digits only.
	 *
	 * @param {string} value Typed value.
	 * @return {string} Its digits.
	 */
	function digits( value ) {
		return String( value || '' ).replace( /\D/g, '' );
	}

	/**
	 * Whether a CPF's two check digits are right.
	 *
	 * @param {string} cpf CPF, with or without punctuation.
	 * @return {boolean} Valid.
	 */
	function isCpf( cpf ) {
		var n = digits( cpf );
		if ( 11 !== n.length || /^(\d)\1{10}$/.test( n ) ) {
			return false;
		}
		for ( var t = 9; t < 11; t++ ) {
			var sum = 0;
			for ( var i = 0; i < t; i++ ) {
				sum += +n.charAt( i ) * ( t + 1 - i );
			}
			var check = ( sum * 10 ) % 11 % 10;
			if ( check !== +n.charAt( t ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * The check digit the first six digits of an RF imply: the seventh digit
	 * of a valid RF. Weights 7 to 2, sum mod 11, then (11 - r) mod 10.
	 *
	 * The rule is Free Form Certificate's (DocumentFormatter::rf_check_digit),
	 * derived from 3,036 RFs and confirmed at 100% on 96 of independent
	 * provenance. Its one blind spot: r = 0 and r = 10 both give 1.
	 *
	 * @param {string} rf RF, with or without punctuation.
	 * @return {number|null} The expected seventh digit, or null when it is not seven digits.
	 */
	function rfCheckDigit( rf ) {
		var n = digits( rf );
		if ( 7 !== n.length ) {
			return null;
		}
		var sum = 0;
		[ 7, 6, 5, 4, 3, 2 ].forEach( function ( weight, i ) {
			sum += +n.charAt( i ) * weight;
		} );
		return ( 11 - ( sum % 11 ) ) % 10;
	}

	/**
	 * Whether an RF's seventh digit is the one its first six imply.
	 *
	 * @param {string} rf RF, with or without punctuation.
	 * @return {boolean} Valid.
	 */
	function isRf( rf ) {
		var expected = rfCheckDigit( rf );
		return null !== expected && expected === +digits( rf ).charAt( 6 );
	}

	/**
	 * A CPF as it is usually written, 000.000.000-00, as far as the digits go.
	 * The mask is Free Form Certificate's (ffc-field-behaviours.js), so a
	 * person sees the same format in both plugins.
	 *
	 * @param {string} value Typed value.
	 * @return {string} Formatted.
	 */
	function formatCpf( value ) {
		var v = digits( value ).slice( 0, 11 );
		if ( v.length > 9 ) {
			return v.replace( /(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4' );
		}
		if ( v.length > 6 ) {
			return v.replace( /(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3' );
		}
		if ( v.length > 3 ) {
			return v.replace( /(\d{3})(\d{1,3})/, '$1.$2' );
		}
		return v;
	}

	/**
	 * An RF as it is usually written, 000.000-0, as far as the digits go.
	 * Free Form Certificate's mask, like formatCpf().
	 *
	 * @param {string} value Typed value.
	 * @return {string} Formatted.
	 */
	function formatRf( value ) {
		var v = digits( value ).slice( 0, 7 );
		if ( v.length > 6 ) {
			return v.replace( /(\d{3})(\d{3})(\d{1})/, '$1.$2-$3' );
		}
		if ( v.length > 3 ) {
			return v.replace( /(\d{3})(\d{1,3})/, '$1.$2' );
		}
		return v;
	}

	/**
	 * A student's address, or the problems that stop it.
	 *
	 * @param {Object} values { name, birthDate }.
	 * @param {Object} config Configuration (domain, middleMin).
	 * @param {Date}   [today] Reference day, for the tests.
	 * @return {{email: string, errors: string[]}} The address, or the keys of the fields in error.
	 */
	function studentEmail( values, config, today ) {
		var errors = [];
		var name = studentName( values.name, config.middleMin );
		var date = birthDate( values.birthDate, today );
		if ( ! name ) {
			errors.push( 'name' );
		}
		if ( ! date ) {
			errors.push( 'birthDate' );
		}
		return { email: errors.length ? '' : name + '.' + date + '@' + config.domain, errors: errors };
	}

	/**
	 * A teacher's address, or the problems that stop it.
	 *
	 * An RF whose seventh digit does not match is refused unless the
	 * configuration turns the check off (checkRfDigit: false); the reason is
	 * reported apart, so the form can say "check the number" rather than
	 * "type seven digits".
	 *
	 * An intern (network 'intern') has the partner network's address with an
	 * "e" before the CPF digits: mariasilva.e12345678909.
	 *
	 * @param {Object} values { network: 'direct'|'partner'|'intern', name, rf, cpf }.
	 * @param {Object} config Configuration (domain, checkRfDigit).
	 * @return {{email: string, errors: string[], reasons: Object}} The address, or the keys of the fields in error and why.
	 */
	function teacherEmail( values, config ) {
		var errors = [];
		var reasons = {};
		var name = teacherName( values.name );
		var direct = 'direct' === values.network;
		var id = direct ? digits( values.rf ) : digits( values.cpf );
		if ( ! name ) {
			errors.push( 'name' );
		}
		if ( direct && 7 !== id.length ) {
			errors.push( 'rf' );
		} else if ( direct && false !== config.checkRfDigit && ! isRf( id ) ) {
			errors.push( 'rf' );
			reasons.rf = 'checkDigit';
		}
		if ( ! direct && ! isCpf( id ) ) {
			errors.push( 'cpf' );
		}
		var prefix = 'intern' === values.network ? 'e' : '';
		return { email: errors.length ? '' : name + '.' + prefix + id + '@' + config.domain, errors: errors, reasons: reasons };
	}

	/**
	 * A state student's address and password, or the problems that stop them.
	 *
	 * The RA is padded with zeros to its 12 digits, as the SED shows it, so a
	 * student who types it without the leading zeros still gets the address.
	 *
	 * @param {Object} values { ra, raDigit, birthDate }.
	 * @param {Object} config Configuration (state.domain, state.suffix).
	 * @param {Date}   [today] Reference day, for the tests.
	 * @return {{email: string, password: string, errors: string[]}} The address and password, or the keys of the fields in error.
	 */
	function stateStudentEmail( values, config, today ) {
		var errors = [];
		var ra = digits( values.ra );
		var check = String( values.raDigit || '' ).trim().toLowerCase();
		var date = birthDate( values.birthDate, today );
		if ( ! ra || ra.length > RA_LENGTH ) {
			errors.push( 'ra' );
		}
		if ( ! /^[0-9x]$/.test( check ) ) {
			errors.push( 'raDigit' );
		}
		if ( ! date ) {
			errors.push( 'birthDate' );
		}
		if ( errors.length ) {
			return { email: '', password: '', errors: errors };
		}
		while ( ra.length < RA_LENGTH ) {
			ra = '0' + ra;
		}
		return {
			email: ra + check + config.state.suffix + '@' + config.state.domain,
			password: date,
			errors: errors,
		};
	}

	/**
	 * Build an element.
	 *
	 * @param {Document} doc      Document.
	 * @param {string}   tag      Tag name.
	 * @param {Object}   [attrs]  Attributes; `text` sets the text content.
	 * @param {Node[]}   [children] Children.
	 * @return {HTMLElement} The element.
	 */
	function el( doc, tag, attrs, children ) {
		var node = doc.createElement( tag );
		Object.keys( attrs || {} ).forEach( function ( key ) {
			var value = attrs[ key ];
			if ( 'text' === key ) {
				node.textContent = value;
			} else if ( true === value ) {
				node.setAttribute( key, '' );
			} else if ( false !== value && null != value ) {
				node.setAttribute( key, String( value ) );
			}
		} );
		( children || [] ).forEach( function ( child ) {
			node.appendChild( child );
		} );
		return node;
	}

	/**
	 * Copy text to the clipboard, with the old selection fallback.
	 *
	 * @param {Document} doc  Document.
	 * @param {string}   text Text.
	 * @return {Promise<void>} Settles when copied.
	 */
	function copyText( doc, text ) {
		var nav = doc.defaultView && doc.defaultView.navigator;
		if ( nav && nav.clipboard && nav.clipboard.writeText ) {
			return nav.clipboard.writeText( text );
		}
		return new Promise( function ( resolve, reject ) {
			var area = el( doc, 'textarea', { 'aria-hidden': 'true', readonly: true } );
			area.value = text;
			area.style.position = 'fixed';
			area.style.opacity = '0';
			doc.body.appendChild( area );
			area.select();
			var ok = false;
			try {
				ok = doc.execCommand( 'copy' );
			} catch {
				ok = false;
			}
			area.remove();
			if ( ok ) {
				resolve();
			} else {
				reject( new Error( 'copy' ) );
			}
		} );
	}

	/**
	 * Which form a container shows: its data-ie-form, or in the standalone
	 * page the `?form=` of its address.
	 *
	 * @param {HTMLElement} container Container.
	 * @return {string} 'students' or 'teachers'.
	 */
	function formOf( container ) {
		var form = container.getAttribute( 'data-ie-form' ) || '';
		var param = container.getAttribute( 'data-ie-form-param' );
		var win = container.ownerDocument.defaultView;
		if ( param && win && win.location ) {
			var asked = new win.URLSearchParams( win.location.search ).get( param );
			if ( -1 !== FORMS.indexOf( asked ) ) {
				form = asked;
			}
		}
		return -1 !== FORMS.indexOf( form ) ? form : 'students';
	}

	/**
	 * Which school network a container serves: its data-ie-network, or in the
	 * standalone page the `?network=` of its address, else the configured one.
	 *
	 * @param {HTMLElement} container Container.
	 * @param {Object}      [config]  Configuration (network).
	 * @return {string} 'city' or 'state'.
	 */
	function networkOf( container, config ) {
		var network = container.getAttribute( 'data-ie-network' ) || ( config && config.network ) || '';
		var param = container.getAttribute( 'data-ie-network-param' );
		var win = container.ownerDocument.defaultView;
		if ( param && win && win.location ) {
			var asked = new win.URLSearchParams( win.location.search ).get( param );
			if ( -1 !== NETWORKS.indexOf( asked ) ) {
				network = asked;
			}
		}
		return -1 !== NETWORKS.indexOf( network ) ? network : 'city';
	}

	/**
	 * Draw the state teachers' panel: there is no rule to apply, so it says
	 * where the address is created and links there.
	 *
	 * @param {HTMLElement} container Container.
	 * @param {Object}      config    Configuration.
	 * @return {Object} Handles for the tests.
	 */
	function mountStateTeachers( container, config ) {
		var doc = container.ownerDocument;
		var s = config.strings;
		container.textContent = '';
		container.appendChild( el( doc, 'div', { class: 'ie-info' }, [
			el( doc, 'p', { text: s.stateTeacherIntro } ),
			el( doc, 'p', {}, [
				el( doc, 'a', { class: 'ie-button ie-link-button', href: config.state.teacherUrl, target: '_blank', rel: 'noopener noreferrer', text: s.stateTeacherLink } ),
			] ),
		] ) );
		return { form: 'teachers', network: 'state', generate: function () {
			return false;
		}, inputs: {}, email: function () {
			return '';
		} };
	}

	/**
	 * Draw a form inside a container and wire it.
	 *
	 * @param {HTMLElement} container Container.
	 * @param {Object}      config    Configuration.
	 * @param {string}      [form]    'students' or 'teachers'; read from the container when absent.
	 * @param {string}      [system]  'city' or 'state'; read from the container and the configuration when absent.
	 * @return {Object} Handles for the tests.
	 */
	function mount( container, config, form, system ) {
		var doc = container.ownerDocument;
		var s = config.strings;
		var kind = form || formOf( container );
		var state = 'state' === ( system || networkOf( container, config ) );
		var uid = 'ie-' + Math.random().toString( 36 ).slice( 2, 8 );
		var inputs = {};

		if ( state && 'teachers' === kind ) {
			return mountStateTeachers( container, config );
		}

		function field( key, label, attrs, hint ) {
			var id = uid + '-' + key;
			var input = el( doc, 'input', Object.assign( { id: id, name: key, class: 'ie-input' }, attrs ) );
			var error = el( doc, 'span', { class: 'ie-field-error', id: id + '-error', hidden: true } );
			input.setAttribute( 'aria-describedby', error.id );
			inputs[ key ] = input;
			return el( doc, 'p', { class: 'ie-field', 'data-ie-field': key }, [
				el( doc, 'label', { for: id, text: label } ),
				hint ? el( doc, 'span', { class: 'ie-hint', text: hint } ) : null,
				input,
				error,
			].filter( Boolean ) );
		}

		container.textContent = '';
		var formEl = el( doc, 'form', { class: 'ie-form', novalidate: true } );
		var network = null;

		if ( 'teachers' === kind ) {
			network = el( doc, 'fieldset', { class: 'ie-network' }, [
				el( doc, 'legend', { text: s.networkLegend } ),
				el( doc, 'p', { class: 'ie-hint', text: s.networkHint } ),
			] );
			// Only the options the site offers; with one left, it is asked for without a choice.
			var offered = Array.isArray( config.teacherNetworks ) && config.teacherNetworks.length ? config.teacherNetworks : TEACHER_NETWORKS;
			var options = [ [ 'partner', s.networkPartner ], [ 'direct', s.networkDirect ], [ 'intern', s.networkIntern ] ].filter( function ( option ) {
				return -1 !== offered.indexOf( option[ 0 ] );
			} );
			if ( ! options.length ) {
				options = [ [ 'partner', s.networkPartner ] ];
			}
			network.hidden = 1 === options.length;
			options.forEach( function ( option, i ) {
				var id = uid + '-network-' + option[ 0 ];
				network.appendChild( el( doc, 'label', { for: id, class: 'ie-radio' }, [
					el( doc, 'input', { type: 'radio', id: id, name: uid + '-network', value: option[ 0 ], checked: 0 === i } ),
					doc.createTextNode( ' ' + option[ 1 ] ),
				] ) );
			} );
			formEl.appendChild( network );
			formEl.appendChild( field( 'name', s.teacherName, { type: 'text', autocomplete: 'name', required: true } ) );
			formEl.appendChild( field( 'rf', s.rf, { type: 'text', inputmode: 'numeric', maxlength: 9, autocomplete: 'off', placeholder: '000.000-0' }, s.rfHint ) );
			formEl.appendChild( field( 'cpf', s.cpf, { type: 'text', inputmode: 'numeric', maxlength: 14, autocomplete: 'off', placeholder: '000.000.000-00' } ) );
		} else if ( state ) {
			formEl.appendChild( field( 'ra', s.stateRa, { type: 'text', inputmode: 'numeric', maxlength: RA_LENGTH, autocomplete: 'off', required: true }, s.stateRaHint ) );
			formEl.appendChild( field( 'raDigit', s.stateRaDigit, { type: 'text', maxlength: 1, autocomplete: 'off', required: true, class: 'ie-input ie-input-short' }, s.stateRaDigitHint ) );
			formEl.appendChild( field( 'birthDate', s.birthDate, { type: 'date', required: true } ) );
			formEl.appendChild( el( doc, 'p', { class: 'ie-hint ie-lookup' }, [
				doc.createTextNode( s.stateRaLookup + ' ' ),
				el( doc, 'a', { href: config.state.raUrl, target: '_blank', rel: 'noopener noreferrer', text: config.state.raUrl.replace( /^https?:\/\//, '' ).split( '/' )[ 0 ] } ),
			] ) );
		} else {
			formEl.appendChild( field( 'name', s.studentName, { type: 'text', autocomplete: 'name', required: true } ) );
			formEl.appendChild( field( 'birthDate', s.birthDate, { type: 'date', required: true } ) );
		}

		var submit = el( doc, 'button', { type: 'submit', class: 'ie-button', text: s.submit } );
		formEl.appendChild( el( doc, 'p', { class: 'ie-actions' }, [ submit ] ) );

		var emailOut = el( doc, 'output', { class: 'ie-value' } );
		var passwordOut = el( doc, 'output', { class: 'ie-value', text: config.password } );
		var copyEmail = el( doc, 'button', { type: 'button', class: 'ie-copy', text: s.copyEmail } );
		var copyPassword = el( doc, 'button', { type: 'button', class: 'ie-copy', text: s.copyPassword } );
		var status = el( doc, 'p', { class: 'ie-status', role: 'status' } );
		var emailLabel = el( doc, 'p', { class: 'ie-result-label', text: 'teachers' === kind ? s.teacherResult : s.studentResult } );
		var result = el( doc, 'div', { class: 'ie-result', hidden: true }, [
			emailLabel,
			el( doc, 'p', { class: 'ie-result-row' }, [ emailOut, copyEmail ] ),
			el( doc, 'p', { class: 'ie-result-label', text: state ? s.statePasswordResult : s.passwordResult } ),
			el( doc, 'p', { class: 'ie-result-row' }, [ passwordOut, copyPassword ] ),
		] );

		// Without a platform address there is no next step to point at.
		var platform = config.platformUrl ? el( doc, 'p', { class: 'ie-next' }, [
			doc.createTextNode( s.nextStep + ' ' ),
			el( doc, 'a', { href: config.platformUrl, target: '_blank', rel: 'noopener noreferrer', text: config.platformName || config.platformUrl } ),
		] ) : null;

		container.appendChild( formEl );
		container.appendChild( result );
		container.appendChild( status );
		if ( platform ) {
			container.appendChild( platform );
		}
		if ( s.privacy ) {
			container.appendChild( el( doc, 'p', { class: 'ie-privacy', text: s.privacy } ) );
		}

		function networkValue() {
			var checked = network && network.querySelector( 'input:checked' );
			return checked ? checked.value : 'partner';
		}

		function syncNetwork() {
			if ( ! network ) {
				return;
			}
			var direct = 'direct' === networkValue();
			// An intern is named as one, in the field and in the result.
			var intern = 'intern' === networkValue();
			formEl.querySelector( '[data-ie-field="name"] label' ).textContent = intern ? s.internName : s.teacherName;
			emailLabel.textContent = intern ? s.internResult : s.teacherResult;
			formEl.querySelector( '[data-ie-field="rf"]' ).hidden = ! direct;
			formEl.querySelector( '[data-ie-field="cpf"]' ).hidden = direct;
			reset();
		}

		function showErrors( keys, reasons ) {
			Object.keys( inputs ).forEach( function ( key ) {
				var input = inputs[ key ];
				var error = doc.getElementById( input.getAttribute( 'aria-describedby' ) );
				var bad = -1 !== keys.indexOf( key );
				var checkDigit = reasons && 'checkDigit' === reasons[ key ];
				input.setAttribute( 'aria-invalid', bad ? 'true' : 'false' );
				error.hidden = ! bad;
				if ( ! bad ) {
					error.textContent = '';
				} else {
					error.textContent = checkDigit ? s.error_rfCheck : s[ 'error_' + key ];
				}
			} );
			if ( keys.length ) {
				inputs[ keys[ 0 ] ].focus();
			}
		}

		function reset() {
			result.hidden = true;
			emailOut.textContent = '';
			status.textContent = '';
		}

		function outcomeOf() {
			if ( 'teachers' === kind ) {
				return teacherEmail( {
					network: networkValue(),
					name: inputs.name.value,
					rf: inputs.rf.value,
					cpf: inputs.cpf.value,
				}, config );
			}
			if ( state ) {
				return stateStudentEmail( { ra: inputs.ra.value, raDigit: inputs.raDigit.value, birthDate: inputs.birthDate.value }, config );
			}
			return studentEmail( { name: inputs.name.value, birthDate: inputs.birthDate.value }, config );
		}

		function generate() {
			var outcome = outcomeOf();
			showErrors( outcome.errors, outcome.reasons );
			if ( outcome.errors.length ) {
				reset();
				status.textContent = s.fixErrors;
				return false;
			}
			// A break opportunity after "@" and each dot, so a long address
			// wraps between its parts; <wbr> adds nothing to textContent,
			// which is what the Copy button copies.
			emailOut.textContent = '';
			outcome.email.split( /(?<=[@.])/ ).forEach( function ( part, i ) {
				if ( i ) {
					emailOut.appendChild( doc.createElement( 'wbr' ) );
				}
				emailOut.appendChild( doc.createTextNode( part ) );
			} );
			if ( outcome.password ) {
				passwordOut.textContent = outcome.password;
			}
			result.hidden = false;
			status.textContent = s.ready;
			return true;
		}

		formEl.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			generate();
		} );
		formEl.addEventListener( 'input', function ( event ) {
			if ( inputs.cpf && event.target === inputs.cpf ) {
				var formatted = formatCpf( inputs.cpf.value );
				if ( formatted !== inputs.cpf.value ) {
					inputs.cpf.value = formatted;
				}
			}
			if ( inputs.rf && event.target === inputs.rf ) {
				var rf = formatRf( inputs.rf.value );
				if ( rf !== inputs.rf.value ) {
					inputs.rf.value = rf;
				}
			}
			if ( inputs.ra && event.target === inputs.ra ) {
				inputs.ra.value = digits( inputs.ra.value ).slice( 0, RA_LENGTH );
			}
			if ( inputs.raDigit && event.target === inputs.raDigit ) {
				inputs.raDigit.value = inputs.raDigit.value.replace( /[^0-9xX]/g, '' ).slice( 0, 1 ).toUpperCase();
			}
			if ( ! result.hidden ) {
				reset();
			}
		} );
		if ( network ) {
			network.addEventListener( 'change', syncNetwork );
			syncNetwork();
		}

		[ [ copyEmail, emailOut, s.emailCopied ], [ copyPassword, passwordOut, s.passwordCopied ] ].forEach( function ( item ) {
			item[ 0 ].addEventListener( 'click', function () {
				copyText( doc, item[ 1 ].textContent ).then( function () {
					status.textContent = item[ 2 ];
				}, function () {
					status.textContent = s.copyFailed;
				} );
			} );
		} );

		return { form: kind, network: state ? 'state' : 'city', generate: generate, inputs: inputs, email: function () {
			return emailOut.textContent;
		} };
	}

	/**
	 * Mount every container on the page.
	 *
	 * @param {Document} doc Document.
	 */
	function boot( doc ) {
		doc.querySelectorAll( '[data-ie-config]' ).forEach( function ( container ) {
			if ( container.getAttribute( 'data-ie-mounted' ) ) {
				return;
			}
			container.setAttribute( 'data-ie-mounted', '1' );
			mount( container, JSON.parse( container.getAttribute( 'data-ie-config' ) ) );
		} );
	}

	var api = {
		nameParts: nameParts,
		studentName: studentName,
		teacherName: teacherName,
		birthDate: birthDate,
		isCpf: isCpf,
		rfCheckDigit: rfCheckDigit,
		isRf: isRf,
		formatCpf: formatCpf,
		formatRf: formatRf,
		studentEmail: studentEmail,
		teacherEmail: teacherEmail,
		stateStudentEmail: stateStudentEmail,
		formOf: formOf,
		networkOf: networkOf,
		mount: mount,
		boot: boot,
	};

	if ( 'object' === typeof module && module.exports ) {
		module.exports = api;
	} else if ( root.document ) {
		if ( 'loading' === root.document.readyState ) {
			root.document.addEventListener( 'DOMContentLoaded', function () {
				boot( root.document );
			} );
		} else {
			boot( root.document );
		}
	}
}( this ) );
