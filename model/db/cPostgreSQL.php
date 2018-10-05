<?php namespace model\db;

class cPostgreSQL extends \app\cModel implements iSQL {

	protected $pt = null;
	protected $rx = null;

	public function __construct( $host, $port, $base, $user, $pass ) {

		if ( ! extension_loaded( 'pgsql' ) ) throw new \Exception( 'PGSQL module not loaded' );

		$s = "options='--client_encoding=UTF8'";
		if ( $host != '' ) $s .= ' host='.$host;
		if ( $port != '' ) $s .= ' port='.$port;
		if ( $base != '' ) $s .= ' dbname='.$base;
		if ( $user != '' ) $s .= ' user='.$user;
		if ( $pass != '' ) $s .= ' password='.$pass;
		$this->pt = @pg_pconnect( $s );

		if ( ! $this->pt ) throw new \Exception( 'PGSQL connection failed' );
	}

	public function get( string $table, array $fields, $filter = null, $sort = null ) {
		$this->select( $table, $fields, $filter, $sort );
		return $this->fetch_all();
	}

	public function select( string $table, array $fields, $filter = null, $sort = null ) {
		if ( empty( $fields ) ) return false;

		$q = sprintf( 'SELECT "%s" FROM "%s"', implode( '", "', $fields ), $table );
		if ( is_array( $filter ) && ! empty( $filter ) ) {
			$filter = pg_convert( $this->pt, $table, $filter );
			foreach( $filter as $k => &$v ) {
				$v = $k . ' = ' . $v ;
			}
			$q .= ' WHERE ' . implode( ' AND ', $filter );
		}

		if ( ( is_array( $sort ) && ! empty( $sort ) ) ) {
			foreach( $sort as &$v ) {
				$t = ltrim( $v, '+-' );
				if ( $v[0] === '+' ) {
					$v = '"' . $t . '" ASC';
				} elseif ( $v[0] === '-' ) {
					$v = '"' . $t . '" DESC';
				} else {
					$v = '"' . $t . '"';
				}
			}
			$q .= ' ORDER BY ' . implode( ', ', $sort );
		}

		$this->rx = @pg_query( $this->pt, $q.';' );
		if ( ! $this->rx ) throw new \Exception( 'PGSQL query error: ' . pg_last_error( $this->pt ) );
		return pg_num_rows( $this->rx );
	}

	public function insert( string $table, array $keyval ) {
		if ( empty( $keyval ) ) return false;

		$q = pg_insert( $this->pt, $table, $keyval, PGSQL_DML_STRING );
		if ( $q !== false ) $q = pg_query( $this->pt, $q );

		if ( ! $q ) throw new \Exception( 'PGSQL query error: ' . pg_last_error( $this->pt ) );
		$t = pg_affected_rows( $q );
		pg_free_result( $q );
		return $t;
	}

	public function update( string $table, array $keyval, array $filter ) {
		if ( empty( $keyval ) || empty( $filter ) ) return false;

		$q = pg_update( $this->pt, $table, $keyval, $filter, PGSQL_DML_STRING );
		if ( $q !== false ) $q = pg_query( $this->pt, $q );

		if ( ! $q ) throw new \Exception( 'PGSQL query error: ' . pg_last_error( $this->pt ) );
		$t = pg_affected_rows( $q );
		pg_free_result( $q );
		return $t;
	}

	public function delete( string $table, array $filter ) {
		if ( empty( $filter ) ) return false;

		$q = pg_delete( $this->pt, $table, $filter, PGSQL_DML_STRING );
		if ( $q !== false ) $q = pg_query( $this->pt, $q );

		if ( ! $q ) throw new \Exception( 'PGSQL query error: ' . pg_last_error( $this->pt ) );
		$t = pg_affected_rows( $q );
		pg_free_result( $q );
		return $t;
	}

	public function fetch() {
		if ( $this->rx === null ) return false;

		$t = pg_fetch_row( $this->rx );
		if ( ! $t ) {
			pg_free_result( $this->rx );
			$this->rx = null;
		}
		return $t;
	}

	public function fetch_all() {
		if ( $this->rx === null ) return false;

		$fff = [];
		while( $t = pg_fetch_row( $this->rx ) ) {
			$fff[] = $t;
		}
		pg_free_result( $this->rx );
		$this->rx = null;
		return $fff;
	}

}
