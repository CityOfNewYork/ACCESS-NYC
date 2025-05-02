<?php
/**
 * @phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
 */
namespace WPML\Infrastructure\WordPress\Component\User\Application\Query;

use WPML\Core\SharedKernel\Component\User\Application\Query\Dto\UserDto;
use WPML\Core\SharedKernel\Component\User\Application\Query\UserQueryInterface;

class UserQuery implements UserQueryInterface {


  /**
   * @return UserDto|null
   */
  public function getCurrent() {
    $currentUser = \wp_get_current_user();

    if ( $currentUser->ID === 0 ) {
      return null;
    }

    return new UserDto(
      $currentUser->ID,
      $currentUser->display_name,
      $currentUser->user_email
    );
  }


}
