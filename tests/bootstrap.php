<?php
/**
 * Bootstrap file for WooCommerce Sniffs tests.
 */

if ( ! defined( 'PHP_CODESNIFFER_IN_TESTS' ) ) {
	define( 'PHP_CODESNIFFER_IN_TESTS', true );
}

$ds = DIRECTORY_SEPARATOR;

// Load the Composer autoloader.
require_once dirname( __DIR__ ) . $ds . 'vendor' . $ds . 'autoload.php';

// Load the PHPCS test bootstrap.
require_once dirname( __DIR__ ) . $ds . 'vendor' . $ds . 'squizlabs' . $ds . 'php_codesniffer' . $ds . 'tests' . $ds . 'bootstrap.php';

/*
 * Set up the global config and ruleset before tests run.
 *
 * ConfigDouble wipes the CodeSniffer.conf data (including installed_paths),
 * so we need to re-register the standard path after creating the ConfigDouble
 * but before the Ruleset is created.
 */
$config        = new PHP_CodeSniffer\Tests\ConfigDouble();
$config->cache = false;

// Re-register installed_paths after ConfigDouble wiped it.
$srcDir = dirname( __DIR__ ) . $ds . 'src';
PHP_CodeSniffer\Config::setConfigData( 'installed_paths', $srcDir, true );

$GLOBALS['PHP_CODESNIFFER_CONFIG'] = $config;

// Initialize the globals used by AbstractSniffUnitTest.
$GLOBALS['PHP_CODESNIFFER_STANDARD_DIRS']  = [];
$GLOBALS['PHP_CODESNIFFER_TEST_DIRS']      = [];
$GLOBALS['PHP_CODESNIFFER_SNIFF_CODES']    = [];
$GLOBALS['PHP_CODESNIFFER_FIXABLE_CODES']  = [];
$GLOBALS['PHP_CODESNIFFER_SNIFF_CASE_FILES'] = [];

// Register the WooCommerce standard's test directories.
$standardDir = $srcDir . $ds . 'WooCommerce';
$testsDir    = $standardDir . $ds . 'Tests' . $ds;

// Discover all test classes and register their directories.
$di = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $testsDir ) );
foreach ( $di as $file ) {
	if ( substr( $file->getFilename(), 0, 1 ) === '.' ) {
		continue;
	}

	$parts = explode( '.', $file->getFilename() );
	$ext   = array_pop( $parts );
	if ( $ext !== 'php' ) {
		continue;
	}

	$className = PHP_CodeSniffer\Autoload::loadFile( $file->getPathname() );
	$GLOBALS['PHP_CODESNIFFER_STANDARD_DIRS'][ $className ] = $standardDir;
	$GLOBALS['PHP_CODESNIFFER_TEST_DIRS'][ $className ]      = $testsDir;
}
