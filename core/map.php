<?php

final class map {

	private $pt = null;
	private $trace = [];

	const PATH = APP_ROOT . '/sitemap.php';

	public function __construct() {
		if ( ! is_file( self::PATH ) ) throw new EMapParser( 'File not found', self::PATH, 'Check the file is existing and readable' );
		$this->pt = \instance::$cache->get( 'sitemap', function() {
			$ret = require self::PATH;
			$this->parse( $ret );
			$this->merge( $ret );
			return $ret;
		}, [], filemtime( self::PATH ) );
	}

	public function path() {
		$args = func_get_args();
		$val = reset( $args );
		$path = [];
		foreach( $this->trace as $v ) {
			if ( '*' !== $v ) {
				$path[] = $v;
			} elseif ( false === $val ) {
				trigger_error( 'Undefined replacer' );
				$path[] = '*';
			} elseif ( null === $val ) {
				$val = next( $args );
			} else {
				$path[] = $val;
				$val = next( $args );
			}
		}
		while ( false !== $val ) {
			if ( null === $val ) {
				array_pop( $path );
			}
			else {
				$path[] = $val;
			}
			$val = next( $args );
		}
		return '/' . implode( '/', $path ) . '/';
	}

	public function routing( &$path ) {
		$this->trace = [];
		$map = $this->pt;
		$method = null;
		$args = [];
		$nice = '/';
		$flag = false;
		$node = strtok( $path, '/' );

		if ( $map[0] !== '' ) {
			$nice .= $map[0] . '/';
			if( $node === $map[0] ) {
				$node = strtok( '/' );
			} else {
				$flag = true;
			}
		}

		while ( $node ) {
			$flag = true;
			if ( $map[4] === null ) break;
			foreach ( $map[4] as $map_child ) {
				if ( $map_child[0] === '*' ) {
					$this->trace[] = '*';
					$flag = false;
					$nice .= $node . '/';
					$args[] = $node;
					$node = strtok( '/' );
					$map = $map_child;
				} elseif ( $node === $map_child[0] ) {
					$this->trace[] = $node;
					$flag = false;
					$nice .= $node . '/';
					$node = strtok( '/' );
					$map = $map_child;
					break;
				}
			}
			if ( $flag ) break;
		}

		if ( $map[1] === null ) {
			if ( $flag ) throw new EClientError( 404 );
			while( $map[1] === null ) {
				$map = reset( $map[4] );
				if ( $map[0] === '*' ) throw new EClientError( 404 );
				$nice .= $map[0] . '/';
			}
			throw new ERedirect( $nice );
		}

		$method = $map[2];
		while( $node ) {
			if ( $method === null ) {
				$method = $node;
			} else {
				$args[] = $node;
			}
			$node = strtok( '/' );
		}


		if ( ! ( $flag || in_array( $nice, [ $path,  $path . '/' ] ) ) ) throw new ERedirect( $nice );

		$path = $nice;
		if ( $method === null ) {
			$method = 'index';
		} else {
			$method = ltrim( $method, '_' );
		}
		if ( ! empty( $map[3] ) ) {
			$args = array_merge( $map[3], $args );
		}

		return [ $map[1], $method, $args ];
	}

	private function parse( &$map, $path = '', $action = null ) {
		if ( is_string( $map[0] ) ) {
			$map = [ trim( $map[0], '/' ), $map[1] ?? null, null, null, $map[2] ?? null ];
			if ( $map[0] !== '' ) $path .= '/' . $map[0];
			$stack = explode( '/', $map[0] );
			$map[0] = array_pop( $stack );
		} else {
			throw new EMapParser( 'Invalid pattern (after)', $path, 'Pattern must be a string' );
		}

		if ( $map[1] === '' ) {
			$map[1] = $action;
		} elseif ( is_string( $map[1] ) ) {
			$map[3] = explode( '/', $map[1] );
			$map[1] = array_shift( $map[3] );
			$t = explode( '@', $map[1] );
			$map[1] = ( $t[0] === '' ) ? $action : $t[0];
			if ( $map[1] === null ) throw new EMapParser( 'Failed to inherit classname', $path, 'Specify a classname directly'  );
			$map[2] = ( empty( $t[1] ) ) ? null : $t[1];
		} else {
			$map[1] = null;
		}
		if ( $map[4] === null ) {
			if ( $map[1] === null ) throw new EMapParser( 'Dead-end', $path, 'Specify or inherit a classname' );
		} else {
			foreach( $map[4] as &$map_child ) {
				$this->parse( $map_child, $path, $map[1] );
			}
		}

		while ( $node = array_pop( $stack ) ) {
			$map = [ $node, null, null, null, [ $map ] ];
		}
	}

	private function merge( &$map, $path = '' ) {
		if ( $map[4] !== null ) {
			if ( $map[0] !== '' ) $path .= '/' . $map[0];
			$uniq = [];
			foreach( $map[4] as $k => &$map_child ) {
				$id = $map_child[0];
				if ( isset( $uniq[$id] ) ) {
					if ( $uniq[$id][1] === null ) {
						list( $uniq[$id][1], $uniq[$id][2], $uniq[$id][3] ) = [ $map_child[1], $map_child[2], $map_child[3] ];
					} elseif ( $map_child[1] !== null ) {
						throw new EMapParser( 'Duplicated node detected', $path . '/' . $uniq[$id][0], 'Remove or rename the duplicate node' );
					}
					if ( $map_child[4] && $uniq[$id][4] ) {
						$uniq[$id][4] = array_merge( $uniq[$id][4], $map_child[4] );
					} elseif ( $map_child[4] && ! $uniq[$id][4] ) {
						$uniq[$id][4] = $map_child[4];
					}
					unset( $map[4][$k] );
				} else {
					$uniq[$id] = &$map_child;
				}
			}
			usort ( $map[4], function( $a, $b ) {
				if( $a[0] === '*' ) return 1;
				if( $b[0] === '*' ) return -1;
				return 0;
			} );
			foreach( $map[4] as &$map_child ) {
				$this->merge( $map_child, $path );
			}
		}
	}

}

class EMapParser extends Exception {

	public function __construct( $type, $path, $hint = '' ) {
		parent::__construct( sprintf( 'Sitemap error: %s at "%s". %s', $type, $path, $hint ) );
	}
}
