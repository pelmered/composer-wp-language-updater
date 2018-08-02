<?php

namespace AngryCreative\WPLanguageUpdater;

use Composer\Installer\PackageEvent;
use Composer\Package\PackageInterface;

/**
 * Class PostUpdateLanguageUpdate
 *
 * @todo Handle removal of Plugins and/or Themes
 *
 * @package AngryCreative\WPLanguageUpdater
 */
class PostUpdateLanguageUpdate {

	/**
	 * Confugred languages for WP installation
	 *
	 * @var array
	 */
	protected static $languages = [];

	/**
	 * Path to wp-content directory
	 *
	 * @var string
	 */
	protected static $wp_content_path = '';

	/**
	 * Composer PackageEvent object
	 *
	 * @var PackageEvent
	 */
	protected static $event;


	/**
	 * Update t10ns when a package is installed
	 *
	 * @param PackageEvent $event Composer PackageEvent object.
	 */
	public static function install_t10ns( PackageEvent $event ) {
		self::$event = $event;

		try {
			self::require_autoloader();
			self::set_config();
			self::get_t10ns_for_package( self::$event->getOperation()->getPackage() );

		} catch ( \Exception $e ) {
			self::$event->getIO()->writeError( $e->getMessage() );
		}
	}

	/**
	 * Update t10ns when a package is updated
	 *
	 * @param PackageEvent $event Composer PackageEvent object.
	 */
	public static function update_t10ns( PackageEvent $event ) {
		self::$event = $event;

		try {
			self::require_autoloader();
			self::set_config();
			self::get_t10ns_for_package( self::$event->getOperation()->getTargetPackage() );

		} catch ( \Exception $e ) {
			self::$event->getIO()->writeError( $e->getMessage() );
		}
	}

	/**
	 * Remove t10ns on uninstall.
	 *
	 * @param PackageEvent $event
	 *
	 * @todo maybe implement this?
	 */
	public static function remove_t10ns( PackageEvent $event ) {
		self::$event = $event;
		exit;
	}

	/**
	 * Require composer autoloader
	 */
	protected static function require_autoloader() {
		$vendor_dir = self::$event->getComposer()->getConfig()->get( 'vendor-dir' );
		require_once $vendor_dir . '/autoload.php';
	}

	/**
	 * Set the config
	 *
	 * @throws \Exception
	 */
	protected static function set_config() {
		$extra = self::$event->getComposer()->getPackage()->getExtra();

		if ( ! empty( $extra['wordpress-languages'] ) ) {
			self::$languages = $extra['wordpress-languages'];
		}

		$wp_content_dir_name = empty( $extra['wordpress-content-dir'] ) ?
			null :
			$extra['wordpress-content-dir'];

		// For backwards compatibility.
		if ( ! $wp_content_dir_name && empty( $extra['wordpress-path-to-content-dir'] ) ) {
			$wp_content_dir_name = $extra['wordpress-path-to-content-dir'];

			trigger_error(
				'Using the extra "wordpress-path-to-content-dir" option is deprecated.
                You should rename it to "wordpress-content-dir".',
				E_USER_DEPRECATED
			);
		}

		self::$wp_content_path = static::locate_wp_content( $wp_content_dir_name );

		if ( empty( self::$languages ) || empty( self::$wp_content_path ) ) {
			throw new \Exception( 'Oops :( Did you forget to add the wordpress-langagues or path to content dir to the extra section of your composer.json?' );
		}
	}


	/**
	 * Get t10ns for a package, where applicable.
	 *
	 * @param PackageInterface $package Composer PackageInterface object.
	 */
	protected static function get_t10ns_for_package( PackageInterface $package ) {
		switch ( $package->getType() ) {
			case 'wordpress-plugin':
				$package_type = 'plugin';
				$slug         = str_replace( 'wpackagist-plugin/', '', $package->getName() );
				break;

			case 'wordpress-theme':
				$package_type = 'theme';
				$slug         = str_replace( 'wpackagist-theme/', '', $package->getName() );

				break;

			case 'package':
				if ( 'johnpbloch/wordpress' === $package->getName() ) {
					$package_type = 'core';
				}
				break;
			default:
				return;
		}

		self::update_package_t10ns( $package_type, $package->getVersion(), $slug );
	}


	/**
	 * Update translations for package
	 *
	 * @param string $package_type  Package type. Plugin, Theme or Core.
	 * @param string $version       Package version.
	 * @param string $slug          Package slug.
	 */
	protected static function update_package_t10ns( $package_type, $version, $slug = '' ) {
		try {
			$t10ns   = new T10ns( $package_type, $slug, $version, self::$languages, self::$wp_content_path );
			$results = $t10ns->fetch_all_t10ns();

			if ( empty( $results ) ) {
				self::$event->getIO()->write( "No translations updated for plugin: {$slug}" );

			} else {
				foreach ( $results as $result ) {
					self::$event->getIO()->write( "Updated translation {$result} for plugin: {$slug}" );
				}
			}
		} catch ( \Exception $e ) {
			self::$event->getIO()->writeError( $e->getMessage() );

		}
	}


}
