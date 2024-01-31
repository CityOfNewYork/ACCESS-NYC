<?php

namespace WPML\User;

use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\User\LanguagePairs\ILanguagePairs;
use function WPML\FP\pipe;

class UsersByCapsRepository {

	/** @var wpdb */
	private $wpdb;

	/** @var ILanguagePairs */
	private $languagePairs;

	public function __construct( \wpdb $wpdb, ILanguagePairs $languagePairs ) {
		$this->wpdb          = $wpdb;
		$this->languagePairs = $languagePairs;
	}

	/**
	 * @param string[] $ownedCaps
	 * @param string[] $excludeCaps
	 *
	 * @return array{
	 *     "ID": string,
	 *     "full_name": string,
	 *     "user_login": string,
	 *     "user_email": string,
	 *     "display_name": string,
	 *     "roles": string[],
	 *     "language_pairs": array{string:string[]}
	 *  }[]
	 */
	public function get( array $ownedCaps, array $excludeCaps = [] ) {
		$sql = "
			SELECT user_id
			FROM {$this->wpdb->usermeta}
			WHERE {$this->buildWhereCondition( $ownedCaps, $excludeCaps)}
		";

		$userIds = $this->wpdb->get_col( $sql );

		$buildUsers = pipe(
			Fns::map( 'get_userdata' ),
			Fns::filter( Fns::identity() ),
			Fns::map( function ( \WP_User $userData ) {
				return (object) [
					'ID'           => $userData->ID,
					'full_name'    => trim( $userData->first_name . ' ' . $userData->last_name ),
					'user_login'   => $userData->user_login,
					'user_email'   => $userData->user_email,
					'display_name' => $userData->display_name,
					'roles'        => $userData->roles
				];
			} ),
			Fns::map( function ( $user ) {
				$user->language_pairs = $this->languagePairs->get( $user->ID );

				return $user;
			} )
		);

		return $buildUsers( $userIds );
	}

	private function buildWhereCondition( array $ownedCaps, array $excludeCaps ) {
		$ownedCapsCondition = Lst::join( ' OR ', Fns::map( function ( $cap ) {
			return $this->wpdb->prepare('meta_value LIKE %s', '%' . $cap . '%' );
		}, $ownedCaps ) );

		if ( $excludeCaps ) {
			$excludeCapsCondition = ' AND ( ' . Lst::join( ' AND ', Fns::map( function ( $cap ) {
					return $this->wpdb->prepare('meta_value NOT LIKE %s', '%' . $cap . '%' );
				}, $excludeCaps ) ) . ' )';
		} else {
			$excludeCapsCondition = '';
		}

		return "meta_key = '{$this->wpdb->prefix}capabilities' AND ( $ownedCapsCondition ) $excludeCapsCondition";
	}
}