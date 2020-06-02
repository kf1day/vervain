<?php namespace model\db;

class cPostgreSQL extends \app\cModel implements iSQL {

	protected $pt = null;
	protected $rx = null;
	protected $cf = null;	//keeps indexes of multi-row SELECT query

	private function parse_col( string &$col, array &$sort ) {
		$t = explode( '/', ltrim( $col, '+-' ) . '/' );
		if ( $col[0] === '+') {
			$sort[] = sprintf( '"%s" ASC', $t[0] );
		} elseif( $col[0] === '-') {
			$sort[] = sprintf( '"%s" DESC' );
		}
		switch ( $t[1] ) {
			case 'timestamp':
				$col = sprintf( 'EXTRACT(epoch from "%s")::int', $t[0] );
			break;
			case 'bit':
			case 'int':
			case 'bool':
			case 'text':
					$col = sprintf( '"%s"::%s', $t[0], $t[1] );
			break;
			default:
				$col = sprintf( '"%s"', $t[0] );
		}
	}

	public function __construct( $host, $port, $base, $user, $pass ) {

		if ( ! extension_loaded( 'pgsql' ) ) throw new \Exception( 'PGSQL module not loaded' );

		$s = "options='--client_encoding=UTF8'";
		if ( $host != '' ) $s .= ' host=' . $host;
		if ( $port != '' ) $s .= ' port=' . $port;
		if ( $base != '' ) $s .= ' dbname=' . $base;
		if ( $user != '' ) $s .= ' user=' . $user;
		if ( $pass != '' ) $s .= ' password=' . $pass;
		$this->pt = @pg_pconnect( $s );

		if ( ! $this->pt ) throw new \Exception( 'PGSQL connection failed' );
	}

	public function get( string $table, array $fields, array $filter = [] ) {
		$this->select( $table, $fields, $filter );
		return $this->fetch_all();
	}

	public function select( string $table, array $fields, array $filter = [] ) {
		if ( empty( $fields ) ) return false;

		$order_by = [];
		$group_by = [];
		$this->cf = null;
		$i = 0;
		foreach( $fields as $k => &$col ) {
			if ( is_string( $col ) ) {
				$this->parse_col( $col, $order_by );
				$group_by[] = $col;
			} elseif( is_array( $col ) ) {
				$this->cf[] = $i;
				$sort = [];
				foreach ( $col as &$v ) {
					$this->parse_col( $v, $sort );
				}
				$sort = ( empty( $sort ) ) ? '' : ' ORDER BY ' . implode( ', ', $sort );
				$col = sprintf( 'json_agg(json_build_array(%s)%s)', implode( ', ', $col ), $sort );
			}
			$i++;
		}

		$q = sprintf( 'SELECT %s FROM "%s"', implode( ', ', $fields ), $table );
		if ( ! empty( $filter ) ) {
			$sort = [];
			foreach( $filter as $k => &$col ) {
				$t = explode( '/', $k . '/' );
				$sort[ $t[0] ] = $col;
				switch( $t[1] ) {
					case 'neq':
						$col = '%s != %s';
					break;
					default:
						$col = '%s = %s';
				}
			}
			$sort = pg_convert( $this->pt, $table, $sort );
			$f = reset( $filter );
			foreach( $sort as $k => &$col ) {
				$col = sprintf( $f, $k, $col );
				$f = next( $filter );
			}
			$q .= ' WHERE ' . implode( ' AND ', $sort );
		}

		if ( $this->cf && ! empty( $group_by ) ) {
			$q .= ' GROUP BY ' . implode( ', ', $group_by );
		}

		if ( ! empty( $order_by ) ) {
			$q .= ' ORDER BY ' . implode( ', ', $order_by );
		}

		$this->rx = @pg_query( $this->pt, $q.';' );
		if ( ! $this->rx ) throw new \Exception( 'PGSQL query error: ' . pg_last_error( $this->pt ) );
		return pg_num_rows( $this->rx );
	}

	public function insert( string $table, array $keyval ) {
		if ( empty( $keyval ) ) return false;

		$q = @pg_insert( $this->pt, $table, $keyval );

		if ( $q === false ) throw new \Exception( 'PGSQL query error: ' . pg_last_error( $this->pt ) );
		$t = pg_affected_rows( $q );
		pg_free_result( $q );
		return $t;
	}

	public function update( string $table, array $keyval, array $filter ) {
		if ( empty( $keyval ) || empty( $filter ) ) return false;

		$q = @pg_update( $this->pt, $table, $keyval, $filter );

		if ( $q === false ) throw new \Exception( 'PGSQL query error: ' . pg_last_error( $this->pt ) );
		$t = pg_affected_rows( $q );
		pg_free_result( $q );
		return $t;
	}

	public function delete( string $table, array $filter ) {
		if ( empty( $filter ) ) return false;

		$q = @pg_delete( $this->pt, $table, $filter );

		if ( $q === false ) throw new \Exception( 'PGSQL query error: ' . pg_last_error( $this->pt ) );
		$t = pg_affected_rows( $q );
		pg_free_result( $q );
		return $t;
	}

	public function fetch() {
		if ( $this->rx === null ) return false;

		$t = pg_fetch_row( $this->rx );
		if ( $t === false ) {
			pg_free_result( $this->rx );
			$this->rx = null;
		} elseif ( $this->cf ) foreach ( $this->cf as $k ) {
			$t[$k] = json_decode( $t[$k] );
		}
		return $t;
	}

	public function fetch_all() {
		if ( $this->rx === null ) return false;

		$fff = [];
		while( $t = $this->fetch() ) {
			$fff[] = $t;
		}
		return $fff;
	}

	public function raw() {
		$args = func_get_args();
		foreach( $args as $k => &$v ) {
			if ( $k === 0 ) continue;
			if( is_string( $v ) ) $v = 'E\'' . pg_escape_string( $this->pt, $v ) . '\'';
		}
		$q = call_user_func_array( 'sprintf', $args );
		$this->cf = null;
		$this->rx = @pg_query( $this->pt, $q . ';' );
		if ( $this->rx === false ) throw new \Exception( 'PGSQL query error: ' . pg_last_error( $this->pt ) );
		return pg_num_rows( $this->rx );

	}

}
