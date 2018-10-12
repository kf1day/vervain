<?php namespace app;

abstract class cModel {
	/* Is there any common methods 4 all models? May be this class is just useless */
}


abstract class cView {
	protected $tpl_path = '';
	protected $tpl_list = [];
	protected $tpl_next = null;

	public function __construct( $tpl_path = '' ) {
		$this->tpl_path = ( $tpl_path == '' ) ? APP_SITE : APP_SITE . '/' . trim( $tpl_path, '/' );
		if ( ! is_dir( $this->tpl_path ) ) throw new \Exception( sprintf( 'Template directory is unreadable: "%s"', $this->tpl_path ) );
	}

	final public function __invoke( $vars = null ) {
		$this->display( $vars );
	}

	public function load() {
		$template = func_get_args();
		foreach( $template as $tmp ) {
			if ( is_string( $tmp ) ) {
				if ( is_file( $this->tpl_path . '/' . $tmp ) ) {
					$this->tpl_list[] = $tmp;
				} else {
					throw new \Exception( sprintf( 'Template is not found: "%s/%s"', $this->tpl_path, $tmp ) );
				}
			}
		}
	}

	protected function fetch() {
		if ( $this->tpl_next === false ) {
			return false;
		} elseif ( $this->tpl_next === null ) {
			$tmp = reset( $this->tpl_list );
		} else {
			$tmp = next( $this->tpl_list );
		}
		if ( $this->tpl_next = $tmp ) {
			return $this->tpl_path . '/' . $tmp;
		}
		return false;
	}

	abstract public function display( $vars = null );
}

abstract class cAction {
	protected $app_cache = null;
	protected $app_path = '';
	final public function __construct( $cache, string $path ) {
		$this->app_cache = $cache;
		$this->app_path = $path;
	}

	public function index() {
		echo 'Default index';
	}

	public function __onload() {
	}

	public function __onerror( $code, $body = '' ) {
		if ( 407 < $code || $code <  401 ) $code = 500;
		http_response_code( $code );
		echo 'Default error handler: ' . $body;
	}
}
