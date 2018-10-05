<?php namespace model\acl;
use \model\db\cLDAP;

class cAuthMSAD extends cAuth {

	protected $pt = null;
	protected $args = [];


	public function __construct( $cache, $host, $port, $base, $user, $pass ) {
		$this->args = [ $host, $port, $base, $user, $pass ];
		parent::__construct( $cache );
	}

	public function get_user( string $uid ): array {
		if ( $this->pt === null ) $this->pt = new cLDAP( $this->args[0], $this->args[1], $this->args[2], $this->args[3], $this->args[4] );
		$fff = [];

		$filter = [
			'objectClass' => 'user',
			'objectCategory' => 'person',
			'userPrincipalName' => $uid,
		];
		if ( $this->pt->select( '', [ 'dn', 'displayName', 'objectGUID' ], $filter ) !== 1 ) throw new \EClientError( 403 );
		$t = $this->pt->fetch();

		$this->pt->select( '', [ 'dn', 'objectGUID', 'sAMAccountName' ], [ 'objectClass' => 'group', 'objectCategory' => 'group', 'member:1.2.840.113556.1.4.1941:' => $t[0] ] );

		$fff['name'] = $t[1];
		$fff['secret'] = $t[2];
		while ( $r = $this->pt->fetch() ) {
			$fff['groups'][] = $r;
		}

		return $fff;
	}
}
