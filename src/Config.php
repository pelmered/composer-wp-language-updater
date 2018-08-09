<?php


namespace AngryCreative\WPLanguageUpdater;

class Config {

	/**
	 * Confugred languages for WP installation
	 *
	 * @var array
	 */
	protected $languages = [];

	/**
	 * Path to wp-content directory
	 *
	 * @var string
	 */
	protected $wp_content_path = '';

	public function __construct( $languages = [], $wp_content_path = null ) {
		$this->set_languages( $languages );
	}

	public function is_valid() {
		return ( ! empty( $this->languages ) && ! empty( $this->wp_content_path ) );
	}

	/**
	 * @return array
	 */
	public function get_languages(): array {
		return $this->languages;
	}

	/**
	 * @param array $languages
	 *
	 * @return Config
	 */
	public function set_languages( array $languages ): Config {
		$this->languages = $languages;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_wp_content_path(): string {
		return $this->wp_content_path;
	}

	/**
	 * @param string $wp_content_path
	 *
	 * @return Config
	 */
	public function set_wp_content_path( string $wp_content_path ): Config {
		$this->wp_content_path = $wp_content_path;

		return $this;
	}

	/**
	 * @param null $start_dir   Directory to looking upwards start from.
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
			$folder_names = array_merge( [ $folder_name ], $folder_names );
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
	 * Looks for folder upwards in the directory tree.
	 *
	 * @param string $folder     Folder and/or filename name to look for.
	 * @param string $start_dir  Directory to looking upwards start from.
	 * @param int    $max_depth  Maximum number of directories to look upwards in.
	 *
	 * @return string       Path to file or folder.
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
