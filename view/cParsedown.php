<?php namespace view;
use \model\cFileSystem;

/**
	ADOPTER FOR https://github.com/erusev/parsedown.git
*/


require '/var/www/parsedown/Parsedown.php';

class cParsedown extends \app\cView {

	protected $pt = null;

	public function __construct( $path ) {
		parent::__construct( $path );
		$this->pt = new \Parsedown();
	}

	public function display() {
		if  ( $tmp = $this->fetch() ) {
			if ( pathinfo( $tmp, PATHINFO_EXTENSION ) === 'md' ) {
				ob_start( [ $this, 'ob_md_parse' ] );
			} else {
				ob_start( [ $this, 'ob_html_closure' ] );
			}
			cFileSystem::cat( $tmp );
			ob_end_flush();
		}
	}

	protected function ob_md_parse( $buf ) {
		return $this->pt->text( $buf );
	}
}
