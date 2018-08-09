<?php

namespace AngryCreative\WPLanguageUpdater;

use Composer\Package\PackageInterface;
use GuzzleHttp\Client;

/**
 * Class T10ns
 *
 * @package AngryCreative\WPLanguageUpdater
 */
class T10ns {

	/**
	 * Theme t10ns API url.
	 *
	 * @var string Plugin t10ns API url.
	 */
	protected $api_url = 'https://api.wordpress.org/translations/%s/1.0/';

	/**
	 * The package object.
	 *
	 * @var string
	 */
	protected $package = null;

	/**
	 * The package type.
	 *
	 * @var bool
	 */
	protected $is_wp_package = true;

	/**
	 * The package type.
	 *
	 * @var string
	 */
	protected $package_type = '';

	/**
	 * Package name, eg 'twenty-seventeen'.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Package slug, eg 'twenty-seventeen'.
	 *
	 * @var string
	 */
	protected $slug;

	/**
	 * Theme version.
	 *
	 * @var float|string
	 */
	protected $version;

	/**
	 * Config object
	 *
	 * @var \AngryCreative\WPLanguageUpdater\Config
	 */
	protected $config;

	/**
	 * A list of available t10s.
	 *
	 * @var array
	 */
	protected $t10ns = [];

	/**
	 * T10ns constructor.
	 *
	 * @param PackageInterface $package    Composer package object.
	 * @param Config           $config     Config object.
	 *
	 * @throws \Exception
	 */
	public function __construct( PackageInterface $package, Config $config ) {

		$this->package = $package;
		$valid_wp_type = $this->extract_package_data( $package );
		$this->config  = $config;
	}

	/**
	 * Extract data from Package object.
	 *
	 * @param $package
	 *
	 * @return bool
	 */
	public function extract_package_data( $package ) {
		$this->version = $package->getVersion();

		switch ( $package->getType() ) {
			case 'wordpress-plugin':
				$this->package_type = 'plugin';
				$this->slug         = $this->extract_slug( $package->getName(), $this->package_type );
				break;

			case 'wordpress-theme':
				$this->package_type = 'theme';
				$this->slug         = $this->extract_slug( $package->getName(), $this->package_type );
				break;

			case 'package':
				if ( 'johnpbloch/wordpress' === $package->getName() ) {
					$this->package_type = 'core';
					$this->slug         = 'wordpress-core';
					break;
				}
				$this->is_wp_package = false;
				return false;
			default:
				$this->is_wp_package = false;
				return false;
		}

		return true;
	}

	protected function get_nice_name( $name, $package_type ) {
		return implode( ' ', array_map( 'ucfirst', explode( '-', $this->extract_slug( $name, $package_type ) ) ) );
	}

	protected function extract_slug( $slug, $package_type ) {
		// Strip wpackagist vendor name
		// return substr($slug, strpos($slug, '/') + 1);
		return str_replace( 'wpackagist-' . $package_type . '/', '', $slug );
	}


	public function is_wp_package() {
		return $this->is_wp_package;
	}

	/**
	 * Get WordPress API URL.
	 *
	 * @return string   WordPress API URL.
	 */
	public function get_api_url() {

		$type = $this->package_type;
		if ( 'plugin' === $this->package_type ) {
			$type = 'plugins';
		}
		if ( 'theme' === $this->package_type ) {
			$type = 'themes';
		}

		return \sprintf( $this->api_url, $type );
	}

	/**
	 * Get slug.
	 *
	 * @return array    Package slug.
	 */
	public function get_slug() : string {
		return $this->slug;
	}

	/**
	 * Get Languages.
	 *
	 * @return array    Array of languages.
	 */
	public function get_languages() : array {
		return $this->config->get_languages();
	}

	/**
	 * Get Languages.
	 *
	 * @return array    Array of languages.
	 */
	public function get_wp_content_path() : string {
		return $this->config->get_wp_content_path();
	}

	/**
	 * Get translations.
	 *
	 * @return array    Array of translations.
	 */
	public function get_t10ns() : array {
		return $this->t10ns;
	}

	/**
	 * Fetch all available t10ns for a plugin.
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function fetch_all_t10ns() : array {

		try {
			$this->t10ns = $this->get_available_t10ns();
		} catch ( \Exception $e ) {
			throw new \Exception( $e->getMessage() );
		}

		$results = [];

		foreach ( $this->get_languages() as $language ) {
			try {
				$result = $this->fetch_t10ns_for_language( $language );
				if ( $result ) {
					$results[] = $language;
				}
			} catch ( \Exception $e ) {
				// Maybe we should do something here?!
			}
		}

		return $results;
	}

	/**
	 * Fetch and move a plugins' t10ns to the correct
	 * directory.
	 *
	 * @param string $language Eg. 'sv_SE'.
	 *
	 * @throws \Exception
	 * @return bool True if the t10ns could be downloaded, or false.
	 */
	protected function fetch_t10ns_for_language( $language ) : bool {
		$has_updated = false;
		$dest_path   = $this->get_dest_path();
		foreach ( $this->t10ns as $t10n ) {

			if ( $t10n->language !== $language ) {
				continue;
			}

			try {

				// TDO: check all files in zip bundle (for example core contains several files)
				$file_name = ( $this->package_type !== 'core' ? $this->get_slug() . '-' : '' ) . $language . '.mo';

				$file_path = $dest_path . '/' . $file_name;

				$file_hash = '';
				if ( file_exists( $dest_path . '/' . $file_name ) ) {
					$file_hash = md5_file( $file_path );
				}

				$this->download_and_move_t10ns( $t10n->package );

				if ( $file_hash !== md5_file( $file_path ) ) {
					$has_updated = true;
				}
			} catch ( \Exception $e ) {
				throw new \Exception( $e->getMessage() );
			}
		}

		return $has_updated;
	}


	protected function is_changed( $file1, $file2 ) {
		return md5_file( $file1 ) === md5_file( $file2 );
	}

	/**
	 * Get a list of available t10ns from the API.
	 *
	 * @param string      $api_url URL to the API for the package type.
	 * @param string|null $version Package version.
	 * @param string|null $slug Theme/Plugin slug.
	 *
	 * @throws \Exception
	 * @return array
	 */
	protected function get_available_t10ns() : array {

		$query = [];

		if ( ! empty( $this->version ) ) {
			$query['version'] = $this->version;
		}

		if ( ! empty( $this->slug ) && 'core' !== $this->package_type ) {
			$query['slug'] = $this->slug;
		}

		$client   = new Client();
		$response = $client->request(
			'GET', $this->get_api_url(), [
				'query' => $query,
			]
		);

		if ( 200 !== $response->getStatusCode() ) {
			throw new \Exception( 'Got status code ' . $response->getStatusCode() );
		}

		$body = json_decode( $response->getBody() );

		if ( empty( $body->translations ) ) {
			throw new \Exception( sprintf( '%s: No translations available.', $this->get_slug() ) );
		}

		return $body->translations;
	}

	/**
	 * Get the destination path for a type of object: either
	 * 'plugin', 'theme' or 'core'.
	 *
	 * This will also create the directory if if doesn't exist.
	 *
	 * @param string $type            The object type.
	 *
	 * @throws \Exception
	 * @return string path to the destination directory.
	 */
	public function get_dest_path() : string {
		$dest_path = $this->get_wp_content_path() . '/languages';

		if ( ! file_exists( $dest_path ) ) {
			$result = mkdir( $dest_path, 0775 );
			if ( ! $result ) {
				throw new \Exception( 'Failed to create directory at: ' . $dest_path );
			}
		}

		$path = '';
		switch ( $this->package_type ) {
			case 'plugin':
				$path = '/plugins';
				break;

			case 'theme':
				$path = '/themes';
				break;
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
	 * @param string $url the URL to the zipped t10ns.
	 *
	 * @throws \Exception
	 * @return string Path to the downloaded files.
	 */
	public function download_t10ns( $url ) : string {
		$client   = new Client();
		$tmp_name = sys_get_temp_dir() . '/' . basename( $url );
		$request  = $client->request(
			'GET', $url, [
				'sink' => $tmp_name,
			]
		);

		if ( 200 !== $request->getStatusCode() ) {
			throw new \Exception( 'T10ns not found' );
		}

		return $tmp_name;
	}

	/**
	 * Unpack the downloaded t10ns and move to the correct path.
	 *
	 * @param string $t10n_files Path to the zipped t10n files.
	 * @param string $dest_path  Path to expand the zipped files to.
	 *
	 * @throws \Exception
	 */
	public function unpack_and_move_archived_t10ns( $t10n_files, $dest_path ) {
		$zip = new \ZipArchive();

		// var_dump($t10n_files);
		if ( true === $zip->open( $t10n_files ) ) {
			for ( $i = 0; $i < $zip->numFiles; $i++ ) {

				/*
			    var_dump($i);
			    var_dump(basename($i));
			    */
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

	/**
	 * Download t10ns, unzip them and move to the relevant directory.
	 *
	 * @param string $package_type    The package type, currently only 'plugin', 'theme', or 'core'.
	 * @param string $package_url     The URL for the package t10ns.
	 * @param string $wp_content_path The Path to the wp_content directory.
	 *
	 * @throws \Exception
	 */
	public function download_and_move_t10ns( $package_url ) {
		try {
			$dest_path = $this->get_dest_path();

			$t10n_files = $this->download_t10ns( $package_url );

			$this->unpack_and_move_archived_t10ns( $t10n_files, $dest_path );

		} catch ( \Exception $e ) {
			// throw new \Exception( $e->getMessage() );
		}
	}

	/**
	 * TODO
	 */
	public function remove_t10ns() {
	}

}
