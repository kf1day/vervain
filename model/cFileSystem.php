<?php namespace model;

class cFileSystem {

	// make dir
	static public function md( $path ) {
//		$path = APP_SITE . trim( $path, '/' );
		$type = @filetype( $path );

		switch( $type ) {
			case false:
				if ( ! mkdir( $path, 0700, true ) ) {
					throw new \Exception( 'Cannot create directory: '.$path );
				}
			case 'dir':
				return true;

			default:
				throw new \Exception( 'Path is not a directory: '.$path );
		}
	}

	// read file
	static public function rf( $path, $binary = false ) {
//		$path = APP_SITE . trim( $path, '/' );
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
				throw new \Exception( 'Path is not a file: '.$path );
		}
	}

	// write file #TODO: check dir, check fwrite status
	static public function wf( $path, $data, $binary = false ) {
//		$path = APP_SITE . trim( $path, '/' );
		$type = @filetype( $path );

		switch( $type ) {
			case false:
			case 'file':
				$h = ( $binary ) ? fopen( $path, 'wb' ) : fopen( $path, 'w' );
				fwrite( $h, $data );
				fclose( $h );
				return true;
			default:
				throw new \Exception( 'Path is not a file: '.$path );
		}
	}

}
