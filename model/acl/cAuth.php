<?php namespace model\acl;

abstract class cAuth extends \app\cModel {

	/**
	 * Cacher class
	 */
	protected $cw = null;

	/**
	 *  User info
	 * @var rAuthUser
	 */
	protected $ui = null;



	abstract public function get_user( string $uid ): array;


	public function __construct( $cache ) {
		$this->cw = $cache;
	}

	final public function auth_server() {
		if ( $u = $_SERVER['PHP_AUTH_USER'] ?? false ) {
			$u = strtolower( $u );
			$this->ui = $this->get_user( $u );
			$this->ui['id'] = $this->usrcode( $u );
			$this->cw->set( 'acl_' . $this->ui['id'], $this->ui );
			return true;
		}
		throw new \Exception( 'Failed to fetch authorized user' );
	}

	final public function auth_cookie( bool $ignore_cache = false ) {
		if ( $uid = $_COOKIE['UID'] ?? false and $hash = $_COOKIE['HASH'] ?? false ) {
			$this->ui = $this->cw->get( 'acl_' . $uid, function( $uid ) {
				$u = $this->usrcode( $uid, 1 );
				return $this->get_user( $u );
			}, [ $uid ] );
			if ( $this->keyring( $this->ui['secret'], $hash ) ) return true;
		}
		throw new \EClientError( 403 );
	}

	public function get_name() {
		return ( $this->ui === null ) ? '' : $this->ui['name'];
	}

	public function get_groups() {
		return ( $this->ui === null ) ? '' : $this->ui['groups'];
	}


	public function get_token() {
		return [ $this->ui['id'], $this->keyring( $this->ui['secret'] ) ];
	}

	final protected function keyring( $secret, $compare = null ) {
		$hash = hash( 'sha256', APP_HASH . $secret . $_SERVER['REMOTE_ADDR'] );
		if ( $compare !== null ) {
			return ( $hash === $compare );
		} else {
			return $hash;
		}
	}

	final protected function usrcode( $uid, $decode = false ) {
		if ( $decode ) {
			return @hex2bin( $uid ) ?? false;
		} else {
			return bin2hex( $uid );
		}
	}

}
