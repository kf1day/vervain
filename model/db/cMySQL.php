<?php namespace model\db;

class cMySQL extends \app\cModel implements iSQL {

	protected $pt = null;
	protected $rx = null;

	public function __construct( $host, $port, $base, $user, $pass ) {

		if ( ! extension_loaded( 'mysqli' ) ) throw new \Exception( 'MySQLi module not loaded' );

		$host = ( is_string( $host ) ) ? $host . ( $port ? ':' . $port : '' ) : '';
		$this->pt = mysqli_connect( $host, $user, $pass, $base );
		if ( ! $this->pt ) throw new \Exception( 'MySQL connection failed' );

		mysqli_query( $this->pt, 'SET NAMES UTF8' );
	}

	public function get( string $table, array $fields, $filter = null, $sort = null ) {
		$this->select( $table, $fields, $filter, $sort );
		return $this->fetch_all();
	}

	public function select( string $table, array $fields, $filter = null, $sort = null ) {
		if ( empty( $fields ) ) return false;

		$q = sprintf( 'SELECT `%s` FROM `%s`', implode( '`, `', $fields ), $table );
		if ( is_array( $filter ) && ! empty( $filter ) ) {
			foreach( $filter as $k => &$v ) $v = '`' . $k . '` = "' . mysqli_real_escape_string( $this->pt, $v ) . '"';
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
		$this->rx = mysqli_query( $this->pt, $q . ';' );
		if ( ! $this->rx ) throw new \Exception( 'MySQL query error: ' . mysqli_error( $this->pt ) );
		return mysqli_num_rows( $this->rx );
	}

	public function insert( string $table, array $keyval ) {
		if ( empty( $keyval ) ) return false;

		array_walk( $keyval, [ $this, 'escape' ], false );

		$q = sprintf( 'INSERT INTO `%s`(%s) VALUES(%s)', $table, implode(', ', array_keys( $keyval ) ), implode(', ', $keyval ) );
		$q = mysql_query( $this->pt, $q . ';' );

		if ( ! $q ) throw new \Exception( 'MySQL query error: ' . mysqli_error( $this->pt ) );
		$t = mysqli_affected_rows( $q );
		mysqli_free_result( $q );
		return $t;
	}

	public function update( string $table, array $keyval, array $filter ) {
		if ( empty( $keyval ) || empty( $filter ) ) return false;

		array_walk( $keyval, [ $this, 'escape' ], true );
		array_walk( $filter, [ $this, 'escape' ], true );

		$q = sprintf( 'UPDATE `%s` SET %s WHERE %s', $table, implode( ', ', $fields ), implode( ' AND ', $filter ) );
		$q = mysql_query( $this->pt, $q . ';' );

		if ( ! $q ) throw new \Exception( 'MySQL query error: ' . mysqli_error( $this->pt ) );
		$t = mysqli_affected_rows( $q );
		mysqli_free_result( $q );
		return $t;
	}

	public function delete( string $table, array $filter ) {
		if ( empty( $filter ) ) return false;

		array_walk( $filter, [ $this, 'escape' ], true );

		$q = sprintf( 'DELETE FROM `%s` WHERE %s', $table, implode( ' AND ', $filter ) );
		$q = mysql_query( $this->pt, $q . ';' );

		if ( ! $q ) throw new \Exception( 'MySQL query error: ' . mysqli_error( $this->pt ) );
		$t = mysqli_affected_rows( $q );
		mysqli_free_result( $q );
		return $t;
	}

	public function fetch() {
		if ( $this->rx === null ) return false;

		$t = mysqli_fetch_row( $this->rx );
		if ( ! $t ) {
			mysqli_free_result( $this->rx );
			$this->rx = null;
		}
		return $t;
	}

	public function fetch_all() {
		if ( $this->rx === null ) return false;

		$fff = [];
		while( $t = mysqli_fetch_row( $this->rx ) ) {
			$fff[] = $t;
		}
		mysqli_free_result( $this->rx );
		$this->rx = null;
		return $fff;
	}

	protected function escape( &$v, $k, $flag = false ) {
		$v = '"' . mysqli_real_escape_string( $this->pt, $v ) . '"';
		if ( $flag ) {
			$v = '`' . $k . '` = ' . $v;
		}
	}
}
