<?php

if ( PHP_SAPI === 'cli' ) exit( 'CLI instance not implemented yet' . PHP_EOL );

define( 'OPT_DEFAULT_CACHE', '\\model\\cache\\cFileSerial' );
define( 'OPT_DEFAULT_CACHE_ARGS', [] );

define( 'APP_CORE', dirname( __FILE__ ) );
define( 'APP_ROOT', rtrim( $_SERVER['DOCUMENT_ROOT'], '/' ) );
define( 'APP_HASH', basename( APP_ROOT ) . '-' . crc32( APP_ROOT ) );

require APP_CORE . '/core/app.php';
require APP_CORE . '/core/map.php';
require APP_CORE . '/core/http.php';

new instance();
