<?php

namespace WPML\StringTranslation\Infrastructure\StringCore\Command;

use WPML\StringTranslation\Application\StringCore\Command\InsertStringsCommandInterface;
use WPML\StringTranslation\Application\StringCore\Command\UpdateStringsCommandInterface;
use WPML\StringTranslation\Application\StringCore\Command\SaveStringsCommandInterface;
use WPML\StringTranslation\Application\StringCore\Domain\StringItem;
use WPML\StringTranslation\Application\StringCore\Domain\StringPosition;
use WPML\StringTranslation\Application\StringCore\Query\Criteria\DomainValueAndContextCriteria;
use WPML\StringTranslation\Application\StringCore\Query\FindByDomainValueAndContextQueryInterface;

class SaveStringsCommand implements SaveStringsCommandInterface {

	/** @var FindByDomainValueAndContextQueryInterface */
	private $findByDomainValueAndContextQuery;

	/** @var InsertStringsCommandInterface */
	private $insertStringsCommand;

	/** @var UpdateStringsCommandInterface */
	private $updateStringsCommand;

	public function __construct(
		FindByDomainValueAndContextQueryInterface $findByDomainValueAndContextQuery,
		InsertStringsCommandInterface             $insertStringsCommand,
		UpdateStringsCommandInterface             $updateStringsCommand
	) {
		$this->findByDomainValueAndContextQuery = $findByDomainValueAndContextQuery;
		$this->insertStringsCommand             = $insertStringsCommand;
		$this->updateStringsCommand             = $updateStringsCommand;
	}

	/**
	 * @param StringItem[] $allStrings
	 *
	 * @return StringItem[]
	 */
	private function partStringsBySameComponentIdAndType( array $allStrings ): array {
		$strings = [];

		foreach ( $allStrings as $string ) {
			$componentIdAndType = $string->getComponentId() . $string->getComponentType();
			if ( ! array_key_exists( $componentIdAndType, $strings ) ) {
				$strings[ $componentIdAndType ] = [];
			}

			$strings[ $componentIdAndType ][] = $string;
		}

		return $strings;
	}

	/**
	 * Same string can come from multiple urls, so we should keep only one copy for each string for correct processing.
	 *
	 * @param StringItem[] $allStrings
	 *
	 * @return StringItem[]
	 */
	private function filterOutStringDuplicates( array $allStrings ): array {
		$foundKeys = [];
		$strings   = [];

		foreach ( $allStrings as $string ) {
			$key = $string->getDomainValueAndContextKey() . ( $string->getName() ?? '' );
			if ( in_array( $key, $foundKeys ) ) {
				continue;
			}

			$foundKeys[] = $key;
			$strings[]   = $string;
		}

		return $strings;
	}

	/**
	 * @param StringItem[] $strings
	 *
	 * @return StringItem[]
	 */
	private function findStringsByDomainValueAndContext( array $strings, array $fields ): array {
		$criteria = new DomainValueAndContextCriteria( $strings, $fields );
		return $this->findByDomainValueAndContextQuery->execute( $criteria );
	}

	/**
	 * @param StringItem[] $strings
	 */
	public function run( array $strings ) {
		$strings = $this->filterOutStringDuplicates( $strings );

		$this->insertStringsCommand->run( $strings );
		$strings = $this->findStringsByDomainValueAndContext( $strings, ['id', 'positions'] );

		$this->saveAutoregisterData( $strings );
	}

	/**
	 * @param StringItem[] $strings
	 */
	private function saveAutoregisterData( array $strings ) {
		if ( count( $strings ) ) {
			$this->updateStringsCommand->run( $strings, ['string_type'], [StringItem::STRING_TYPE_AUTOREGISTER] );
			$stringsByCmpIdAndType = $this->partStringsBySameComponentIdAndType( $strings );
			foreach ( $stringsByCmpIdAndType as $componentIdAndType => $groupStrings ) {
				$componentId   = substr( $componentIdAndType, 0, -1 );
				$componentType = substr( $componentIdAndType, -1 );
				$this->updateStringsCommand->run( $groupStrings, ['component_id', 'component_type'], [ $componentId, $componentType ] );
			}
		}
	}
}