<?php
/**
 * Created by PhpStorm.
 * User: richardsweeney
 * Date: 2017-10-16
 * Time: 11:49
 */

namespace AngryCreative\WPLanguageUpdater;

require_once 'Base.php';

/**
 * Class CoreTest
 *
 * @package AngryCreative\WPLanguageUpdater
 */
class CoreTest extends Base {

	public function testCore() {
        $languages = [ 'en_GB' ,'sv_SE' ];

        require_once Config::locate_composer_autoloader();
        $dir = __DIR__.'/wp-content';

        $package = new \Composer\Package\CompletePackage('johnpbloch/wordpress', '4.8.2', '4.8.2');
        $package->setType('package');

        $config = new Config();
        $config->set_languages($languages);
        $config->set_wp_content_path($dir);

		$core = new T10ns( $package, $config );

		$this->assertEquals( $dir . '/languages', $core->get_dest_path() );

		$result = $core->fetch_all_t10ns();
		$this->assertInternalType( 'array', $result );
		$this->assertNotEmpty( $result );

		$this->assertFileExists( $core->get_dest_path() );

		foreach($languages as $language) {
            $this->assertFileExists( $core->get_dest_path() . '/'.$language.'.mo' );
            $this->assertFileExists( $core->get_dest_path() . '/'.$language.'.po' );
            $this->assertFileExists( $core->get_dest_path() . '/admin-'.$language.'.mo' );
            $this->assertFileExists( $core->get_dest_path() . '/admin-'.$language.'.po' );
        }
	}
}
