<?php
/**
 * The plugin's buttons.
 *
 * @package InstitutionalEmail
 */

declare(strict_types=1);

namespace InstitutionalEmail;

/**
 * Markup of a `.ie-btn` button: a slim copy of Free Form Certificate's button
 * family (its `Core\Button`), with the two categories this plugin uses.
 *
 * The category names what the action does: `primary` completes the screen's
 * work (Save Changes, work out the address, go to the SED), `neutral` opens,
 * picks or copies. `assets/css/buttons.css` draws them from tokens each
 * palette maps, so the admin and the form share the family and differ only
 * in shape. The icon is a `.ie-icon-*` class from `Icons`, drawn as a mask
 * on `::before`, so a script that rewrites the label keeps it. `button()` in
 * `assets/js/institutional-email.js` prints the same markup for the buttons
 * the form's script builds.
 */
final class Button {

	/**
	 * The categories this plugin uses, from Free Form Certificate's five.
	 */
	public const CATEGORIES = array( 'primary', 'neutral' );

	/**
	 * What an unknown category falls back to: it says nothing about the
	 * action, which is the honest default for a name nobody recognised.
	 */
	public const FALLBACK = 'neutral';

	/**
	 * The `type` values a `<button>` accepts.
	 */
	private const TYPES = array( 'button', 'submit', 'reset' );

	/**
	 * The markup.
	 *
	 * Arguments: `label` (visible text, required; without it nothing is
	 * printed); `category` (one of CATEGORIES, default `neutral`); `icon`
	 * (an `Icons::MASKED` name); `href` (makes the button a link); `type`
	 * (`button`, `submit` or `reset`, ignored with `href`); `id`; `name` and
	 * `value` (ignored with `href`); `class` (extra space-separated classes,
	 * the hooks scripts read); `block` (fills its container); `disabled`;
	 * `attrs` (further attributes such as `data-*`: a string is escaped,
	 * `true` prints the bare attribute, and an `on*` handler is refused).
	 *
	 * @param array<string, mixed> $args See above.
	 */
	public static function html( array $args ): string {
		$label = is_scalar( $args['label'] ?? null ) ? (string) $args['label'] : '';
		if ( '' === $label ) {
			return '';
		}

		$category = is_string( $args['category'] ?? null ) ? $args['category'] : self::FALLBACK;
		if ( ! in_array( $category, self::CATEGORIES, true ) ) {
			self::doingItWrong( sprintf( '"%1$s" is not a button category; it is drawn as %2$s. Use one of: %3$s.', $category, self::FALLBACK, implode( ', ', self::CATEGORIES ) ) );
			$category = self::FALLBACK;
		}

		$classes = array( 'ie-btn', 'ie-btn-' . $category );
		if ( ! empty( $args['block'] ) ) {
			$classes[] = 'ie-btn-block';
		}

		$icon = is_string( $args['icon'] ?? null ) ? $args['icon'] : '';
		if ( Icons::masked( $icon ) ) {
			$classes[] = 'ie-icon-' . $icon;
		} elseif ( '' !== $icon ) {
			self::doingItWrong( sprintf( '"%s" is not an icon a button can carry; the button is drawn without one.', $icon ) );
		}

		$extra = preg_split( '/\s+/', trim( is_string( $args['class'] ?? null ) ? $args['class'] : '' ) );
		foreach ( is_array( $extra ) ? $extra : array() as $one ) {
			if ( '' !== $one ) {
				$classes[] = $one;
			}
		}

		$href     = isset( $args['href'] ) && is_scalar( $args['href'] ) ? (string) $args['href'] : null;
		$disabled = ! empty( $args['disabled'] );
		$attrs    = array();

		if ( null === $href ) {
			$type          = is_string( $args['type'] ?? null ) ? $args['type'] : 'button';
			$attrs['type'] = in_array( $type, self::TYPES, true ) ? $type : 'button';
		} else {
			$attrs['href'] = esc_url( $href );
			if ( $disabled ) {
				$classes[] = 'disabled';
			}
		}
		$attrs['class'] = implode( ' ', array_unique( $classes ) );

		foreach ( null === $href ? array( 'id', 'name', 'value' ) : array( 'id' ) as $key ) {
			if ( isset( $args[ $key ] ) && is_scalar( $args[ $key ] ) && '' !== (string) $args[ $key ] ) {
				$attrs[ $key ] = (string) $args[ $key ];
			}
		}

		if ( $disabled ) {
			if ( null === $href ) {
				$attrs['disabled'] = true;
			} else {
				$attrs['aria-disabled'] = 'true';
			}
		}

		foreach ( is_array( $args['attrs'] ?? null ) ? $args['attrs'] : array() as $key => $value ) {
			$key = strtolower( (string) $key );
			if ( 1 !== preg_match( '/^[a-z][a-z0-9_:.-]*$/', $key ) || str_starts_with( $key, 'on' ) || isset( $attrs[ $key ] ) ) {
				continue;
			}
			if ( true === $value ) {
				$attrs[ $key ] = true;
			} elseif ( is_scalar( $value ) && false !== $value ) {
				$attrs[ $key ] = (string) $value;
			}
		}

		$printed = '';
		foreach ( $attrs as $key => $value ) {
			if ( true === $value ) {
				$printed .= ' ' . $key;
			} elseif ( 'href' === $key ) {
				$printed .= ' href="' . $value . '"';
			} else {
				$printed .= ' ' . $key . '="' . esc_attr( $value ) . '"';
			}
		}

		$tag = null === $href ? 'button' : 'a';
		return '<' . $tag . $printed . '>' . esc_html( $label ) . '</' . $tag . '>';
	}

	/**
	 * Print the markup.
	 *
	 * @param array<string, mixed> $args See html().
	 */
	public static function render( array $args ): void {
		echo self::html( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- html() escapes every value.
	}

	/**
	 * Report a call that asked for something the family does not have.
	 *
	 * @param string $message Message for the developer.
	 */
	private static function doingItWrong( string $message ): void {
		if ( function_exists( '_doing_it_wrong' ) ) {
			// A message to the developer, read under WP_DEBUG: English and untranslated, like core's own.
			_doing_it_wrong( __METHOD__, esc_html( $message ), '1.5.0' );
		}
	}
}
