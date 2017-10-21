<?php
/**
 * Created by PhpStorm.
 * User: richardsweeney
 * Date: 2017-10-15
 * Time: 15:49
 */

namespace AngryCreative;

use GuzzleHttp\Client;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

abstract class T10ns {

	/**
	 * @return array
	 */
	abstract protected function get_available_t10ns() : array;

	/**
	 * @return array
	 */
	abstract public function fetch_t10ns() : array;

	/**
	 * Get the common.yml configuration file.
	 *
	 * @return bool|string
	 */
	protected function get_site_language_config_file() {
		return file_get_contents( dirname( dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) ) . '/conf/arguments/common.yml' );
	}

	/**
	 * Get a list of languages on the site.
	 *
	 * @throws \Exception
	 * @return false|array
	 */
	public function get_site_languages() {
		$config_file = $this->get_site_language_config_file();
		if ( ! $config_file ) {
			throw new \Exception( 'Config file not found.' );
		}

		try {
			$config = Yaml::parse( $config_file );
		} catch ( ParseException $e ) {
			throw new \Exception( $e->getMessage() );
		}

		if ( empty( $config['languages'] ) ) {
			throw new \Exception( 'Languages not configured in common.yml' );
		}

		return $config['languages'];
	}

	/**
	 * Get the destination path for a type of object: either
	 * 'plugin', 'theme' or 'core'.
	 *
	 * This will also create the directory if if doesn't exist.
	 *
	 * @param string $type The object type.
	 *
	 * @return string path to the destination directory.
	 * @throws \Exception
	 */
	public function get_dest_path( $type = 'plugin' ) {
		$dest_path = dirname( dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) ) . '/public/wp-content/languages';

		if ( ! file_exists( $dest_path ) ) {
			$result = mkdir( $dest_path, 0775 );
			if ( ! $result ) {
				throw new \Exception( 'Failed to create directory at: ' . $dest_path );
			}
		}

		switch ( $type ) {
			case 'plugin' :
				$path = '/plugins';
				break;

			case 'theme' :
				$path = '/themes';
				break;

			default :
				$path = '';
		}

		$dest_path .= $path;

		if ( ! file_exists( $dest_path ) ) {
			$result = mkdir( $dest_path, 0775 );
			if ( ! $result ) {
				throw new \Exception( 'Failed to create directory at: ' . $dest_path );
			}
		}

		return $dest_path;
	}

	/**
	 * Download a zipped file of t10ns.
	 *
	 * @param $url
	 *
	 * @throws \Exception
	 * @return string Path to the downloaded files.
	 */
	public function download_t10ns( $url ) {
		$client   = new Client();
		$tmp_name = sys_get_temp_dir() . '/' . basename( $url );
		$request  = $client->request( 'GET', $url, [
			'sink' => $tmp_name,
		] );

		if ( 200 !== $request->getStatusCode() ) {
			throw new \Exception( 'T10ns not found' );
		}

		return $tmp_name;
	}

	/**
	 * @param string $t10n_files Path to the zipped t10n files.
	 * @param string $dest_path  Path to expand the zipped files to.
	 *
	 * @throws \Exception
	 */
	public function unpack_and_more_archived_t10ns( $t10n_files, $dest_path ) {
		$zip = new \ZipArchive();

		if ( true === $zip->open( $t10n_files ) ) {
			for ( $i = 0; $i < $zip->numFiles; $i++ ) {
				$ok = $zip->extractTo( $dest_path, [ $zip->getNameIndex( $i ) ] );
				if ( false === $ok ) {
					throw new \Exception( 'There was an error moving the translation to the destination directory' );
				}
			}
			$zip->close();

		} else {
			throw new \Exception( 'The was an error unzipping or moving the t10n files' );

		}
	}

}
