<?php
/**
 * Created by PhpStorm.
 * User: richardsweeney
 * Date: 2017-10-16
 * Time: 11:49
 */

namespace AngryCreative\WPLanguageUpdater;

/**
 * Class CoreTest
 *
 * @package AngryCreative\WPLanguageUpdater
 */
class CoreTest extends \PHPUnit_Framework_TestCase {

	public function testCore() {


		//require_once dirname( dirname( __DIR__ ) ) . '/vendor/autotoload.php';

        //$this->requireAutoloader();


        require_once T10ns::locate_composer_autoloader();
        $dir = T10ns::locate_wp_content();

		$core = new T10ns( 'core', '', '4.8.2', [ 'sv_SE' ], $dir );

		$this->assertEquals( $dir . '/languages', $core->get_dest_path( 'core', $dir ) );


		$result = $core->fetch_all_t10ns();
		$this->assertInternalType( 'array', $result );
		$this->assertNotEmpty( $result );

		$this->assertFileExists( $core->get_dest_path( 'core', $dir ) );
		$this->assertFileExists( $core->get_dest_path( 'core', $dir ) . '/sv_SE.mo' );
		$this->assertFileExists( $core->get_dest_path( 'core', $dir ) . '/sv_SE.po' );
		$this->assertFileExists( $core->get_dest_path( 'core', $dir ) . '/admin-sv_SE.mo' );
		$this->assertFileExists( $core->get_dest_path( 'core', $dir ) . '/admin-sv_SE.po' );

	}

}
