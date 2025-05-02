<?php

namespace WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetCredits;

use WPML\Core\Component\ATE\Application\Query\AccountException;
use WPML\Core\Component\ATE\Application\Query\AccountInterface;
use WPML\Core\Port\Endpoint\EndpointInterface;

class GetCreditsController implements EndpointInterface {

  /** @var AccountInterface */
  private $ateAccount;


  public function __construct( AccountInterface $ateAccount ) {
    $this->ateAccount = $ateAccount;
  }


  /**
   * @param array<string,mixed> $requestData
   *
   * @return array<string, mixed>
   * @throws AccountException
   *
   */
  public function handle( $requestData = null ): array {
    $credits = $this->ateAccount->getCredits();

    return [
      'success' => true,
      'data'    => [
        'available_balance'      => $credits->getAvailableBalance(),
        'payAsYouGoSubscription' => $credits->getPayAsYouGo(),
        'totalCreditsDeposited'  => $credits->getTotalCreditsDeposited(),
        'totalCreditsSpent'      => $credits->getTotalCreditsSpent(),
        'subscriptionUsage'      => $credits->getSubscriptionUsage(),
      ]
    ];
  }


}
