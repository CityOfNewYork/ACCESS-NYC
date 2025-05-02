<?php

namespace WPML\TM\ATE\AutoTranslate\Endpoint;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\TM\API\ATE\Account;

class GetAccountBalances implements IHandler {

	public function run( Collection $data ) {
		return Account::getAccountBalances();
	}
}
