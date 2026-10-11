<?php
/**
 * The generator's configuration.
 *
 * @package InstitutionalEmail
 */

declare(strict_types=1);

namespace InstitutionalEmail;

/**
 * The defaults the plugin and the standalone build share. The script reads
 * the rules' parameters and every string it shows from here, so it has no
 * text of its own and the two deliverables cannot drift apart.
 */
final class Config {

	/**
	 * The RME/SP institutional domain.
	 */
	public const DOMAIN = 'edu.sme.prefeitura.sp.gov.br';

	/**
	 * The initial password of a new account.
	 */
	public const PASSWORD = '12345678';

	/**
	 * Where the new account is used first.
	 */
	public const PLATFORM_URL = 'https://classroom.google.com/';

	/**
	 * The forms the shortcode and the standalone page offer.
	 */
	public const FORMS = array( 'students', 'teachers' );

	/**
	 * The configuration the script reads.
	 *
	 * @return array{domain: string, password: string, platformName: string, platformUrl: string, strings: array<string, string>}
	 */
	public static function defaults(): array {
		return array(
			'domain'       => self::DOMAIN,
			'password'     => self::PASSWORD,
			'platformName' => __( 'Google Classroom', 'institutional-email' ),
			'platformUrl'  => self::PLATFORM_URL,
			'strings'      => self::strings(),
		);
	}

	/**
	 * Every string the form shows.
	 *
	 * @return array<string, string>
	 */
	public static function strings(): array {
		return array(
			'studentName'     => __( 'Student\'s full name', 'institutional-email' ),
			'birthDate'       => __( 'Student\'s date of birth', 'institutional-email' ),
			'teacherName'     => __( 'Teacher\'s full name', 'institutional-email' ),
			'networkLegend'   => __( 'Network', 'institutional-email' ),
			'networkHint'     => __( 'Direct network for public servants; partner network for staff who are not public servants.', 'institutional-email' ),
			'networkPartner'  => __( 'Partner network (CPF)', 'institutional-email' ),
			'networkDirect'   => __( 'Direct network (RF)', 'institutional-email' ),
			'rf'              => __( 'Registro Funcional (RF)', 'institutional-email' ),
			'rfHint'          => __( 'The 7 digits, without the check digit.', 'institutional-email' ),
			'cpf'             => __( 'CPF', 'institutional-email' ),
			'submit'          => __( 'Find the email', 'institutional-email' ),
			'studentResult'   => __( 'The student\'s email is:', 'institutional-email' ),
			'teacherResult'   => __( 'The teacher\'s email is:', 'institutional-email' ),
			'passwordResult'  => __( 'The initial password is:', 'institutional-email' ),
			'copyEmail'       => __( 'Copy email', 'institutional-email' ),
			'copyPassword'    => __( 'Copy password', 'institutional-email' ),
			'emailCopied'     => __( 'Email copied.', 'institutional-email' ),
			'passwordCopied'  => __( 'Password copied.', 'institutional-email' ),
			'copyFailed'      => __( 'Could not copy; select the text and copy it by hand.', 'institutional-email' ),
			'ready'           => __( 'Email found. Write it down with the password.', 'institutional-email' ),
			'fixErrors'       => __( 'Check the fields marked below.', 'institutional-email' ),
			'error_name'      => __( 'Type the full name, with at least a first and a last name.', 'institutional-email' ),
			'error_birthDate' => __( 'Choose a valid date of birth.', 'institutional-email' ),
			'error_rf'        => __( 'Type the 7 digits of the RF.', 'institutional-email' ),
			'error_cpf'       => __( 'Type a valid CPF.', 'institutional-email' ),
			'nextStep'        => __( 'Then sign in with this email and password, not a personal account, at:', 'institutional-email' ),
			'privacy'         => __( 'The email is worked out in your browser: nothing you type is sent or stored anywhere.', 'institutional-email' ),
		);
	}
}
