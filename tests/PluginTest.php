<?php
/**
 * Created by PhpStorm.
 * User: richardsweeney
 * Date: 2017-10-16
 * Time: 11:49
 */

namespace AngryCreative\WPLanguageUpdater;

/**
 * Class PluginTest
 *
 * @package AngryCreative\WPLanguageUpdater
 */
class PluginTest extends \PHPUnit_Framework_TestCase {

	public function testPlugin() {


        require_once T10ns::locate_composer_autoloader();
        $dir = T10ns::locate_wp_content();

		//$dir    = dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/public/wp-content';
		$plugin = new T10ns( 'plugin', 'redirection', '2.8.1', [ 'sv_SE' ], $dir );

		$this->assertInternalType( 'array', $plugin->get_languages() );
		$this->assertNotEmpty( $plugin->get_languages() );

		$this->assertInternalType( 'array', $plugin->get_t10ns() );
		$this->assertNotEmpty( $plugin->get_t10ns() );

		$this->assertEquals( $dir . '/languages/plugins', $plugin->get_dest_path( 'plugin', $dir ) );

		$result = $plugin->fetch_all_t10ns();
		$this->assertInternalType( 'array', $result );
		$this->assertNotEmpty( $result );

		$this->assertFileExists( $plugin->get_dest_path( 'plugin', $dir ) );
		$this->assertFileExists( $plugin->get_dest_path( 'plugin', $dir ) . '/redirection-sv_SE.mo' );
		$this->assertFileExists( $plugin->get_dest_path( 'plugin', $dir ) . '/redirection-sv_SE.po' );
	}

}
