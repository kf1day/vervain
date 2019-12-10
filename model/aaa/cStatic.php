<?php namespace model\aaa;

class cStatic extends cGeneric {

	protected $staticdb = [];

	public function __construct( array $db ) {
		$this->staticdb = $db;
	}

	protected function get_pass() {
		return $this->staticdb[$this->user]['secret'] ?? null;
	}

	protected function get_data() {
		$this->data = $this->staticdb[$this->user];
	}

}
