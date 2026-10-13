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
	 * A student's middle name gives its initial to the address when it has at
	 * least this many letters, so "da", "dos" and "Rui" drop out.
	 */
	public const MIDDLE_MIN = 4;

	/**
	 * How wide the form grows on a page, in pixels, unless the settings say
	 * otherwise. The standalone page fills its iframe instead.
	 */
	public const FORM_WIDTH = 640;

	/**
	 * The form's colours, as the stylesheet declares them. Each key is a
	 * custom property of the form, `--ie-` plus the key with dashes.
	 */
	public const COLORS = array(
		'accent'      => '#2271b1',
		'accent_text' => '#ffffff',
		'border'      => '#8c8f94',
		'muted'       => '#50575e',
		'error'       => '#b32d2e',
		'ok_bg'       => '#edfaef',
		'ok_border'   => '#00a32a',
		'value_bg'    => '#f6f7f7',
	);

	/**
	 * The corners' radius, in pixels, as the stylesheet declares it.
	 */
	public const RADIUS = 4;

	/**
	 * The fonts the form can use, the same three as the other plugins of the
	 * family: the theme's (the default), the plugins' own system font stack,
	 * or Arial.
	 */
	public const FONTS = array( 'theme', 'plugin', 'arial' );

	/**
	 * The plugins' own font: the system stack Free Form Certificate's forms
	 * use, so the two read as one family whatever the theme.
	 */
	public const PLUGIN_FONT = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif';

	/**
	 * The school networks a form can serve: São Paulo city schools (RME/SP)
	 * and São Paulo state schools (SEDUC-SP).
	 */
	public const NETWORKS = array( 'city', 'state' );

	/**
	 * A state student's address: RA + check digit + "sp" @ this domain.
	 */
	public const STATE_DOMAIN = 'al.educacao.sp.gov.br';

	public const STATE_SUFFIX = 'sp';

	/**
	 * Where a state student looks up the RA.
	 */
	public const STATE_RA_URL = 'https://sed.educacao.sp.gov.br/NCA/CadastroAluno/ConsultaRAAluno';

	/**
	 * Where a state teacher creates the address, on the first access to the SED.
	 */
	public const STATE_TEACHER_URL = 'https://sed.educacao.sp.gov.br/obtenha-seu-acesso';

	/**
	 * The forms the shortcode and the standalone page offer.
	 */
	public const FORMS = array( 'students', 'teachers' );

	/**
	 * The options of the city teachers' form: partner network (CPF), direct
	 * network (RF) and interns (CPF with an "e" before it).
	 */
	public const TEACHER_NETWORKS = array( 'partner', 'direct', 'intern' );

	/**
	 * The configuration the script reads.
	 *
	 * @return array{network: string, forms: list<string>, domain: string, password: string, middleMin: int, checkRfDigit: bool, teacherNetworks: list<string>, analytics: bool, state: array{domain: string, suffix: string, raUrl: string, teacherUrl: string}, platformName: string, platformUrl: string, strings: array<string, string>}
	 */
	public static function defaults(): array {
		return array(
			'network'         => self::NETWORKS[0],
			'forms'           => self::FORMS,
			'domain'          => self::DOMAIN,
			'password'        => self::PASSWORD,
			'middleMin'       => self::MIDDLE_MIN,
			'checkRfDigit'    => true,
			'teacherNetworks' => self::TEACHER_NETWORKS,
			'analytics'       => false,
			'state'           => array(
				'domain'     => self::STATE_DOMAIN,
				'suffix'     => self::STATE_SUFFIX,
				'raUrl'      => self::STATE_RA_URL,
				'teacherUrl' => self::STATE_TEACHER_URL,
			),
			'platformName'    => __( 'Google Classroom', 'institutional-email' ),
			'platformUrl'     => self::PLATFORM_URL,
			'strings'         => self::strings(),
		);
	}

	/**
	 * Every string the form shows.
	 *
	 * @return array<string, string>
	 */
	public static function strings(): array {
		return array(
			'profileLabel'        => __( 'Choose the profile', 'institutional-email' ),
			'profileStudents'     => __( 'Students', 'institutional-email' ),
			'profileTeachers'     => __( 'Teachers', 'institutional-email' ),
			'studentName'         => __( 'Student\'s full name', 'institutional-email' ),
			'birthDate'           => __( 'Student\'s date of birth', 'institutional-email' ),
			'teacherName'         => __( 'Teacher\'s full name', 'institutional-email' ),
			'internName'          => __( 'Intern\'s full name', 'institutional-email' ),
			'networkLegend'       => __( 'Network', 'institutional-email' ),
			'networkHint'         => __( 'Direct network for public servants; partner network for staff who are not public servants; Intern for interns of the city schools.', 'institutional-email' ),
			'networkPartner'      => __( 'Partner network (CPF)', 'institutional-email' ),
			'networkDirect'       => __( 'Direct network (RF)', 'institutional-email' ),
			'networkIntern'       => __( 'Intern (CPF)', 'institutional-email' ),
			'rf'                  => __( 'Registro Funcional (RF)', 'institutional-email' ),
			'rfHint'              => __( 'The 7 digits, check digit included: 518.178-0 is 5181780.', 'institutional-email' ),
			'cpf'                 => __( 'CPF', 'institutional-email' ),
			'submit'              => __( 'Find the email', 'institutional-email' ),
			'studentResult'       => __( 'The student\'s email is:', 'institutional-email' ),
			'teacherResult'       => __( 'The teacher\'s email is:', 'institutional-email' ),
			'internResult'        => __( 'The intern\'s email is:', 'institutional-email' ),
			'passwordResult'      => __( 'The initial password is:', 'institutional-email' ),
			'copyEmail'           => __( 'Copy email', 'institutional-email' ),
			'copyPassword'        => __( 'Copy password', 'institutional-email' ),
			'emailCopied'         => __( 'Email copied.', 'institutional-email' ),
			'passwordCopied'      => __( 'Password copied.', 'institutional-email' ),
			'copyFailed'          => __( 'Could not copy; select the text and copy it by hand.', 'institutional-email' ),
			'ready'               => __( 'Email found. Write it down with the password.', 'institutional-email' ),
			'fixErrors'           => __( 'Check the marked fields.', 'institutional-email' ),
			'error_name'          => __( 'Type the full name, with at least a first and a last name.', 'institutional-email' ),
			'error_birthDate'     => __( 'Choose a valid date of birth.', 'institutional-email' ),
			'error_rf'            => __( 'Type the 7 digits of the RF.', 'institutional-email' ),
			'error_rfCheck'       => __( 'This RF\'s check digit does not match: check the number.', 'institutional-email' ),
			'error_cpf'           => __( 'Type a valid CPF.', 'institutional-email' ),
			'stateRa'             => __( 'Student\'s RA (Registro do Aluno)', 'institutional-email' ),
			'stateRaHint'         => __( 'The digits before the check digit, as the SED shows them; leading zeros may be left out.', 'institutional-email' ),
			'stateRaDigit'        => __( 'RA check digit', 'institutional-email' ),
			'stateRaDigitHint'    => __( 'A number from 0 to 9, or X.', 'institutional-email' ),
			'stateRaLookup'       => __( 'Do not know the RA? Look it up on the SED:', 'institutional-email' ),
			'statePasswordResult' => __( 'The initial password is the date of birth:', 'institutional-email' ),
			'stateTeacherIntro'   => __( 'State teachers create their institutional email on their first access to the SED, and its password is the same as the SED one.', 'institutional-email' ),
			'stateTeacherLink'    => __( 'Get your access on the SED', 'institutional-email' ),
			'error_ra'            => __( 'Type the RA\'s digits, up to 12.', 'institutional-email' ),
			'error_raDigit'       => __( 'Type the check digit: a number from 0 to 9, or X.', 'institutional-email' ),
			'nextStep'            => __( 'Then sign in with this email and password, not a personal account, at:', 'institutional-email' ),
			'privacy'             => __( 'The email is worked out in your browser: nothing you type is sent or stored anywhere.', 'institutional-email' ),
		);
	}
}
