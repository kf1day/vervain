<?php namespace model;

class cVURL extends \app\cModel {

	const METHODS = [
		'GET',
		'HEAD',
		'POST',
		'PATCH',
		'PUT',
		'DELETE',
	];

	public static function raw( $uri, $method, $headers = null, $body = null, &$status = null ) {

		if ( ! in_array( $method, self::METHODS ) ) return false;
		if ( ! is_string( $body ) ) $body = '';
		if ( ! is_array( $headers ) ) $headers = [];

		$opts['http']['method'] = $method;
		$opts['http']['content'] = $body;
		$opts['http']['header'] = '';
		foreach( $headers as $k => $v ) {
			$opts['http']['header'] .= $k.': '.$v."\r\n";
		}

		$context = stream_context_create( $opts );
		$res = @file_get_contents( $uri, false, $context );
		sscanf( $http_response_header[0], 'HTTP/%f %d %s', $v, $status, $s ) ;
		return $res;
	}

	public static function get( $uri, $headers = null, $body = null, &$status = null ) {

		if ( is_array( $body ) ) $body = http_build_query( $body );
		if ( $body !== '' ) $uri .= '?'.$body;

		return self::raw( $uri, self::METHODS[0], $headers, $body, $status );
	}

	public static function post_form( $uri, $headers = null, $body = null, &$status = null ) {

		if ( is_array( $body ) ) $body = http_build_query( $body );
		if ( ! is_array( $headers ) ) $headers = [];

		$headers['Content-Type'] = 'application/x-www-form-urlencoded';

		return self::raw( $uri, self::METHODS[2], $headers, $body, $status );
	}

/*	public static function json( $uri, $method, $head = null, $data = null ) {
		$resp = self::query( $uri, $method, $head, $data );
		return json_decode( $resp, true );
		var_dumb( 'LOL' )
	}*/
}
