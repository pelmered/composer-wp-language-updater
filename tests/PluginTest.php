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
 * Class PluginTest
 *
 * @package AngryCreative\WPLanguageUpdater
 */
class PluginTest extends Base {

	public function testPlugin() {

        $languages = [ 'en_GB' ,'sv_SE' ];

        $plugins = [
            'redirection' => '2.8.1',
            'wordpress-seo' => '3.4',
            'acf-content-analysis-for-yoast-seo' => '2.8.1',
        ];

        require_once Config::locate_composer_autoloader();

        $dir = $this->wp_content;

        foreach($plugins as $slug => $version) {
            $package = new \Composer\Package\CompletePackage($slug, $version, $version);
            $package->setType('wordpress-plugin');

            $config = new Config();
            $config->set_languages($languages);
            $config->set_wp_content_path($dir);

            $t10ns = new T10ns($package, $config);

            $this->assertInternalType('array', $t10ns->get_languages());
            $this->assertNotEmpty($t10ns->get_languages());

            $this->assertEquals($dir . '/languages/plugins', $t10ns->get_dest_path());

            $result = $t10ns->fetch_all_t10ns();
            $this->assertInternalType('array', $result);
            $this->assertNotEmpty($result);

            $this->assertFileExists($t10ns->get_dest_path());

            foreach ($languages as $language) {
                $this->assertFileExists($t10ns->get_dest_path() . '/redirection-' . $language . '.mo');
                $this->assertFileExists($t10ns->get_dest_path() . '/redirection-' . $language . '.po');

            }
        }
	}
}
