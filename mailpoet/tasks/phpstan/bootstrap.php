<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

// Constants
define('WPINC', 'wp-includes');
define('WP_LANG_DIR', 'wp-content/languages');
define('WP_MEMORY_LIMIT', 268435456);
define('WP_MAX_MEMORY_LIMIT', 268435456);
define('MAILPOET_VERSION', '1.0.0');
define('DB_HOST', 'localhost');
define('DB_NAME', 'wordpress');
define('DB_USER', 'wordpress');
define('DB_PASSWORD', '12345');
define('MAILPOET_PREMIUM_VERSION', '1.0.0');

// This needs to be set because \MailPoet\Doctrine\TablePrefixMetadataFactory can't construct without it
MailPoet\Config\Env::$dbPrefix = 'wp_';

// Load tracy
$tracyPath = __DIR__ . '/../../tools/vendor/tracy.phar';
require_once($tracyPath);
