<?php

namespace WPML\StringTranslation\Application\StringCore\Domain;

class StringItem {

	// Strings registered with all other methods except new autoregistration(including scanned from theme and localisation page).
	const STRING_TYPE_DEFAULT = 0;
	// Strings registered with new autoregistration feature, introduced in WPML 4.7.
	const STRING_TYPE_AUTOREGISTER = 1;

	// String type is unknown by default for compatibility with already existing and not autoregistered yet strings.
	const COMPONENT_TYPE_UNKNOWN = 0;
	const COMPONENT_TYPE_PLUGIN = 1;
	const COMPONENT_TYPE_THEME = 2;
	const COMPONENT_TYPE_CORE = 3;

	// “End of Transmission” character (U+0004, or "\4" in PHP).
	// It’s the same delimiter as in gettext used to glue the context with the singular string. Also used in WP core from 6.5.
	const EOT_CHARACTER = '\4';

	/** @var int|null */
	private $id;

	/** @var string */
	private $language;

	/** @var string */
	private $domain;

	/** @var string|null */
	private $context;

	/** @var string */
	private $value;

	/** @var int */
	private $status;

	/** @var string */
	private $name;

	/** @var string */
	private $domainNameContextMd5;

	/** @var string|null */
	private $componentId;

	/** @var string */
	private $componentType;

	/** @var int */
	private $stringType;

	/** @var StringPosition[] */
	private $positions = [];

	/** @var StringTranslation[] */
	private $translations = [];

	/**
	 * @param string      $language
	 * @param string      $domain
	 * @param string|null $context
	 * @param string      $value
	 * @param int         $status
	 * @param string|null $name
	 * @param string|null $componentId
	 * @param int         $componentType
	 * @param int         $stringType
	 */
	public function __construct(
		string $language = '',
		string $domain = '',
		string $context = null,
		string $value = '',
		int $status = ICL_TM_NOT_TRANSLATED,
		string $name = null,
		string $componentId = null,
		int $componentType = self::COMPONENT_TYPE_UNKNOWN,
		int $stringType = self::STRING_TYPE_DEFAULT
	) {
		$this->language      = $language;
		$this->domain        = $domain;
		$this->context       = $context;
		$this->value         = $value;
		$this->status        = $status;
		$this->componentId   = $componentId;
		$this->componentType = $componentType;
		$this->stringType    = $stringType;

		if ( ! $name ) {
			$name = md5( $value );
		}
		$this->name = (string) $name;

		$this->domainNameContextMd5 = md5( $domain . $name . $context );
	}

	public static function parseTextAndContextKey( string $textAndContext ): array {
		$res = explode( self::EOT_CHARACTER, $textAndContext );
		return count( $res ) > 1 ? $res : [ $res[0], null ];
	}

	public static function createTextAndContextKey( string $text, string $context = null ): string {
		return is_string( $context ) && strlen( $context ) > 0
			? $text . self::EOT_CHARACTER . $context
			: $text;
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
		return ! is_null( $this->id );
	}

	public function getLanguage(): string {
		return $this->language;
	}

	public function setLanguage( string $language ) {
		$this->language = $language;
	}

	public function getDomain(): string {
		return $this->domain;
	}

	public function setDomain( string $domain ) {
		$this->domain = $domain;
	}

	/**
	 * @return string|null
	 */
	public function getContext() {
		return $this->context;
	}

	public function setContext( string $context ) {
		$this->context = $context;
	}

	public function getValue(): string {
		return $this->value;
	}

	public function setValue( string $value ) {
		$this->value = $value;
	}

	/*
	 * Example: string '&lt;h2&gt;Subscribe To Our Newsletter&lt;/h2&gt;'.
	 * After html_entity_decode: '<h2>Subscribe To Our Newsletter</h2>'.
	 * After strip_tags: 'Subscribe To Our Newsletter'.
	 */
	public static function filterOnlyTextFromValue( string $value ): string {
		return (string) trim( preg_replace( '/\s+/', ' ', strip_tags( html_entity_decode( $value ) ) ) );
	}

	public function getStatus(): int {
		return $this->status;
	}

	public function setStatus( int $status ) {
		$this->status = $status;
	}

	/**
	 * @param string[] $allLanguageCodes
	 */
	public function refreshStatus( string $defaultLanguageCode, array $allLanguageCodes ) {
		if ( count( $allLanguageCodes ) === 0 || count( $this->translations ) === 0 ) {
			return;
		}

		if ( $defaultLanguageCode !== 'en' && $defaultLanguageCode !== $this->language ) {
			$allLanguageCodes[] = $defaultLanguageCode;
		}

		if ( in_array( $this->language, $allLanguageCodes ) ) {
			$allLanguageCodes = array_filter(
				$allLanguageCodes,
				function( $value ) {
					return $value !== $this->language;
				}
			);
		}

		$translatedLanguageCodes = array_map(
			function( StringTranslation $translation ) {
				return $translation->getLanguage();
			},
			$this->translations
		);

		$translatedLanguagesCount = count( array_intersect( $allLanguageCodes, $translatedLanguageCodes ) );

		if ( $translatedLanguagesCount === 0 ) {
			$this->status = ICL_STRING_TRANSLATION_NOT_TRANSLATED;
			return;
		}

		if ( $translatedLanguagesCount < count( $allLanguageCodes ) ) {
			$this->status = ICL_STRING_TRANSLATION_PARTIAL;
			return;
		}

		$this->status = ICL_STRING_TRANSLATION_COMPLETE;
	}

	/**
	 * @return string|null
	 */
	public function getName() {
		return $this->name;
	}

	public function setName( string $name ) {
		$this->name = $name;
	}

	public function getDomainNameContextMd5(): string {
		return $this->domainNameContextMd5;
	}

	public function setComponentId( string $componentId = null ) {
		$this->componentId = $componentId;
	}

	/**
	 * @return string|null
	 */
	public function getComponentId() {
		return $this->componentId;
	}

	/**
	 * @param int $componentType
	 */
	public function setComponentType( int $componentType ) {
		$this->componentType = $componentType;

		$types = [
			self::COMPONENT_TYPE_PLUGIN,
			self::COMPONENT_TYPE_CORE,
			self::COMPONENT_TYPE_THEME,
		];

		if ( ! in_array( $this->componentType, $types ) ) {
			$this->componentType = self::COMPONENT_TYPE_UNKNOWN;
		}
	}

	/**
	 * @return int
	 */
	public function getComponentType(): int {
		return $this->componentType;
	}

	public function setStringType( int $stringType ) {
		$this->stringType = $stringType;
	}

	public function getStringType(): int {
		return $this->stringType;
	}

	public function addPosition( StringPosition $position ) {
		$this->positions[] = $position;
	}

	/**
	 * @return StringPosition[]
	 */
	public function getPositions(): array {
		return $this->positions;
	}

	public function unsetPositions() {
		$this->positions = [];
	}

	/**
	 * @return StringPosition[]
	 */
	public function getNewPositions(): array {
		return array_filter(
			$this->getPositions(),
			function( $position ) {
				return ! $position->hasId();
			}
		);
	}

	public function getDomainValueAndContextKey(): string {
		return $this->getDomain() . $this->getValue() . ( $this->getContext() ?? '' );
	}

	public function addTranslation( StringTranslation $translation ) {
		$this->translations[] = $translation;
	}

	/**
	 * @return StringTranslation[]
	 */
	public function getTranslations(): array {
		return $this->translations;
	}
}