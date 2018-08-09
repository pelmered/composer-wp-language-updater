<?php

namespace AngryCreative\WPLanguageUpdater;

use Composer\Package\PackageInterface;
use Composer\Script\Event;

/**
 * Class PostUpdateLanguageUpdate
 *
 * @todo Handle removal of Plugins and/or Themes
 *
 * @package AngryCreative\WPLanguageUpdater
 */
class PostUpdateLanguageUpdate {

    /**
     * Composer Event object
     *
     * @var \Composer\Script\Event
     */
    protected static $event;

	/**
	 * Update t10ns when a composer is run
	 *
	 * @param \Composer\Script\Event $event Composer Event object.
	 */
	public static function update_t10ns( Event $event ) {
	    self::$event = $event;

        $packages = self::$event->getComposer()
                                ->getRepositoryManager()
                                ->getLocalRepository()
                                ->getPackages();


        self::$event->getIO()->write('Checking for translations...');

        try {
			self::require_autoloader();
			$config = self::set_config();

            foreach($packages as $package) {
                static::update_package_t10ns( $package, $config );
            }

		} catch ( \Exception $e ) {
			self::$event->getIO()->writeError( $e->getMessage() );
		}


        die();
	}

	/**
	 * Remove t10ns on uninstall.
	 *
	 * @param \Composer\Script\Event $event
	 *
	 * @todo maybe implement this?
	 */
	public static function remove_t10ns( Event $event ) {
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

        $config = new Config();

		$extra = self::$event->getComposer()->getPackage()->getExtra();

		if ( ! empty( $extra['wordpress-languages'] ) ) {
			$config->set_languages( $extra['wordpress-languages'] );
		}

		$wp_content_dir_name = empty( $extra['wordpress-content-dir'] ) ?
			null :
			$extra['wordpress-content-dir'];

		// For backwards compatibility.
		if ( ! $wp_content_dir_name && !empty( $extra['wordpress-path-to-content-dir'] ) ) {
			$wp_content_dir_name = $extra['wordpress-path-to-content-dir'];

			trigger_error(
				'Using the extra "wordpress-path-to-content-dir" option is deprecated.
                You should rename it to "wordpress-content-dir".',
				E_USER_DEPRECATED
			);
		}

        $config->set_wp_content_path( Config::locate_wp_content( $wp_content_dir_name ) );


		if ( !$config->is_valid() ) {
			throw new \Exception( 'Oops :( Did you forget to add the wordpress-langagues or path to content dir to the extra section of your composer.json?' );
		}

		return $config;
	}

	/**
	 * Update translations for package
	 *
	 * @param string $package_type  Package type. Plugin, Theme or Core.
	 * @param string $version       Package version.
	 * @param string $slug          Package slug.
	 */
	protected static function update_package_t10ns( PackageInterface $package, Config $config ) {
		try {
			$t10ns   = new T10ns( $package, $config );

			if( $t10ns->is_wp_package() ) {
                $results = $t10ns->fetch_all_t10ns();

                if ( empty( $results ) ) {
                    self::$event->getIO()->write(
                        sprintf('%s: No new translation updates found.', $t10ns->get_slug())
                    );

                } else {
                    self::$event->getIO()->write(
                        sprintf('%s: Updated translations: %s', $t10ns->get_slug(), implode(', ', $results) )
                    );
                }
            }
        } catch ( \Exception $e ) {
            self::$event->getIO()->writeError( $e->getMessage() );
        }
	}


}
