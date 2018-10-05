<?php

final class instance {

	public function __construct() {

		spl_autoload_register( [ $this, 'loader' ] );
		$path = $_SERVER['DOCUMENT_URI'];
		$cvar = $_SERVER['VERVAIN_CACHE'] ?? null;
		if ( $cvar === null ) {
			$cvar = OPT_DEFAULT_CACHE;
			$cvar_args = OPT_DEFAULT_CACHE_ARGS;
		} else {
			$cvar_args = $_SERVER['VERVAIN_CACHE_ARGS'] ?? '';
			$cvar_args = explode( ',', $cvar_args );
		}
		$ref = null;
		$cls = null;

		$onload = true;

		try {
			$ref = new ReflectionClass( $cvar );
			if ( ! $ref->isSubclassOf( '\\model\\cache\\iCacher' ) ) throw new Exception( sprintf( 'Class "%s" must implement "\\model\\cache\\iCacher"', $cvar ) );
			$cache = $ref->newInstanceArgs( $cvar_args );
			$ref = null;

			$map = new map( $cache );
			try {
				list( $action, $method, $args ) = $map->routing( $path );
			} catch( EClientError $e ) {
				$action = '__default';
				$method = '__onerror';
				$args = [ $e->getCode(), $e->getMessage() ];
				$onload = false;
			}
			$ref = new ReflectionClass( '\\action\\' . $action );
			if ( ! $ref->isSubclassOf( '\\app\\cAction' ) ) throw new Exception( sprintf( 'Class "\\action\\%s" must be instance of "\\app\\cAction"', $action ) );

			$cls = $ref->newInstance( $cache, $path );
			if ( $onload ) $ref->getMethod( '__onload' )->invoke( $cls );
			if( ! $ref->hasMethod( $method ) ) throw new EClientError( 404, sprintf( 'Method "\\%s::%s()" not found!', $ref->getName(), $method ) );
			$ref->getMethod( $method )->invokeArgs( $cls, $args );

		} catch( ERedirect $e ) {
			$e();
		} catch( EClientError $e ) {
			$args = [ $e->getCode(), $e->getMessage() ];
			$ref->getMethod( '__onerror' )->invokeArgs( $cls, $args );
		} catch( Exception $e ) {
			http_response_code( 500 );
			echo $e->getMessage();
		}
	}

	private function loader( $classname ) {
		$classname = str_replace( '\\', '/', $classname );
		if ( is_file( APP_SITE . '/' . $classname . '.php' ) ) {
			include APP_SITE . '/' . $classname . '.php';
		} elseif ( is_file( APP_ROOT . '/' . $classname . '.php' ) ) {
			include APP_ROOT . '/' . $classname . '.php';
		}
	}
}

class ERedirect extends Exception {
	private $url = null;

	public function __construct( $url = null ) {
		parent::__construct();
		$this->url = $url;
	}

	public function __invoke() {
		if ( isset( $_SERVER['REQUEST_SCHEME'] ) ) {
			$scheme = $_SERVER['REQUEST_SCHEME'];
		} elseif ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ) { // http://nginx.org/en/docs/http/ngx_http_core_module.html#var_https
			$scheme = 'https';
		} else {
			$scheme = 'http';
		}
		$host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'];
		if ( ! in_array( $scheme . $_SERVER['SERVER_PORT'], [ 'http80', 'https443' ] ) ) $host .= ':' . $_SERVER['SERVER_PORT'];
		header( 'Location: ' . $scheme . '://' . $host . $this->url, true, 302 ); // absolute path required due to RFC
	}
}

class EClientError extends Exception {

	const HTTP_STATUS = [
		400 => 'Bad Request',
		401 => 'Unauthorized',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		409 => 'Conflict',
	];

	public function __construct( $code, $body = null ) {
		if ( ! isset( self::HTTP_STATUS[$code] ) ) $code = 400;
		if ( $body === null ) $body = self::HTTP_STATUS[$code];
		parent::__construct( $body, $code );
	}
}
