<?php namespace app;

abstract class cModel {
	/* Is there any common methods 4 all models? May be this class is just useless */
}


abstract class cView {
	private $path = '';
	private $list = [];
	private $next = false;

	public function __construct( $path = null ) {
		$this->path = ( $path === null ) ? APP_ROOT : APP_ROOT . '/' . trim( $path, '/' );
		if ( ! is_dir( $this->path ) ) throw new \Exception( sprintf( 'Template directory is unreadable: "%s"', $this->path ) );
	}

	final public function __invoke() {
		call_user_func_array( [ $this, 'display'], func_get_args() );
	}

	public function load() {
		if ( $this->next === false ) {
			$template = func_get_args();
			foreach( $template as $tmp ) {
				if ( is_string( $tmp ) ) {
					if ( is_file( $this->path . '/' . $tmp ) ) {
						$this->list[] = $tmp;
					} else {
						throw new \Exception( sprintf( 'Template is not found: "%s/%s"', $this->path, $tmp ) );
					}
				} else {
					throw new \Exception( 'Template should be a string pathname' );
				}
			}
		} else {
			throw new \Exception( 'Cannot load templates while output is processing' );
		}
	}

	protected function fetch() {
		$tmp = ( $this->next === false ) ? reset( $this->list ) : next( $this->list );
		if ( $tmp === false ) {
			$this->next = false;
			return false;
		} else {
			$this->next = true;
			return $this->path . '/' . $tmp;
		}
	}

	protected function ob_html_closure( $buf ) {
		$buf = preg_replace( '/<!--.*?-->|(?<=>)\s+(?=<)/', '', $buf );
		return $buf;
	}

	abstract public function display();
}

abstract class cAction {
	protected $PATH = '';
	final public function __construct( string $path ) {
		$this->PATH = $path;
	}

	public function index() {
		echo 'Default index';
	}

	public function __onload() {
	}

	public function __onerror( int $code, string $body = '' ) {
		printf( 'Default error handler: %s (%d)', $body, $code );
	}
}
