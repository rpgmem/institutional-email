<?php

declare(strict_types=1);

namespace IE\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use InstitutionalEmail\Button;
use InstitutionalEmail\Icons;
use PHPUnit\Framework\TestCase;

/**
 * The button family's markup (#89): Free Form Certificate's classes under
 * this plugin's prefix, with the two categories it uses.
 *
 * @covers \InstitutionalEmail\Button
 */
final class ButtonTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( 'esc_attr' )->alias( static fn( $s ): string => htmlspecialchars( (string) $s, ENT_QUOTES ) );
		Functions\when( 'esc_html' )->alias( static fn( $s ): string => htmlspecialchars( (string) $s, ENT_QUOTES ) );
		Functions\when( 'esc_url' )->alias( static fn( $s ): string => str_starts_with( (string) $s, 'javascript:' ) ? '' : htmlspecialchars( (string) $s, ENT_QUOTES ) );
	}

	protected function tearDown(): void {
		$this->addToAssertionCount( \Mockery::getContainer()->mockery_getExpectationCount() );
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_a_primary_submit_with_its_icon(): void {
		self::assertSame(
			'<button type="submit" class="ie-btn ie-btn-primary ie-icon-save" id="submit" name="submit">Save Changes</button>',
			Button::html(
				array(
					'label'    => 'Save Changes',
					'category' => 'primary',
					'icon'     => 'save',
					'type'     => 'submit',
					'id'       => 'submit',
					'name'     => 'submit',
				)
			)
		);
	}

	public function test_neutral_is_the_default_and_hooks_and_data_attributes_follow(): void {
		self::assertSame(
			'<button type="button" class="ie-btn ie-btn-neutral ie-icon-copy ie-copy" data-ie-copy="ie-shortcode">Copy</button>',
			Button::html(
				array(
					'label' => 'Copy',
					'icon'  => 'copy',
					'class' => 'ie-copy',
					'attrs' => array( 'data-ie-copy' => 'ie-shortcode' ),
				)
			)
		);
	}

	public function test_an_unknown_category_is_drawn_neutral_and_reported(): void {
		Functions\expect( '_doing_it_wrong' )->once();
		self::assertStringContainsString( 'class="ie-btn ie-btn-neutral"', Button::html( array( 'label' => 'X', 'category' => 'danger' ) ), 'Only the categories this plugin uses exist.' );
	}

	public function test_an_unknown_icon_is_dropped_and_reported(): void {
		Functions\expect( '_doing_it_wrong' )->once();
		self::assertSame( '<button type="button" class="ie-btn ie-btn-neutral">X</button>', Button::html( array( 'label' => 'X', 'icon' => 'settings' ) ), 'An icon without a mask class would draw nothing.' );
	}

	public function test_a_link_button_and_the_disabled_states(): void {
		self::assertSame(
			'<a href="https://example.org/" class="ie-btn ie-btn-primary ie-btn-block ie-icon-external" target="_blank">Go</a>',
			Button::html(
				array(
					'label'    => 'Go',
					'category' => 'primary',
					'icon'     => 'external',
					'href'     => 'https://example.org/',
					'block'    => true,
					'type'     => 'submit',
					'name'     => 'ignored',
					'attrs'    => array( 'target' => '_blank' ),
				)
			)
		);
		self::assertStringContainsString( ' disabled>', Button::html( array( 'label' => 'X', 'disabled' => true ) ) );
		$link = Button::html( array( 'label' => 'X', 'href' => '#', 'disabled' => true ) );
		self::assertStringContainsString( 'disabled" aria-disabled="true"', $link );
	}

	public function test_values_are_escaped_and_handlers_refused(): void {
		$html = Button::html(
			array(
				'label' => '<b>Hi</b>',
				'type'  => 'evil',
				'attrs' => array(
					'onclick'   => 'alert(1)',
					'title'     => '"x"',
					'data-flag' => true,
					'data-off'  => false,
					'bad key'   => 'x',
				),
			)
		);
		self::assertSame( '<button type="button" class="ie-btn ie-btn-neutral" title="&quot;x&quot;" data-flag>&lt;b&gt;Hi&lt;/b&gt;</button>', $html );
	}

	public function test_without_a_label_nothing_is_printed(): void {
		self::assertSame( '', Button::html( array( 'icon' => 'copy' ) ) );
		self::assertSame( '', Button::html( array( 'label' => '' ) ) );
	}

	public function test_render_prints_the_markup(): void {
		$this->expectOutputString( Button::html( array( 'label' => 'X' ) ) );
		Button::render( array( 'label' => 'X' ) );
	}

	public function test_every_icon_a_button_may_carry_is_a_registry_drawing(): void {
		foreach ( Icons::MASKED as $name ) {
			self::assertTrue( Icons::has( $name ), $name );
			self::assertTrue( Icons::masked( $name ), $name );
		}
		self::assertFalse( Icons::masked( 'settings' ), 'A drawing used inline only has no class.' );
	}
}
