<?php namespace model\aaa;

abstract class cGeneric extends \app\cModel {

	protected $uid = null;
	protected $user = null;
	protected $data = null;

	abstract protected function get_pass(): string;
	abstract protected function get_data();

	final public function auth_env() {
		$user = $_SERVER['PHP_AUTH_USER'] ?? false;
		if ( $user ) {
			$this->user = strtolower( $user );
			$this->uid = self::usrcode( $this->user );
			$secret = $this->get_pass();
			$this->get_data();

			return [ $this->uid, self::keyring( $secret ) ];
		}
		throw new \Exception( 'Failed to fetch authorized user' );
	}

	final public function auth_cookie() {
		$uid = $_COOKIE['UID'] ?? false;
		$hash = $_COOKIE['HASH'] ?? false;
		if ( $uid && $hash ) {
			$this->uid = $uid;
			$this->user = self::usrcode( $uid, true );
			$secret = $this->get_pass();
			if ( self::keyring( $secret, $hash ) ) {
				$this->get_data();
			} else {
				throw new \EClientError( 401 );
			}
		} else {
			throw new \EClientError( 403 );
		}
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
