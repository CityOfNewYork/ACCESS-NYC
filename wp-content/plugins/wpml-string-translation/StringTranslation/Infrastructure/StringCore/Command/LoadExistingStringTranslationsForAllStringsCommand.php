<?php

namespace WPML\StringTranslation\Infrastructure\StringCore\Command;

use WPML\StringTranslation\Application\StringCore\Command\LoadExistingStringTranslationsCommandInterface;
use WPML\StringTranslation\Application\StringCore\Command\LoadExistingStringTranslationsForAllStringsCommandInterface;
use WPML\StringTranslation\Application\StringCore\Domain\StringItem;
use WPML\StringTranslation\Application\StringCore\Query\FindAllStringsQueryInterface;
use WPML\StringTranslation\Application\StringCore\Query\FindAllStringsCountQueryInterface;
use WPML\StringTranslation\Application\StringCore\Query\Criteria\SearchCriteria;
use WPML\StringTranslation\Application\StringCore\Query\Criteria\SearchSelectCriteria;

class LoadExistingStringTranslationsForAllStringsCommand implements LoadExistingStringTranslationsForAllStringsCommandInterface {

	const BATCH_SIZE = 1000;

	/** @var LoadExistingStringTranslationsCommandInterface */
	private $loadExistingStringTranslationsCommand;

	/** @var FindAllStringsQueryInterface */
	private $findAllStringsQuery;

	/** @var FindAllStringsCountQueryInterface */
	private $findAllStringsCountQuery;

	public function __construct(
		LoadExistingStringTranslationsCommandInterface $loadExistingStringTranslationsCommand,
		FindAllStringsQueryInterface                   $findAllStringsQuery,
		FindAllStringsCountQueryInterface              $findAllStringsCountQuery
	) {
		$this->loadExistingStringTranslationsCommand = $loadExistingStringTranslationsCommand;
		$this->findAllStringsQuery                   = $findAllStringsQuery;
		$this->findAllStringsCountQuery              = $findAllStringsCountQuery;
	}

	public function run( array $criteria = [] ) {
		$criteria          = new SearchCriteria();
		$totalStringsCount = $this->findAllStringsCountQuery->execute( $criteria );
		for ( $i = 0; $i < $totalStringsCount; $i += self::BATCH_SIZE ) {
			$offset = $i;
			$limit  = self::BATCH_SIZE;

			$criteria = new SearchCriteria(
				null,
				null,
				null,
				null,
				null,
				null,
				null,
				null,
				[],
				$limit,
				$offset
			);
			$selectCriteria = new SearchSelectCriteria(['id', 'gettext_context', 'context', 'value']);
			$strings = $this->createStrings( $this->findAllStringsQuery->execute( $criteria, $selectCriteria ) );

			$this->loadExistingStringTranslationsCommand->run( $strings );

			unset( $strings );
		}
	}

	/**
	 * @param StringItem[] $strings
	 */
	private function createStrings( array $strings ) {
		return array_map(
			function( $string ) {
				$item = new StringItem(
					'en',
					$string->getDomain(),
					$string->getContext(),
					$string->getValue()
				);
				$item->setId( $string->getId() );

				return $item;
			},
			$strings
		);
	}
}