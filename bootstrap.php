<?php

if ( PHP_SAPI === 'cli' ) exit( 'CLI instance not implemented yet' . PHP_EOL );

define( 'OPT_DEFAULT_CACHE', '\\model\\cache\\cFileSerial' );
define( 'OPT_DEFAULT_CACHE_ARGS', [] );

define( 'APP_ROOT', dirname( __FILE__ ) );
define( 'APP_SITE', rtrim( $_SERVER['DOCUMENT_ROOT'], '/' ) );
define( 'APP_HASH', basename( APP_SITE ) . '-' . substr( md5( APP_SITE ), 0, 6 ) );

require APP_ROOT.'/core/app.php';
require APP_ROOT.'/core/map.php';
require APP_ROOT.'/core/http.php';

new instance();
