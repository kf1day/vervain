<?php namespace app;

abstract class cModel {
	/* Is there any common methods 4 all models? May be this class is just useless */
}


abstract class cView {
	private $path = '';
	private $list = [];
	private $next = null;

	public function __construct( $path = '' ) {
		$this->path = ( $path == '' ) ? APP_ROOT : APP_ROOT . '/' . trim( $path, '/' );
		if ( ! is_dir( $this->path ) ) throw new \Exception( sprintf( 'Template directory is unreadable: "%s"', $this->path ) );
	}

	final public function __invoke() {
		call_user_func_array( [ $this, 'display'], func_get_args() );
	}

	public function load() {
		$template = func_get_args();
		foreach( $template as $tmp ) {
			if ( is_string( $tmp ) ) {
				if ( is_file( $this->path . '/' . $tmp ) ) {
					$this->list[] = $tmp;
				} else {
					throw new \Exception( sprintf( 'Template is not found: "%s/%s"', $this->path, $tmp ) );
				}
			}
		}
	}

	protected function fetch() {
		if ( $this->next === false ) {
			return false;
		} elseif ( $this->next === null ) {
			$tmp = reset( $this->list );
		} else {
			$tmp = next( $this->list );
		}
		if ( $this->next = $tmp ) {
			return $this->path . '/' . $tmp;
		}
		return false;
	}

	abstract public function display();
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
