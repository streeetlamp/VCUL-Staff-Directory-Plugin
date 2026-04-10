# VCUL Staff Directory

A WordPress plugin for managing the VCUL staff directory.


## Shortcodes

Two shortcodes are provided by the plugin:

* `[vcul_directory]` - drops in the #app element and required js/css. 

## Running Tests

Tests require PHPUnit, the WP test library, and the PHPUnit Polyfills. The WP test library is expected at `/tmp/wordpress-tests-lib` by default (override with `WP_TESTS_DIR`).

```bash
composer install
WP_TESTS_PHPUNIT_POLYFILLS_PATH=vendor/yoast/phpunit-polyfills ./vendor/bin/phpunit
```

## WordPress REST API support

The `directory` post type and all its associated meta is available via the WordPress REST API at the `/wp-json/vcul-directory/v1/get-directory` endpoint.
