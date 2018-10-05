<?php namespace view;

class cN8ive extends \app\cView {

	const STRIP = 0x01;
	const O_FT1 = 0x02;
	const O_FT2 = 0x04;
	const O_FT3 = 0x08;

	const TYPE_HTML = 0x10;
	const TYPE_XSLT = 0x20;
	const TYPE_XML = 0x30;

	protected $type = 0x00;
	protected $strip = false;


	public function __construct( $tpl_path, $opts = null ) {
		parent::__construct( $tpl_path );
		if ( $opts !== null ) {
			if ( $opts & self::STRIP ) $this->strip = true;
			$this->type = $opts & 0xf0;
		}
	}

	public function display( $vars = null ) {
		if ( is_array( $vars ) ) {
			extract( $vars );
			unset( $vars );
		}
		if ( $this->strip ) ob_start( [ $this, 'closure' ] );

		switch( $this->type ) {

			case self::TYPE_HTML:
				header( 'Content-Type: text/html; charset=utf-8' );
				echo '<!DOCTYPE html>'.PHP_EOL;
				break;

			case self::TYPE_XSLT:
				header( 'Content-Type: application/xml; charset=utf-8' );
				echo '<?xml version="1.0" encoding="utf-8"?>'.PHP_EOL;
				break;

			case self::TYPE_XML:
				header( 'Content-Type: text/xml; charset=utf-8' );
				echo '<?xml version="1.0" encoding="utf-8"?>'.PHP_EOL;
				break;

			default:
				header( 'Content-Type: text/plain; charset=utf-8' );

		}
		while( $tpl = $this->fetch() ) {
			include $tpl;
		}
		ob_end_flush();
	}

	protected function closure( $buf ) {
		$buf = preg_replace( '/<!--.*?-->|(?<=>)\s*[\r\n]+|[\r\n]+\s*(?=<)/s', '', $buf );
		return $buf;
	}
}
