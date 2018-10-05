<?php namespace model\cache;

interface iCacher {

	public function get( string $key, callable $callback, array $args = [], $version = null );
	public function set( string $key, $value, $version = null );

}
