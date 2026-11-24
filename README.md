> Fork of [ClassicPress](https://github.com/ClassicPress/ClassicPress) at 9e36410. Modern white-label admin lives in `src/wp-content/mu-plugins/cms*` (CSS + hooks, no core edits). Update core by re-copying upstream.

## Admin UI

- Admin UI: `src/wp-content/mu-plugins/cms.php` + `cms/` (no core edits).
- Languages: Polish (default on install) and English. Users switch in *Profile → Language* or on the login screen. Packs: `src/wp-content/languages/` (WordPress 6.2 `pl_PL`).
- Clean URLs: `/admin`, `/login`, `/content`, `/includes`, `/api` instead of `wp-*`. Direct `wp-admin` / `wp-login.php` requests return 404.
  Enable in `wp-config.php` **after** the server rewrites are in place:

  ```php
  define( 'CMS_HIDE_PATHS', true );
  ```

  Apache: rules are added to `.htaccess` automatically (Settings → Permalinks → Save).

  nginx:

  ```nginx
  location = /admin { return 301 /admin/; }
  rewrite ^/admin/(.*)$ /wp-admin/$1 last;
  rewrite ^/login/?$ /wp-login.php last;
  rewrite ^/content/(.*)$ /wp-content/$1 last;
  rewrite ^/includes/(.*)$ /wp-includes/$1 last;
  ```

  Local dev: `php -S 127.0.0.1:8080 -t src tools/dev-router.php`

<a href="https://www.classicpress.net"><img src="src/wp-admin/images/classicpress-logo.png" height="63"></a>

**ClassicPress: The CMS for Creators. Stable. Lightweight. Instantly Familiar.**

ClassicPress is a community-led open source content management system for creators. It is a fork of WordPress 6.2 that preserves the TinyMCE classic editor as the default option. It is half the size of WordPress, contains less bloat improving performance, and has no block editor (Gutenberg/Full Site Editing).

[![Coding Standards](https://github.com/ClassicPress/ClassicPress/actions/workflows/coding-standards.yml/badge.svg)](https://github.com/ClassicPress/ClassicPress/actions/workflows/coding-standards.yml)
[![PHPUnit Tests](https://github.com/ClassicPress/ClassicPress/actions/workflows/phpunit-tests.yml/badge.svg)](https://github.com/ClassicPress/ClassicPress/actions/workflows/phpunit-tests.yml)
[![JavaScript Tests](https://github.com/ClassicPress/ClassicPress/actions/workflows/javascript-tests.yml/badge.svg)](https://github.com/ClassicPress/ClassicPress/actions/workflows/javascript-tests.yml)
[![PHP Compatibility](https://github.com/ClassicPress/ClassicPress/actions/workflows/php-compatibility.yml/badge.svg)](https://github.com/ClassicPress/ClassicPress/actions/workflows/php-compatibility.yml)
[![Financial Contributors](https://opencollective.com/classicpress/tiers/badge.svg?label=Financial+Contributors)](https://opencollective.com/classicpress)

For more information, see:

- [The official ClassicPress website ›](https://www.classicpress.net/)
- [The ClassicPress documentation ›](https://docs.classicpress.net/)
- [The ClassicPress governance ›](https://www.classicpress.net/governance/)
- [Suggest features ›](https://github.com/ClassicPress/ClassicPress/issues/)

## Contributions
This project exists thanks to all the people who contribute and who have contributed in the past, whether as part of the long history of thousands of contributions to WordPress from many different people, or as contributions to ClassicPress itself.

Would you like to help? [Here is how you can start ›](https://github.com/ClassicPress/ClassicPress/blob/develop/.github/CONTRIBUTING.md)

## Sponsors
Corporate sponsors that believe in ClassicPress. [Become a sponsor ›](https://opencollective.com/classicpress/contribute)

All donations are tax-deductible in the United States.

[![Brinkhost IT](https://www.classicpress.net/wp-content/uploads/2022/07/brinkman-it-sponsor.png)](https://www.brinkhost.nl)
[![Tukutoi](https://www.classicpress.net/wp-content/uploads/2022/07/tukutoi-sponsor.png)](https://www.tukutoi.com)

## Financial Contributors
Support the ClassicPress project by making a [donation ›](https://opencollective.com/classicpress/contribute)

All donations are tax-deductible in the United States.

[![Financial contributors](https://opencollective.com/classicpress/individuals.svg?width=890)](https://opencollective.com/classicpress)
