<?php

namespace WPML\Core\Component\ATE\Application\Query\Dto;

class CreditInfoDto {

  /**
   * @var int
   */
  private $freeCreditsAmount;

  /**
   * @var bool
   */
  private $activeSubscription;

  /**
   * @var int|null
   */
  private $subscriptionMaxLimit;

  /**
   * @var int
   */
  private $subscriptionUsage;

  /**
   * @var int
   */
  private $availableBalance;

  /**
   * @var int
   */
  private $totalCreditsDeposited;

  /**
   * @var int
   */
  private $totalCreditsSpent;

  /**
   * @var bool
   */
  private $payAsYouGo;


  public function __construct(
    int $freeCreditsAmount,
    bool $activeSubscription,
    int $subscriptionUsage,
    int $availableBalance,
    int $totalCreditsDeposited,
    int $totalCreditsSpent,
    bool $payAsYouGo,
    int $subscriptionMaxLimit = null
  ) {
    $this->freeCreditsAmount     = $freeCreditsAmount;
    $this->activeSubscription    = $activeSubscription;
    $this->subscriptionMaxLimit  = $subscriptionMaxLimit;
    $this->subscriptionUsage     = $subscriptionUsage;
    $this->availableBalance      = $availableBalance;
    $this->totalCreditsDeposited = $totalCreditsDeposited;
    $this->totalCreditsSpent     = $totalCreditsSpent;
    $this->payAsYouGo            = $payAsYouGo;
  }


  public function getFreeCreditsAmount(): int {
    return $this->freeCreditsAmount;
  }


  public function getActiveSubscription(): bool {
    return $this->activeSubscription;
  }


  /**
   * @return int|null
   */
  public function getSubscriptionMaxLimit() {
    return $this->subscriptionMaxLimit;
  }


  public function getSubscriptionUsage(): int {
    return $this->subscriptionUsage;
  }


  public function getAvailableBalance(): int {
    return $this->availableBalance;
  }


  public function getTotalCreditsDeposited(): int {
    return $this->totalCreditsDeposited;
  }


  public function getTotalCreditsSpent(): int {
    return $this->totalCreditsSpent;
  }


  public function getPayAsYouGo(): bool {
    return $this->payAsYouGo;
  }


}
