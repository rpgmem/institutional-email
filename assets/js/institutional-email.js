/**
 * Institutional Email: works out the RME/SP institutional address of a
 * student (name + date of birth) or a teacher (name + RF or CPF).
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
	 * A CPF as it is usually written, 000.000.000-00, for the field.
	 *
	 * @param {string} value Typed value.
	 * @return {string} Formatted, as far as the digits go.
	 */
	function formatCpf( value ) {
		var n = digits( value ).slice( 0, 11 );
		return n.replace( /^(\d{3})(\d)/, '$1.$2' )
			.replace( /^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3' )
			.replace( /\.(\d{3})(\d)/, '.$1-$2' );
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
	 * @param {Object} values { network: 'direct'|'partner', name, rf, cpf }.
	 * @param {Object} config Configuration (domain).
	 * @return {{email: string, errors: string[]}} The address, or the keys of the fields in error.
	 */
	function teacherEmail( values, config ) {
		var errors = [];
		var name = teacherName( values.name );
		var direct = 'direct' === values.network;
		var id = direct ? digits( values.rf ) : digits( values.cpf );
		if ( ! name ) {
			errors.push( 'name' );
		}
		if ( direct && 7 !== id.length ) {
			errors.push( 'rf' );
		}
		if ( ! direct && ! isCpf( id ) ) {
			errors.push( 'cpf' );
		}
		return { email: errors.length ? '' : name + '.' + id + '@' + config.domain, errors: errors };
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
	 * Draw a form inside a container and wire it.
	 *
	 * @param {HTMLElement} container Container.
	 * @param {Object}      config    Configuration.
	 * @param {string}      [form]    'students' or 'teachers'; read from the container when absent.
	 * @return {Object} Handles for the tests.
	 */
	function mount( container, config, form ) {
		var doc = container.ownerDocument;
		var s = config.strings;
		var kind = form || formOf( container );
		var uid = 'ie-' + Math.random().toString( 36 ).slice( 2, 8 );
		var inputs = {};

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
			[ [ 'partner', s.networkPartner ], [ 'direct', s.networkDirect ] ].forEach( function ( option, i ) {
				var id = uid + '-network-' + option[ 0 ];
				network.appendChild( el( doc, 'label', { for: id, class: 'ie-radio' }, [
					el( doc, 'input', { type: 'radio', id: id, name: uid + '-network', value: option[ 0 ], checked: 0 === i } ),
					doc.createTextNode( ' ' + option[ 1 ] ),
				] ) );
			} );
			formEl.appendChild( network );
			formEl.appendChild( field( 'name', s.teacherName, { type: 'text', autocomplete: 'name', required: true } ) );
			formEl.appendChild( field( 'rf', s.rf, { type: 'text', inputmode: 'numeric', maxlength: 7, autocomplete: 'off' }, s.rfHint ) );
			formEl.appendChild( field( 'cpf', s.cpf, { type: 'text', inputmode: 'numeric', maxlength: 14, autocomplete: 'off', placeholder: '000.000.000-00' } ) );
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
		var result = el( doc, 'div', { class: 'ie-result', hidden: true }, [
			el( doc, 'p', { class: 'ie-result-label', text: 'teachers' === kind ? s.teacherResult : s.studentResult } ),
			el( doc, 'p', { class: 'ie-result-row' }, [ emailOut, copyEmail ] ),
			el( doc, 'p', { class: 'ie-result-label', text: s.passwordResult } ),
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
			formEl.querySelector( '[data-ie-field="rf"]' ).hidden = ! direct;
			formEl.querySelector( '[data-ie-field="cpf"]' ).hidden = direct;
			reset();
		}

		function showErrors( keys ) {
			Object.keys( inputs ).forEach( function ( key ) {
				var input = inputs[ key ];
				var error = doc.getElementById( input.getAttribute( 'aria-describedby' ) );
				var bad = -1 !== keys.indexOf( key );
				input.setAttribute( 'aria-invalid', bad ? 'true' : 'false' );
				error.hidden = ! bad;
				error.textContent = bad ? s[ 'error_' + key ] : '';
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

		function generate() {
			var outcome = 'teachers' === kind ?
				teacherEmail( {
					network: networkValue(),
					name: inputs.name.value,
					rf: inputs.rf.value,
					cpf: inputs.cpf.value,
				}, config ) :
				studentEmail( { name: inputs.name.value, birthDate: inputs.birthDate.value }, config );
			showErrors( outcome.errors );
			if ( outcome.errors.length ) {
				reset();
				status.textContent = s.fixErrors;
				return false;
			}
			emailOut.textContent = outcome.email;
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
				inputs.rf.value = digits( inputs.rf.value ).slice( 0, 7 );
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

		return { form: kind, generate: generate, inputs: inputs, email: function () {
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
		formatCpf: formatCpf,
		studentEmail: studentEmail,
		teacherEmail: teacherEmail,
		formOf: formOf,
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
