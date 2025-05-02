<?php

namespace WPML\StringTranslation\Application\StringCore\Domain;

class StringPosition {

	/** @var int|null */
	private $id;

	/**
	 * There are 7 constants defined in /inc/constants.php:
	 *     This is a string scanned with a scanner from Theme and Localisation page.
	 *     define( 'ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_SOURCE', 0 );
	 *     This is a string which was captured with old string tracking feature(before WPML 4.7).
	 *     define( 'ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_PAGE', 1 );
	 *     This is a string which was captured with old string tracking feature(before WPML 4.7).
	 *     define( 'ICL_STRING_TRANSLATION_STRING_TRACKING_THRESHOLD', 5 );
	 *     Next 4 types are for the new autoregistration feature introduced in WPML 4.7.
	 *     define( 'ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_FRONTEND', 6 );
	 *     define( 'ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_BACKEND', 7 );
	 *     define( 'ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_REST', 8 );
	 *     define( 'ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_AJAX', 9 );
	 *
	 * First 3 are legacy ones, last 4 are related to new /StringTranslation structure.
	 * We need to define those new constants in that file too instead of this class because
	 * we need to use those constants in legacy part of the code too.
	 *
	 * @var int
	 */
	private $kind;

	/** @var string */
	private $positionInPage;

	/** @var StringItem|null */
	private $string;

	/**
	 * @param StringItem|null $string
	 */
	public function __construct( int $kind, string $positionInPage, $string ) {
		$this->setKind( $kind );
		$this->setPositionInPage( $positionInPage );
		$this->setString( $string );
	}

	public function setId( int $id ) {
		$this->id = $id;
	}

	/**
	 * @return int|null
	 */
	public function getId() {
		return $this->id;
	}

	public function hasId(): bool {
		return ! is_null( $this->getId() );
	}

	public function setKind( int $kind ) {
		$this->kind = $kind;
	}

	public function getKind(): int {
		return $this->kind;
	}

	public function setPositionInPage( string $positionInPage ) {
		$this->positionInPage = $positionInPage;
	}

	public function getPositionInPage(): string {
		return $this->positionInPage;
	}

	public function setString( StringItem $string ) {
		$this->string = $string;
	}

	public function getString(): StringItem {
		return $this->string;
	}

	public function isEqualTo( StringPosition $position ) {
		return (
			$this->getString()->getId() === $position->getString()->getId() &&
			$this->getKind() === $position->getKind() &&
			$this->getPositionInPage() === $position->getPositionInPage()
		);
	}
}