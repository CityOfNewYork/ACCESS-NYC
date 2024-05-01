<?php

namespace WPML\BlockEditor\Blocks\LanguageSwitcher;

use WPML\BlockEditor\Blocks\LanguageSwitcher;
use WPML\BlockEditor\Blocks\LanguageSwitcher\Model\LanguageItem;
use WPML\BlockEditor\Blocks\LanguageSwitcher\Model\LanguageItemTemplate;
use WPML\BlockEditor\Blocks\LanguageSwitcher\Model\LanguageSwitcherTemplate;
use WPML\FP\Obj;

class Render {

	const COLOR_CLASSNAMES_STRING = 'has-text-color has-%s-color';
	const COLOR_STYLE_STRING = 'color:%s;';
	const BACKGROUND_CLASSNAMES_STRING = 'has-background has-%s-background-color';
	const BACKGROUND_STYLE_STRING = 'background-color:%s;';

	/** @var Parser */
	private $parser;

	/** @var Repository */
	private $repository;

	public function __construct( Parser $parser, Repository $repository ) {
		$this->parser     = $parser;
		$this->repository = $repository;
	}

	/**
	 * @param string $savedHTML
	 * @param string $source_block
	 * @param \WP_Block $parent_block
	 *
	 * @return string
	 */
	public function render_block( $blockAttrs, $savedHTML, $parentBlock ) {
		$context                  = $parentBlock->context;
		$languageSwitcherTemplate = $this->parser->parse( $blockAttrs, $savedHTML, $parentBlock, $context );


		$languageSwitcher = $this->repository->getCurrentLanguageSwitcher();

		foreach ( $languageSwitcher->getLanguageItems() as $languageItem ) {
			$isCurrent         = $languageSwitcher->getCurrentLanguageCode() === $languageItem->getCode();
			$parserXPathPrefix = $isCurrent ? Parser::PATH_CURRENT_LANGUAGE_ITEM : Parser::PATH_LANGUAGE_ITEM;

			$languageSwitcherItemTemplate = $isCurrent ?
				$languageSwitcherTemplate->getCurrentLanguageItemTemplate() :
				$languageSwitcherTemplate->getLanguageItemTemplate();

			$this->createLanguageItemNode(
				$languageSwitcherItemTemplate,
				$parserXPathPrefix,
				$languageItem,
				$languageSwitcherTemplate,
				$parentBlock,
				$context
			);
		}

		return $this->getBodyHTML( $languageSwitcherTemplate->getDOMDocument() );
	}

	/**
	 * @param LanguageItemTemplate $languageItemTemplate
	 * @param string $XPathPrefix
	 * @param LanguageItem $languageItem
	 * @param LanguageSwitcherTemplate $languageSwitcherTemplate
	 *
	 * @return \DOMNode|null
	 */
	private function createLanguageItemNode(
		LanguageItemTemplate $languageItemTemplate,
		$XPathPrefix,
		LanguageItem $languageItem,
		LanguageSwitcherTemplate $languageSwitcherTemplate,
		$sourceBlock,
		$context
	) {
		$template  = $languageItemTemplate->getTemplate();
		$container = $languageItemTemplate->getContainer();
		if ( empty( $template ) || empty( $container ) ) {
			return null;
		}
		$newLanguageItem = $template->cloneNode( true );
		$container->appendChild( $newLanguageItem );


		$linkQuery  = $languageSwitcherTemplate->getDOMXPath()->query( $XPathPrefix . '/' . Parser::PATH_ITEM_LINK );
		$textTarget = &$newLanguageItem;
		if ( $linkQuery->length > 0 ) {
			$link = $linkQuery->item( $linkQuery->length - 1 );
			$link->setAttribute( 'href', $languageItem->getUrl() );
			$textTarget = $link;
		}

		if ( $languageItemTemplate->getLabelTemplate() ) {
			$labelQuery = $languageSwitcherTemplate->getDOMXPath()->query( $XPathPrefix . '/' . Parser::PATH_ITEM_LABEL );
			if ( $labelQuery->length > 0 ) {
				$label = $labelQuery->item( $labelQuery->length - 1 );
				if ( $label ) {
					$label->textContent = $languageItemTemplate->getLabelTemplate()->getDisplayName( $languageItem );
				}
			} else {
				$textTarget->textContent = $languageItemTemplate->getLabelTemplate()->getDisplayName( $languageItem );
			}
		}

		$flagQuery = $languageSwitcherTemplate->getDOMXPath()->query( $XPathPrefix . '/' . Parser::PATH_ITEM_FLAG_URL );
		if ( $flagQuery->length > 0 ) {
			$flag = $flagQuery->item( $flagQuery->length - 1 );

			if ( $flag ) {
				$flag->setAttribute( 'src', $languageItem->getFlagUrl() );
			}
		}

		if ( isset( $sourceBlock->attributes['layoutOpenOnClick'] ) ) {
			$dropdownFirstItemQuery = $languageSwitcherTemplate->getDOMXPath()->query( $XPathPrefix . "/ancestor::li" );
			if ( $dropdownFirstItemQuery->length > 0 ) {
				$dpFirstItem = $dropdownFirstItemQuery->item( $dropdownFirstItemQuery->length - 1 );

				if ( $dpFirstItem ) {
					$dpFirstItem->setAttribute(
						'onclick',
						"(()=>{const ariaExpanded = this.children[0].getAttribute('aria-expanded');
					this.children[0].setAttribute('aria-expanded', ariaExpanded === 'true' ? 'false' : 'true');})(this);"
					);
				}
			}
		}

		// Apply some classNames and Styles according to values in context if the current block is Navigation Language Switcher
		// We use values from context to inherit them from the parent Navigation Block
		if ( $sourceBlock->name === LanguageSwitcher::BLOCK_NAVIGATION_LANGUAGE_SWITCHER ) {
			// Apply specific logic only when the language item = current language item
			if ( $XPathPrefix === Parser::PATH_CURRENT_LANGUAGE_ITEM ) {
				$this->maybeApplyColorsForLanguageItems( $languageSwitcherTemplate, $XPathPrefix, $context, true );
			}

			// Apply specific logic only when the language item = secondary language item
			if ( $XPathPrefix === Parser::PATH_LANGUAGE_ITEM ) {
				$this->maybeApplyColorsForLanguageItems( $languageSwitcherTemplate, $XPathPrefix, $context, false );
			}
		}

		return $newLanguageItem;
	}

	/**
	 * @param LanguageSwitcherTemplate $languageSwitcherTemplate
	 * @param string $XPathPrefix
	 * @param array $context
	 * @param bool $isCurrentLanguageItem
	 *
	 * @return void
	 */
	private function maybeApplyColorsForLanguageItems( $languageSwitcherTemplate, $XPathPrefix, $context, $isCurrentLanguageItem ) {
		$langItemQuery     = $languageSwitcherTemplate->getDOMXPath()->query( $XPathPrefix );
		$langItemSpanQuery = $languageSwitcherTemplate->getDOMXPath()->query( $XPathPrefix . "//span[@data-wpml='label']" );

		if ( $langItemQuery->length > 0 ) {
			$langItem     = $langItemQuery->item( $langItemQuery->length - 1 );
			$langItemSpan = $langItemSpanQuery->item( $langItemSpanQuery->length - 1 );

			if ( $langItem && $langItemSpan ) {
				if ( $isCurrentLanguageItem ) {
					$this->maybeApplyColorsForCurrentLanguageItem( $langItem, $langItemSpan, $context );
				} else {
					$this->maybeApplyColorsForLanguageItem( $langItem, $langItemSpan, $context );
				}
			}
		}
	}

	/**
	 * @param \DOMNode $langItem
	 * @param \DOMNode $langItemSpan
	 * @param array $context
	 *
	 * @return void
	 */
	private function maybeApplyColorsForCurrentLanguageItem( $langItem, $langItemSpan, $context ) {
		$namedTextColor        = Obj::propOr( null, 'textColor', $context );
		$namedBackgroundColor  = Obj::propOr( null, 'backgroundColor', $context );
		$customTextColor       = Obj::propOr( null, 'customTextColor', $context );
		$customBackgroundColor = Obj::propOr( null, 'customBackgroundColor', $context );

		if ( isset( $namedTextColor ) ) {
			$this->appendAttributeValueToDOMElement( $langItemSpan, 'class', ' ' . sprintf( self::COLOR_CLASSNAMES_STRING, $namedTextColor ) );
		} elseif ( isset( $customTextColor ) ) {
			$this->appendAttributeValueToDOMElement( $langItemSpan, 'style', sprintf( self::COLOR_STYLE_STRING, $customTextColor ) );
		}

		if ( isset( $namedBackgroundColor ) ) {
			$this->appendAttributeValueToDOMElement( $langItem, 'class', ' ' . sprintf( self::BACKGROUND_CLASSNAMES_STRING, $namedBackgroundColor ) );
		} elseif ( isset( $customBackgroundColor ) ) {
			$this->appendAttributeValueToDOMElement( $langItem, 'style', sprintf( self::BACKGROUND_STYLE_STRING, $customBackgroundColor ) );
		}
	}

	/**
	 * @param \DOMNode $langItem
	 * @param \DOMNode $langItemSpan
	 * @param array $context
	 *
	 * @return void
	 */
	private function maybeApplyColorsForLanguageItem( $langItem, $langItemSpan, $context ) {
		$namedOverlayTextColor        = Obj::propOr( null, 'overlayTextColor', $context );
		$namedOverlayBackgroundColor  = Obj::propOr( null, 'overlayBackgroundColor', $context );
		$customOverlayTextColor       = Obj::propOr( null, 'customOverlayTextColor', $context );
		$customOverlayBackgroundColor = Obj::propOr( null, 'customOverlayBackgroundColor', $context );

		if ( isset( $namedOverlayTextColor ) ) {
			$this->appendAttributeValueToDOMElement( $langItemSpan, 'class', ' ' . sprintf( self::COLOR_CLASSNAMES_STRING, $namedOverlayTextColor ) );
		} elseif ( isset( $customOverlayTextColor ) ) {
			$this->appendAttributeValueToDOMElement( $langItemSpan, 'style', sprintf( self::COLOR_STYLE_STRING, $customOverlayTextColor ) );
		}

		if ( isset( $namedOverlayBackgroundColor ) ) {
			$this->appendAttributeValueToDOMElement( $langItem, 'class', ' ' . sprintf( self::BACKGROUND_CLASSNAMES_STRING, $namedOverlayBackgroundColor ) );
		} elseif ( isset( $customOverlayBackgroundColor ) ) {
			$this->appendAttributeValueToDOMElement( $langItem, 'style', sprintf( self::BACKGROUND_STYLE_STRING, $customOverlayBackgroundColor ) );
		}
	}

	/**
	 * @param \DOMNode $element
	 * @param string $attribute
	 * @param string $value
	 *
	 * @return void
	 */
	private function appendAttributeValueToDOMElement( $element, $attribute, $value ) {
		$currentElementAttributeValue = $this->getDOMElementCurrentAttributeValue( $element, $attribute );
		$element->setAttribute( $attribute, $currentElementAttributeValue . $value );
	}

	/**
	 * @param \DOMNode $element
	 * @param string $attribute
	 *
	 * @return string
	 */
	private function getDOMElementCurrentAttributeValue( $element, $attribute ) {
		return $element->getAttribute( $attribute );
	}

	/**
	 * @param \DOMDocument $DOMDocument
	 *
	 * @return string
	 */
	private function getBodyHTML( $DOMDocument ) {
		$html = $DOMDocument->saveHTML();

		if ( ! $html ) {
			return '';
		}

		$start = strpos( $html, '<body>' );
		$end   = strpos( $html, '</body>' );

		return substr( $html, $start + strlen( '<body>' ), $end - $start - strlen( '<body>' ) ) ?: '';
	}
}

