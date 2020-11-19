<?php namespace model;

class cFileSystem extends \app\cModel {

	// make dir
	static public function md( string $path, $mode = 0700 ) {
//		$path = APP_ROOT . trim( $path, '/' );
		$type = @filetype( $path );

		switch( $type ) {
			case false:
				if ( ! mkdir( $path, $mode, true ) ) {
					throw new \Exception( 'Cannot create directory: ' . $path );
				}
			case 'dir':
				return true;

			default:
				throw new \Exception( 'Path is not a directory: ' . $path );
		}
	}

	// read file
	static public function rf( string $path, bool $binary = false ) {
//		$path = APP_ROOT . trim( $path, '/' );
		$type = @filetype( $path );

		switch( $type ) {
			case 'file':
				$h = ( $binary ) ? fopen( $path, 'rb' ) : fopen( $path, 'r' );
				$data = fread( $h, filesize( $path ) );
				fclose( $h );
				return $data;
			case false:
				return false;
			default:
				throw new \Exception( 'Path is not a file: ' . $path );
		}
	}

	// write file #TODO: check dir, check fwrite status
	static public function wf( string $path, $data, bool $binary = false ) {
//		$path = APP_ROOT . trim( $path, '/' );
		$type = @filetype( $path );

		switch( $type ) {
			case false:
			case 'file':
				$h = ( $binary ) ? fopen( $path, 'wb' ) : fopen( $path, 'w' );
				fwrite( $h, $data );
				fclose( $h );
				return true;
			default:
				throw new \Exception( 'Path is not a file: ' . $path );
		}
	}

	static public function cat( string $path, bool $binary = true ) {
		$type = @filetype( $path );

		switch( $type ) {
			case 'file':
				$h = fopen( $path, ( $binary ) ? 'rb' : 'r' );
				fpassthru( $h );
				fclose( $h );
				return true;
			default:
				throw new \Exception( 'Path is not a file: ' . $path );
		}
	}

}
