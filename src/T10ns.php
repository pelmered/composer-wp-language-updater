<?php

namespace AngryCreative\WPLanguageUpdater;

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
	 * The package type.
	 *
	 * @var string
	 */
	protected $package_type = '';

	/**
	 * Theme slug, eg 'twenty-seventeen'.
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
	 * Array of languages availale on the current site.
	 *
	 * @var array
	 */
	protected $languages = [];

	/**
	 * Path to the wp-content directory.
	 *
	 * @var string
	 */
	protected $wp_content_path;

	/**
	 * A list of available t10s.
	 *
	 * @var array
	 */
	protected $t10ns = [];

	/**
	 * T10ns constructor.
	 *
	 * @param string       $package_type    Package type. Plugin, Theme or Core.
	 * @param string       $slug            Package slug.
	 * @param float|string $version         Package version.
	 * @param array        $languages       Array of languages.
	 * @param string       $wp_content_path Path to wp-content.
	 *
	 * @throws \Exception
	 */
	public function __construct( $package_type, $slug, $version = '', array $languages, $wp_content_path ) {
		$this->package_type    = $package_type;
		$this->slug            = $slug;
		$this->version         = $version;
		$this->languages       = $languages;
		$this->wp_content_path = $wp_content_path;

		try {
			$this->t10ns = $this->get_available_t10ns( $this->get_api_url(), $this->version, $this->slug );
		} catch ( \Exception $e ) {
			throw new \Exception( $e->getMessage() );
		}
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
	 * Get Languages.
	 *
	 * @return array    Array of languages.
	 */
	public function get_languages() : array {
		return $this->languages;
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
		$results = [];

		foreach ( $this->languages as $language ) {
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
		foreach ( $this->t10ns as $t10n ) {
			if ( $t10n->language !== $language ) {
				continue;
			}

			try {
				$this->download_and_move_t10ns( $this->package_type, $t10n->package, $this->wp_content_path );
				$has_updated = true;

			} catch ( \Exception $e ) {
				throw new \Exception( $e->getMessage() );

			}
		}

		return $has_updated;
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
	protected function get_available_t10ns( $api_url, $version = null, $slug = null ) : array {
		$query = [];

		if ( ! empty( $version ) ) {
			$query['version'] = $version;
		}

		if ( ! empty( $slug ) ) {
			$query['slug'] = $slug;
		}

		$client   = new Client();
		$response = $client->request(
			'GET', $api_url, [
				'query' => $query,
			]
		);

		if ( 200 !== $response->getStatusCode() ) {
			throw new \Exception( 'Got status code ' . $response->getStatusCode() );
		}

		$body = json_decode( $response->getBody() );

		if ( empty( $body->translations ) ) {
			throw new \Exception( 'No t10ns found' );
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
	public function get_dest_path( $type = 'plugin' ) : string {
		$dest_path = $this->wp_content_path . '/languages';

		if ( ! file_exists( $dest_path ) ) {
			$result = mkdir( $dest_path, 0775 );
			if ( ! $result ) {
				throw new \Exception( 'Failed to create directory at: ' . $dest_path );
			}
		}

		$path = '';
		switch ( $type ) {
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

	/**
	 * Download t10ns, unzip them and move to the relevant directory.
	 *
	 * @param string $package_type    The package type, currently only 'plugin', 'theme', or 'core'.
	 * @param string $package_url     The URL for the package t10ns.
	 * @param string $wp_content_path The Path to the wp_content directory.
	 *
	 * @throws \Exception
	 */
	public function download_and_move_t10ns( $package_type = 'plugin', $package_url, $wp_content_path ) {
		try {
			$dest_path = $this->get_dest_path( $package_type, $wp_content_path );

			try {
				$t10n_files = $this->download_t10ns( $package_url );

				try {
					$this->unpack_and_more_archived_t10ns( $t10n_files, $dest_path );

				} catch ( \Exception $e ) {
					throw new \Exception( $e->getMessage() );
				}
			} catch ( \Exception $e ) {
				throw new \Exception( $e->getMessage() );
			}
		} catch ( \Exception $e ) {
			throw new \Exception( $e->getMessage() );
		}
	}

	public function remove_t10ns() {
	}

	/**
	 * @param null $start_dir   Directory to looking upwards start from
	 *
	 * @return string       Path to composer autoloader.
	 * @throws \Exception
	 */
	public static function locate_composer_autoloader( $start_dir = null ) {
		if ( ! $start_dir ) {
			$start_dir = dirname( __DIR__, 2 );
		}
		$location = 'vendor/autoload.php';

		try {
			$path = static::locate_path_to_folder( $location, $start_dir );
		} catch ( \Exception $ex ) {
		}

		if ( $path ) {
			return $path;
		}

		throw new \Exception( 'Failed to locate composer autoloader in tree' );
	}

	/**
	 * @param null $folder_name wp-content folder name.
	 * @param null $start_dir   Directory to looking upwards start from.
	 *
	 * @return string       Path to wp-content directory.
	 * @throws \Exception
	 */
	public static function locate_wp_content( $folder_name = null, $start_dir = null ) {
		if ( ! $start_dir ) {
			$start_dir = dirname( __DIR__, 2 );
		}

		$folder_names = [
			'wp-content',
			'app',
		];

		if ( $folder_name ) {
			$folder_names = array_merge( $folder_name, $folder_names );
		}

		foreach ( $folder_names as $location ) {
			try {
				$path = static::locate_path_to_folder( $location, $start_dir );
			} catch ( \Exception $ex ) {
				continue;
			}

			if ( $path && file_exists( $path . '/plugins' ) && file_exists( $path . '/themes' ) ) {
				return $path;
			}
		}

		throw new \Exception( 'Failed to locate WP content directory in tree' );
	}

	/**
	 * Looks for folder upwards in the directory tree
	 *
	 * @param string $folder     Folder and/or filename name to look for.
	 * @param string $start_dir  Directory to looking upwards start from.
	 * @param int    $max_depth  Maximum number of directories to look upwards in.
	 *
	 * @return string       Path to file or folder
	 * @throws \Exception
	 */
	public static function locate_path_to_folder( string $folder, $start_dir = __DIR__, $max_depth = 10 ) {
		$path = $start_dir;

		for ( $i = 0; $i < $max_depth; $i++ ) {

			$p = $path . '/' . $folder;

			if ( file_exists( $p ) ) {

				return $p;
			}
			$path = \dirname( $path );
		}

		throw new \Exception( 'Failed to locate directory in tree: ' . $folder );
	}

}
