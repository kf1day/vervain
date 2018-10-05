<?php namespace view;

/**
	ADOPTER FOR https://github.com/bzick/fenom.git
*/


require '/var/www/fenom/src/Fenom.php';

class cFenom extends \app\cView {

	protected $pt = null;

	public function __construct( $tpl_path, $options ) {
		parent::__construct( $tpl_path );
		$cache = new \model\cache\fs();
		\Fenom::registerAutoload();
		$this->pt = \Fenom::factory( $this->tpl_path, $cache->getpath(), $options );
	}

	public function display( $vars = null ) {
		header( 'Content-Type: text/html; charset=utf-8' );
		echo '<!DOCTYPE html>';
		foreach ( $this->tpl_list as $tpl ) {
			$this->pt->display( $tpl, $vars );
		}
	}
}
