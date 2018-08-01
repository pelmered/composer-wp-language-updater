<?php
/**
 * Created by PhpStorm.
 * User: richardsweeney
 * Date: 2017-10-16
 * Time: 11:49
 */

namespace AngryCreative\WPLanguageUpdater;

/**
 * Class ThemeTest
 *
 * @package AngryCreative\WPLanguageUpdater
 */
class ThemeTest extends \PHPUnit_Framework_TestCase {

	public function testTheme() {
        require_once T10ns::locate_composer_autoloader();
        $dir = T10ns::locate_wp_content();

		//$dir    = dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/public/wp-content';
		$theme = new T10ns( 'theme', 'twentytwelve', '2.2.0.0', [ 'sv_SE' ], $dir );

		$this->assertInternalType( 'array', $theme->get_languages() );
		$this->assertNotEmpty( $theme->get_languages() );
		$this->assertEquals( $theme->get_languages(), [ 'sv_SE' ] );

		$this->assertInternalType( 'array', $theme->get_t10ns() );
		$this->assertNotEmpty( $theme->get_t10ns() );

		$this->assertEquals( $dir . '/languages/themes', $theme->get_dest_path( 'theme', $dir ) );

		$result = $theme->fetch_all_t10ns();
		$this->assertInternalType( 'array', $result );
		$this->assertNotEmpty( $result );

		$this->assertFileExists( $theme->get_dest_path( 'theme', $dir ) );
		$this->assertFileExists( $theme->get_dest_path( 'theme', $dir ) . '/twentytwelve-sv_SE.mo' );
		$this->assertFileExists( $theme->get_dest_path( 'theme', $dir ) . '/twentytwelve-sv_SE.po' );
	}

}
