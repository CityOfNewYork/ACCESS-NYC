<?php

namespace WPML\Core\Component\ATE\Application\Query;

use WPML\Core\Component\ATE\Application\Query\Dto\AccountBalanceDto;
use WPML\Core\Component\ATE\Application\Query\Dto\CreditInfoDto;

interface AccountInterface {


  /**
   * @return CreditInfoDto
   * @throws AccountException
   */
  public function getCredits(): CreditInfoDto;


   /**
  * @return AccountBalanceDto
  * @throws AccountException
  */
  public function getAccountBalances(): AccountBalanceDto;


}
