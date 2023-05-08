# Composer Auto Language Updates

## This package is abandoned. Use this package instead: [inpsyde/wp-translation-downloader](https://github.com/inpsyde/wp-translation-downloader).



This package will automatically update translations for WordPress core, themes & plugins when you install or update them via composer.*

This is a fork by the unmaintained [package](https://github.com/Angrycreative/composer-plugin-language-update) by Angry Creative.


*\* This only works if the translations are available via the WordPress API.*

## Installation instructions

#### 1. Require the package.

Run `composer require pelmered/composer-plugin-language-update`.

#### 2. Define the languages used on your site and the path to your wp-content directory.
 
 This can be done by adding the following parameters to the extras object in your sites' main `composer.json` file.

```json
"extra": {
  "wordpress-languages": [ "en_GB", "sv_SE", "da_DK" ],
  "wordpress-content-dir": "public/wp-content"
 }
``` 

(We need to add a list of locales manually as this operation cannot rely on having a connection to the database available).

#### 3. Add the required composer install hooks.

Add the following lines to the `scripts` section of your `composer.json`.

```json
"scripts": {
  "post-install-cmd": "@wp-language-update",
  "post-update-cmd": "@wp-language-update",
  "wp-language-update": [
    "AngryCreative\\WPLanguageUpdater\\PostUpdateLanguageUpdate::update_t10ns"
  ],
  "post-package-uninstall": "AngryCreative\\WPLanguageUpdater\\PostUpdateLanguageUpdate::delete_t10ns"
}
```

That's it. Next time you run a `composer update|install` the translations for the relevant packages will be installed automatically.

### Tests

If you're testing, this package must be installed as a part of WordPress installation. You should ideally remove the entire `wp-content/languages` directory, so as to make sure the package behaves as expected.

Obviously you should probably do this on seperate branch, so you don't remove t10ns accidentaly when you run the tests.

`cd` into the packagage directory and run `composer test`.

You **may** need to run the tests as root to avoid permissions errors when creating the directories.

### WTF?

#### I can haz missing translation plz?

This only works if the t10ns are found on the WordPress API, eg. https://api.wordpress.org/translations/plugins/1.0/?slug=redirection&version=2.7.3

#### I can haz missing feature plz?

Sure thing! This is GitHub so just make us a pull request and we'll work together on making that happen.
