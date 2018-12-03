<?php namespace view;
use \model\cFileSystem;

/**
	ADOPTER FOR https://github.com/bzick/fenom.git
*/


require '/var/www/fenom/src/Fenom.php';

class cFenom extends \app\cView {

	protected $pt = null;

	const PATH = APP_ROOT . '/cache' . '/' . APP_HASH . '/fenom';

	public function __construct( $path, $opts ) {
		parent::__construct( $path );
		cFileSystem::md( self::PATH );
		\Fenom::registerAutoload();
		$this->pt = \Fenom::factory( $this->path, self::PATH, $opts );
		header( 'Content-Type: text/html; charset=utf-8' );
		echo '<!DOCTYPE html>';
	}

	public function display( $vars = null ) {
		if  ( $tmp = $this->fetch() ) {
			$this->pt->display( $tpl, $vars );
		}
	}
}
