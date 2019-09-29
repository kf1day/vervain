<?php namespace model\acl;
use \model\cache\iCacher;

class cAuthUserInfo {
	public $name = '';
	public $secret = '';
	private $groups = [];

	public function __construct( array $user_info ) {
		$this->name = $user_info['name'];
		$this->secret = $user_info['secret'];
		$this->groups = $user_info['groups'] ?? [];
	}

	public function in_group( string $group_name ) {
		if ( ! in_array( $group_name, $this->groups ) ) {
			throw new \EClientError( 403 );
		}
	}
}

abstract class cAuth extends \app\cModel {

	private $cache = null;
	private $user = null;

	abstract public function fetch_user( string $uid ): array;

	public function __construct( iCacher $cache ) {
		$this->cache = $cache;
	}

	final public function auth_server() {
		if ( $u = $_SERVER['PHP_AUTH_USER'] ?? false ) {
			$u = strtolower( $u );
			$uid = $this->usrcode( $u );
			$t = $this->fetch_user( $u );
			$this->user = new cAuthUserInfo( $t );
			$this->cache->set( 'acl_' . $uid, $t );

			return [ $uid, $this->keyring( $t['secret'] ) ];
		}
		throw new \Exception( 'Failed to fetch authorized user' );
	}

	final public function auth_cookie() {
		if ( $this->user !== null ) return $this->user;

		$uid = $_COOKIE['UID'] ?? false;
		$hash = $_COOKIE['HASH'] ?? false;
		if ( $uid && $hash ) {
			if ( $u = $this->usrcode( $uid, true ) ) {
				$t = $this->cache->get( 'acl_' . $uid, [ $this, 'fetch_user' ], [ $u ] );
				if ( $this->keyring( $t['secret'], $hash ) ) {
					$this->user = new cAuthUserInfo( $t );
					return $this->user;
				}
			}
		}
		throw new \EClientError( 401 );
	}

	public function get_name() {
		return $this->user->name ?? '';
	}

	final private function keyring( $secret, $compare = null ) {
		$hash = hash( 'sha256', APP_HASH . $secret . $_SERVER['REMOTE_ADDR'] );
		if ( $compare === null ) {
			return $hash;
		} else {
			return ( $hash === $compare );
		}
	}

	final private function usrcode( $uid, bool $decode = false ) {
		if ( $decode ) {
			return @hex2bin( $uid ) ?? false;
		} else {
			return bin2hex( $uid );
		}
	}

}
