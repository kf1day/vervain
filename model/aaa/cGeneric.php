<?php namespace model\aaa;

abstract class cGeneric extends \app\cModel {

	protected $uid = null;
	protected $user = null;
	protected $data = null;

	abstract protected function backend_get_pass(): string;
	abstract protected function backend_get_data();

	final public function auth_env() {
		if ( $u = $_SERVER['PHP_AUTH_USER'] ?? false ) {
			$this->user = strtolower( $u );
			$this->uid = self::usrcode( $this->user );
			$secret = $this->backend_get_pass();
			$this->backend_get_data();

			return [ $this->uid, self::keyring( $secret ) ];
		}
		throw new \Exception( 'Failed to fetch authorized user' );
	}

	final public function auth_cookie() {
		if ( $this->user !== null ) return $this->user;

		$uid = $_COOKIE['UID'] ?? false;
		$hash = $_COOKIE['HASH'] ?? false;
		if ( $uid && $hash ) {
			if ( $u = self::usrcode( $uid, true ) ) {
				$t = $this->cache->get( 'acl_' . $uid, [ $this, 'fetch_user' ], [ $u ] );
				if ( self::keyring( $t['secret'], $hash ) ) {
					$this->user = new cAuthUserInfo( $t );
					return $this->user;
				}
			}
		}
		throw new \EClientError( 401 );
	}

	final public static function keyring( string $secret, $compare = null ) {
		$hash = hash( 'sha256', APP_HASH . $secret . $_SERVER['REMOTE_ADDR'] );
		if ( $compare === null ) {
			return $hash;
		} else {
			return ( $hash === $compare );
		}
	}

	final public static function usrcode( string $uid, bool $decode = false ) {
		if ( $decode ) {
			return @hex2bin( $uid ) ?? false;
		} else {
			return bin2hex( $uid );
		}
	}

}
