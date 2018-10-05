<?php namespace model\cache;
use \model\cFileSystem;

class cFileSerial implements iCacher {

	protected $pt = [];
	protected $vx = [];
	protected $ig = false;
	protected $changed = false;

	const PATH = APP_ROOT . '/cache/' . APP_HASH;

	public function __construct() {
		if (  extension_loaded( 'igbinary' ) ) $this->ig = true;
		cFileSystem::md( static::PATH );
		$t = cFileSystem::rf( static::PATH . '/__fserial__', $this->ig );
		if ( $t !== false ) {
			$t = ( $this->ig ) ? igbinary_unserialize( $t ) : unserialize( $t );
		}
		list( $this->pt, $this->vx ) = $t;
	}

	public function __destruct() {
		if ( $this->changed )  {
			$t = ( $this->ig ) ? igbinary_serialize( [ $this->pt, $this->vx ] ) : serialize( [ $this->pt, $this->vx ] );
			cFileSystem::wf( static::PATH . '/__fserial__', $t, $this->ig );
		}
	}

	// interface methods
	public function get( string $key, callable $callback, array $args = [], $version = null ) {
		if ( $version === null) {
			$force_update = false;
		} else {
			$current = $this->vx[$key] ?? false;
			$force_update = ( $current !== $version );
		}

		if ( $force_update || ( $fff = $this->pt[$key] ?? false ) === false ) {
			$fff = call_user_func_array( $callback, $args );
			$this->set( $key, $fff, $version );
		}
		return $fff;
	}

	public function set( string $key, $value, $version = null ) {
		$this->changed = true;
		$this->pt[$key] = $value;
		if ( $version !== null ) $this->vx[$key] = $version;
	}
}
