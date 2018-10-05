<?php namespace model\db;

interface iSQL {

	public function get( string $table, array $fields, $filter = null, $sort = null );
	public function select( string $table, array $fields, $filter = null, $sort = null );
	public function insert( string $table, array $keyval );
	public function update( string $table, array $keyval, array $filter );
	public function delete( string $table, array $filter );
	public function fetch();
	public function fetch_all();

}
