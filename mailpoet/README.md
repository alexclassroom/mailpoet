### Table of Contents

1. [MailPoet](#mailpoet)
2. [Setup](#setup)
   1. [Requirements](#requirements)
   2. [Installation](#installation)
   3. [Frameworks and libraries](#frameworks-and-libraries)
3. [Workflow Commands](#workflow-commands)
4. [Coding and Testing](#coding-and-testing)
   1. [DI](#di)
   2. [PHP-Scoper](#php-scoper)
   3. [Changelog](#changelog)
   4. [i18n](#i18n)
   5. [Acceptance testing](#acceptance-testing)

## MailPoet

- For help with product, visit [SUPPORT](../SUPPORT.md).
- To use the Docker-based development environment, see [monorepo README](../README.md).
- To use plugin code directly, follow instructions below.

## Setup

### Requirements

- PHP >= 7.4
- NodeJS
- WordPress

### Installation

The instructions below assume you already have a working WordPress development environment:

```bash
# 1. Clone this repository somewhere outside the WordPress installation:
git clone https://github.com/mailpoet/mailpoet.git

# 2. Go to the plugin directory within cloned the repository:
cd mailpoet/mailpoet

# 3. Symlink the MailPoet plugin to your WordPress installation:
ln -s $(pwd) <wordpress>/wp-content/plugins/mailpoet

# 4. Create the .env file:
cp .env.sample .env

# 5. Install dependencies (PHP and JS):
./do install

# 6. Compile JS and CSS:
./do compile:all
```

### Frameworks and libraries

- [Symfony/dependency-injection](https://github.com/symfony/dependency-injection) ([docs for 3.4](https://symfony.com/doc/3.4/components/dependency_injection.html)).
- [PHP-Scoper](https://github.com/humbug/php-scoper) for moving dependencies into MP namespace
- [Twig](https://twig.symfony.com/) and [Handlebars](https://handlebarsjs.com/) are used for templates rendering.
- [Monolog](https://seldaek.github.io/monolog/) is used for logging.
- [Robo](https://robo.li/) is used to write and run workflow commands.
- [Codeception](https://codeception.com/) is used to write unit and acceptance tests.
- [Docker](https://www.docker.com/), [Docker Compose](https://docs.docker.com/compose/) and [Selenium](https://www.seleniumhq.org/) to run acceptance tests.
- [React](https://reactjs.org/) is used to create most of UIs.
- [Marionette](https://marionettejs.com/) is used to build the newsletters editor.
- [SCSS](http://sass-lang.com/) is used to write styles.
- [Mocha](https://mochajs.org/), [Chai](https://www.chaijs.com/) and [Sinon](https://sinonjs.org/) are used to write Javascript tests.
- [ESLint](https://eslint.org/) is used to lint JS files.
- [Webpack](https://webpack.js.org/) is used to bundle assets.

## Workflow Commands

There are two different `./do` commands. One is in the free MailPoet directory and runs the commands on the local computer. The second is in the repository root and runs the commands in a Docker container.
Running `./do` commands in the repository root will run the command in the Docker container.

It is recommended to run the assets commands directly in the free MailPoet directory. That means installing the dependencies locally. Running the js and css compilation commands within the container is possible, but it is slower.

On the other hand, the tests should be run in the container. The container has all the necessary dependencies installed and configured. And there is a database running. See more details in the README.md in the repository root.

```bash
$ ./do install             # install PHP and JS dependencies
$ ./do update              # update PHP and JS dependencies

$ ./do compile:css         # compiles SCSS files into CSS.
$ ./do compile:js          # bundles JS files for the browser.
$ ./do compile:all         # compiles CSS and JS files.

$ ./do watch:css           # watch CSS files for changes and compile them.
$ ./do watch:js            # watch JS files for changes and compile them.

$ ./do test:unit [--file=...] [--debug]
  # runs the PHP unit tests.
  # if --file specified then only tests on that file are executed.
  # if --debug then tests are executed in debugging mode.
$ ./do test:integration [--file=...] [--multisite] [--debug]
  # runs the PHP integration tests.
  # if --file specified then only tests on that file are executed.
  # if --multisite then tests are executed in a multisite wordpress setup.
  # if --debug then tests are executed in debugging mode.
$ ./do test:multisite-integration # alias for ./do test:integration --multisite
$ ./do test:debug-unit            # alias for ./do test:unit --debug
$ ./do test:debug-integration     # alias for ./do test:integration --debug
$ ./do test:failed-unit           # run the last failing unit test.
$ ./do test:failed-integration    # run the last failing integration test.
$ ./do test:javascript            # run the JS tests.
$ ./do test:acceptance [--file=...] [--skip-deps]
  # run acceptances tests into a docker environment.
  # if --file given then only tests on that file are executed.
  # if --skip-deps then it skips installation of composer dependencies.
$ ./do test:acceptance-multisite [--file=...] [--skip-deps]
  # download 3rd party plugins for tests
  # if you pass tag it will attempt to download zip for the tag otherwise it downloads the latest release
  # e.g. ./do download:woo-commerce-zip 5.20.0
$ ./do download:woo-commerce-zip [<tag>]
$ ./do download:woo-commerce-subscriptions-zip [<tag>]
  # same as test:acceptance but runs into a multisite wordpress setup.
$ ./do delete:docker      # stop and remove all running docker containers.

$ ./do qa:lint             # PHP code linter.
$ ./do qa:lint-javascript  # JS code linter.
$ ./do qa:phpstan          # PHP code static analysis using PHPStan.
$ ./do qa                  # PHP and JS linters.

$ ./do release:changelog-get  [--version-name=...]     # Prints out changelog and release notes for given version or for newest version.
$ ./do release:changelog-update  [--version-name=...] [--quiet] # Updates changelog in readme.txt for given version or for newest version.
$ ./do changelog:add --type=<type> --description=<description> # Creates a new changelog entry
$ ./do changelog:preview [--version=<version>] # Preview compiled changelog for next version

$ ./do container:dump      # Generates DI container cache.

$ ./do generate:data [<generatorName>] [<threads>] # Generates random usage data (Note: requires WooCommerce active) e.g. ./do generate:data past_revenues 4
```

## Coding and Testing

### DI

We use Symfony/dependency-injection container. Container configuration can be found in `lib/DI/ContainerFactory.php`
The container is configured and used with minimum sub-dependencies to keep final package size small.
You can check [the docs](https://symfony.com/doc/3.4/components/dependency_injection.html) to learn more about Symfony Container.

### PHP-Scoper

We use PHP-Scoper package to prevent plugin libraries conflicts in PHP. Two plugins may be using different versions of a library. PHP-Scoper prefix dependencies namespaces and they are then moved into `vendor-prefixed` directory.
Dependencies handled by PHP-Scoper are configured in extra configuration files `prefixer/composer.json` and `prefixer/scoper.inc.php`. Installation and processing is triggered in post scripts of the main `composer.json` file.

### Changelog

Create changelog entries using:

```bash
./do changelog:add --type=Fixed --description="Brief description of the change"
```

See [readme](changelog/README.md) for detailed documentation.

### i18n

We use functions `__()`, `_n()`, `_x()`, and `_nx()` with domain `mailpoet` to translate strings. Please follow [best practices](https://codex.wordpress.org/I18n_for_WordPress_Developers).

#### Comments for translators

When the translation string can be ambiguous, add [a translators comment](https://codex.wordpress.org/I18n_for_WordPress_Developers#Descriptions) for clarification. Don't use `_x()` or `_xn()` for clarification.

```php
// translators:
$customErrorMessage = sprintf(
  // translators: %1$s is the link, %2$s is the error message.
  __('Please see %1$s for more information. %2$s.', 'mailpoet'),
  'https://kb.mailpoet.com',
  $errorMessage
);
```

#### In PHP code

```php
__('text to translate', 'mailpoet');
_n('single text', 'plural text', $number, 'mailpoet');
_x('text to translate', 'context', 'mailpoet');
_xn('single text', 'plural text', $number, 'context', 'mailpoet');
```

#### In JavaScript/TypeScript code

```ts
import { __, _n, _x, _xn } from '@wordpress/i18n';

__('text to translate', 'mailpoet');
_n('single text', 'plural text', number, 'mailpoet');
_x('text to translate', 'context', 'mailpoet');
_nx('single text', 'plural text', number, 'context', 'mailpoet');
```

To replace placeholders in translated strings, use `sprintf`:

```ts
import { sprintf } from '@wordpress/i18n';

sprintf(__('Hello %s', 'mailpoet'), 'John');
```

To replace React elements use `createInterpolateElement`:

```tsx
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { CustomComponent } from '../custom-component.js';

const translatedString = createInterpolateElement(
  __(
    'This is a <span>string</span> with a <a>link</a> and a self-closing <custom_component/>.',
  ),
  {
    span: <span class="special-text" />,
    a: <a href="https://make.wordpress.org" />,
    custom_component: <CustomComponent />,
  },
);
```

For more information, see the [@wordpress/i18n](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/)
and the [createInterpolateElement](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-element/#createinterpolateelement)
guides.

### Acceptance testing

To run the whole acceptance testing suite you need the docker daemon to be running and after that use a command: `./do test:acceptance`.
If you want to run only a single test use the parameter `--file`:

```bash
./do test:acceptance --skip-deps --file tests/acceptance/ReceiveStandardEmailCest.php
```

The argument `--skip-deps` is useful locally to speed up the run.

If there are some unexpected errors you can delete all the runtime and start again.
To delete all the docker runtime for acceptance tests use the command `./do delete:docker`.

When debugging you can add `$i->pause();` in to your test which pauses the execution.

We are using Gravity Flow plugin's setup as an example for our acceptance test suite: https://www.stevenhenty.com/learn-acceptance-testing-deeply/

From the article above:

_Windows users only: enable hard drive sharing in the Docker settings._

The browser runs in a docker container. You can use a VNC client to watch the test run, follow instructions in official
repo: https://github.com/SeleniumHQ/docker-selenium
If you’re on a Mac, you can open vnc://localhost:5900 in Safari to watch the tests running in Chrome. If you’re on Windows, you’ll need a VNC client. Password: secret.
