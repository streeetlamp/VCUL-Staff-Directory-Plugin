# Codebase Notes

Quick observations from a once-over of the plugin. Not exhaustive — just things worth knowing.

## Security

**REST API has no access control.** All four endpoints use `'permission_callback' => '__return_true'` (`rest-api.php:447,458,469,481`). Anyone can hit them unauthenticated from anywhere.

**The privacy model is spoofable.** The `privacy_check()` function (`plugin.php:54-62`) gates "internal" data (phone, photo) on the `HTTP_SEC_FETCH_SITE` request header. That header is trivially faked. It's not a real access control — it just filters data for honest browsers making cross-origin requests.

**Post meta is saved unsanitized despite having sanitize callbacks.** `directory-post-type.php:523` checks whether a `sanitize_callback` is defined... and then saves the raw `$_POST[$key]` anyway. The callback is never invoked. The nonce check on save is correct but the actual sanitization is broken.

**SSRF via guides field.** `rest-api.php:371-387` does `wp_remote_get($guides)` where `$guides` is contributor-controlled post meta. No URL validation, no domain whitelist. The response body comes back in the API response directly.

## Code Issues

**Version mismatch.** The plugin header says `Version: 1.0.13`; `plugin_version()` returns `'0.0.10'`. These are used for cache-busting asset URLs so it matters.

**Namespace inconsistency.** `rest-api.php:3` declares `namespace VCUL\Plugin\Directory` while every other file uses `namespace VCUL\Directory\*`. Functionally fine since the class is instantiated directly, but confusing.

**`sanitize_js()` doesn't sanitize.** `directory-post-type.php:141-144` base64-encodes the guides value and registers it as a `sanitize_callback`. Base64 is encoding, not sanitization.

**Undefined variable in loops.** In `get_department()` (`rest-api.php:~211`), `$department_list` is only ever assigned inside the loop body. If the query returns nothing, the variable is undefined when it's returned. Same pattern exists in `get_experts()`.

**`reverseName()`** (`rest-api.php:5-11`) reverses hyphen-separated slug parts (`john-smith` → `smith-john`) for a `flipped_slug` field. It's undocumented and the purpose isn't obvious from the code.

## Tests

The test suite is a placeholder. `tests/test-vcul-directory.php` has one test: `$this->assertTrue(true)`. Nothing is actually tested.

## Build & Dependencies

Two build systems coexist — legacy Grunt and `@wordpress/scripts`. The Grunt pipeline handles CSS/JS linting and minification of files in `js/` and `css/`; wp-scripts handles `assets/src/index.js → assets/dist/`. It's unclear whether `js/directory.min.js` is built from the `assets/` source or is a separately maintained artifact.

npm dependencies are circa 2018 (`autoprefixer@^8`, `stylelint@^9`). Composer has `wpcs@0.14.1` (current WPCS is 3.x with a different package name). These likely don't run cleanly against current tooling.

## Minor

- The anonymous headshot fallback image (`img/anon_headshot.jpg`) is hardcoded throughout `rest-api.php` but doesn't appear to exist in the repo — would 404 in production when a photo is hidden.
- The settings page (`directory-settings.php`) stores an org chart PDF URL. It's returned by the `get_settings` endpoint but not validated or used anywhere else in PHP — effectively just a config passthrough.
- `esc_attr()` is used on REST API response fields throughout `rest-api.php`. It's meant for HTML attribute context, not JSON; in JSON it's harmless but semantically wrong.
