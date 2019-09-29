<?php namespace model\acl;
use \model\db\cLDAP;
use \model\cache\iCacher;

class cAuthMSAD extends cAuth {

	protected $pt = null;
	protected $args = [];
	protected $target_groups = [];
	protected $remap_users = [];

	const BIT_AND = ':1.2.840.113556.1.4.803:'; // LDAP_MATCHING_RULE_BIT_AND
	const BIT_OR = ':1.2.840.113556.1.4.804:'; // LDAP_MATCHING_RULE_BIT_OR
	const IN_CHAIN = ':1.2.840.113556.1.4.1941:'; //LDAP_MATCHING_RULE_IN_CHAIN

	public function __construct( iCacher $cache, $host, $port, $base, $user, $pass ) {
		parent::__construct( $cache );
		$this->args = [ $host, $port, $base, $user, $pass ];
	}

	public function set_target_groups() {
		$this->target_groups = func_get_args();
	}

	public function set_remap_users( array $remap ) {
		$this->remap_users = $remap;
	}

	public function fetch_user( string $uid ): array {
		if ( $this->pt === null ) $this->pt = new cLDAP( $this->args[0], $this->args[1], $this->args[2], $this->args[3], $this->args[4] );
		$fff = [];

		if ( $this->remap_users !== null && isset( $this->remap_users[$uid] ) ) {
			$uid = $this->remap_users[$uid];
		}

		$filter = [
			'objectClass' => 'user',
			'objectCategory' => 'person',
			'userPrincipalName' => $uid,
//			'!userAccountControl' . self::BIT_AND => '2',
		];
		if ( $this->pt->select( '', [ 'dn', 'displayName', 'objectGUID' ], $filter ) !== 1 ) throw new \EClientError( 401 );
		$t = $this->pt->fetch();
		$fff['name'] = $t[1];
		$fff['secret'] = $t[2];

		$filter = [
			'objectClass' => 'group',
			'objectCategory' => 'group',
			'member' . self::IN_CHAIN => $t[0],
		];
		$this->pt->select( '', [ 'sAMAccountName' ], $filter );
		if ( empty( $this->target_groups ) ) {
			$fff['groups'] = array_column( $this->pt->fetch_all( false ), 0 );
		} else {
			while ( $r = $this->pt->fetch() ) {
				if ( in_array( $r[0], $this->target_groups ) ) 	$fff['groups'][] = $r[0];
			}
		}

		return $fff;
	}
}
