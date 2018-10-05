<?php namespace view;

class cJSON extends \app\cView {

	public function display( $vars = null ) {
		header( 'Content-Type: text/json; charset=utf-8' );
		echo json_encode( $vars, JSON_UNESCAPED_UNICODE );
	}
}
