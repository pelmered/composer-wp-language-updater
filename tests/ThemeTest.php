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
 * Class ThemeTest
 *
 * @package AngryCreative\WPLanguageUpdater
 */
class ThemeTest extends Base {

	public function testTheme() {

        $languages = [ 'en_GB' ,'sv_SE' ];

        require_once Config::locate_composer_autoloader();

        $dir = $this->wp_content;

        $package = new \Composer\Package\CompletePackage('twentytwelve', '2.2.0.0', '2.2.0.0');
        $package->setType('wordpress-theme');

        $config = new Config();
        $config->set_languages($languages);
        $config->set_wp_content_path($dir);

        $theme = new T10ns( $package, $config );

		$this->assertInternalType( 'array', $theme->get_languages() );
		$this->assertNotEmpty( $theme->get_languages() );
		$this->assertEquals( $theme->get_languages(), $languages );

		$this->assertEquals( $dir . '/languages/themes', $theme->get_dest_path() );

		$result = $theme->fetch_all_t10ns();
		$this->assertInternalType( 'array', $result );
		$this->assertNotEmpty( $result );

		$this->assertFileExists( $theme->get_dest_path() );

        foreach($languages as $language) {
            $this->assertFileExists( $theme->get_dest_path() . '/twentytwelve-'.$language.'.mo' );
            $this->assertFileExists( $theme->get_dest_path() . '/twentytwelve-'.$language.'.po' );
        }
	}

}
