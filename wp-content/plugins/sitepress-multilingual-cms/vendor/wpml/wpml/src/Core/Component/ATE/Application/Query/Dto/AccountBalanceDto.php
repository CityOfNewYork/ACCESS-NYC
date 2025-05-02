<?php

namespace WPML\Core\Component\ATE\Application\Query\Dto;

class AccountBalanceDto {

  /**
   * @var int
   */
  private $accountBalance;

  /**
   * @var string
   */
  private $redirectUrl;


  public function __construct(
    int $accountBalance,
    string $redirectUrl
  ) {
    $this->accountBalance = $accountBalance;
    $this->redirectUrl = $redirectUrl;
  }


  public function getAccountBalance(): int {
    return $this->accountBalance;
  }


  public function getRedirectUrl(): string {
    return $this->redirectUrl;
  }


}
