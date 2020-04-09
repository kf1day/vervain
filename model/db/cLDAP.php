<?php namespace model\db;

class cLDAP extends \app\cModel implements iSQL {

	protected $pt = null;	// $link_identifier
	protected $ax = null;	// queried fields
	protected $rx = null;	// $result_identifier
	protected $el = null;	// $result_entry_identifier
	protected $root = '';

	public function __construct( $host, $port, $base, $user, $pass ) {

		if ( ! extension_loaded( 'ldap' ) ) throw new \Exception( 'LDAP module not loaded' );

		$this->pt = ( $port ) ? ldap_connect( $host, $port ) : ldap_connect( $host );
		if ( ! $this->pt ) throw new \Exception( 'Incorrect LDAP settings' );

		ldap_set_option( $this->pt, LDAP_OPT_PROTOCOL_VERSION, 3 );
		ldap_set_option( $this->pt, LDAP_OPT_REFERRALS, 0 );

		if ( ! @ldap_bind( $this->pt, $user, $pass ) ) throw new \Exception( 'LDAP connection failed' );
		$this->root = $base;
	}

	public function get( string $table, array $fields, $filter = null ) {
		$this->select( $table, $fields, $filter );
		return $this->fetch_all();
	}

	public function select( string $table, array $fields, $filter = null ) {
		$root = ( $table === '' ) ? $this->root : $table . ',' . $this->root;
		$filt = '';
		foreach ( $filter as $k => $v ) {
			$filt .= '(' . $k . '=' . $v . ')';
		}
		$this->el = null;
		if ( count( $filter ) > 1 ) $filt = '(&' . $filt . ')';
		if ( $this->rx = ldap_search( $this->pt, $root, $filt, $fields ) ) {
			$this->ax = $fields;
			return ldap_count_entries( $this->pt, $this->rx );
		} else {
			$this->ax = $this->rx = null;
			return false;
		}
	}

	public function insert( string $table, array $keyval ) {}
	public function update( string $table, array $keyval, array $filter ){}
	public function delete( string $table, array $filter ){}

	public function fetch( &$dn = null ) {
		if ( $this->rx === null ) return false;
		$this->el = ( $this->el === null ) ? ldap_first_entry( $this->pt, $this->rx ) : ldap_next_entry( $this->pt, $this->el );
		if ( $this->el === false ) {
			ldap_free_result ( $this->rx );
			$this->el = $this->ax = $this->rx = null;
			return false;
		}

		$fff = $this->ax;
		foreach( $fff as &$key ) {
			$t = ( $key === 'dn' ) ? ldap_get_dn( $this->pt, $this->el ) : ldap_get_values( $this->pt, $this->el, $key );
			$this->xvalue( $key, $t );
		}
		return $fff;
	}

	public function fetch_all( bool $assoc = true ) {
		if ( $this->rx === null ) return false;
		$t = ldap_get_entries( $this->pt, $this->rx );
		$fff = [];
		ldap_free_result( $this->rx );
		for( $i = 0; $i < $t['count']; $i++ ) {
			$u = $this->ax;
			foreach( $u as &$key ) {
				$this->xvalue( $key, $t[$i][strtolower($key)] ?? false );
			}
			if ( $assoc ) {
				$fff[$t[$i]['dn']] = $u;
			} else {
				$fff[] = $u;
			}
		}
		$this->el = $this->ax = $this->rx = null;
		return $fff;
	}

	protected function xvalue( &$key, $val ) {
		if ( $val === false ) {
			$key = null;
		} else {
			switch ( strtolower( $key ) ) {
				case 'dn':
					$key = $val;
					break;
				case 'objectsid':
					$key = $this->bin2sid( $val[0] );
					break;
				case 'objectguid':
					$key = $this->bin2guid( $val[0] );
					break;
				default:
					if ( $val['count'] === 1 ) {
						$key = mb_check_encoding( $val[0] ) ? $val[0] : bin2hex( $val[0] ) ;
					} else {
						unset( $val['count'] );
						$key = $val;
					}
			}
		}
	}

	protected function bin2sid( $b ) {
		$u = unpack( 'Ca/X/Jb/V*c', $b );
		$u['b'] &= 0xffffffffffff; // need last 48 bytes from uint64 (Jb)
		return 'S-' . implode( '-', $u );
	}

	protected function bin2guid( $b ) {
		$u = unpack( 'Va/v2b/n2c/Nd', $b );
		return sprintf( '%08X-%04X-%04X-%04X-%04X%08X', $u['a'], $u['b1'], $u['b2'], $u['c1'], $u['c2'], $u['d'] );
	}


}
