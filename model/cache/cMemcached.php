<?php namespace model\cache;

class cMemcached implements iCacher {

	protected $pt = [];
	protected $sv = null;

	public function __construct( $host, $port = 0 ) {

		if ( extension_loaded( 'memcached' ) ) {
			$this->sv = new \Memcached( APP_HASH );
			if ( empty( $this->sv->getServerList() ) ) {
				$this->sv->addServer( $host, $port );
			}
		} elseif ( extension_loaded( 'memcache' ) ){
			if ( $port == 0 ) {
				$this->sv = memcache_pconnect( 'unix://' . $host, 0 );
			} else {
				$this->sv = memcache_pconnect( $host, $port );
			}
		} else {
			throw new \Exception( 'Memcache(d) module not loaded' );
		}
		if ( ! $this->sv ) {
			throw new \Exception( 'Memcache(d) connection failed' );
		}
	}



	// interface methods
	public function get( string $key, callable $callback, array $args = [], $version = null ) {
		if ( $version === null || $version === -1 ) {
			$force_update = false;
		} else {
			$current = $this->sv->get( APP_HASH . '/' . crc32( $key ) . '/v' );
			$force_update = ( $current !== $version );
		}

		if ( $force_update || ( $fff = $this->sv->get( APP_HASH . '/' . crc32( $key ) ) ) === false ) {
			$fff = call_user_func_array( $callback, $args );
			if ( $version !== -1 ) {
				$this->set( $key, $fff, $version );
			}
		}
		return $fff;
	}

	public function set( string $key, $value, $version = null ) {
		$this->sv->set( APP_HASH . '/' . crc32( $key ), $value );
		if ( $version !== null ) {
			$this->sv->set( APP_HASH . '/' . crc32( $key ) . '/v', $version );
		}
	}
}
