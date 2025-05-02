<?php

namespace WPML\StringTranslation\Infrastructure\StringHtml\Command;

use WPML\StringTranslation\Infrastructure\StringGettext\Command\ParseStringTextAndPlaceholders;
use WPML\StringTranslation\Application\StringCore\Domain\StringItem;

class MatchHtmlStringWithGettextStrings {

	/** @var ParseStringTextAndPlaceholders */
	private $parseStringTextAndPlaceholders;

	public function __construct(
		ParseStringTextAndPlaceholders $parseStringTextAndPlaceholders
	) {
		$this->parseStringTextAndPlaceholders = $parseStringTextAndPlaceholders;
	}

	/**
	 * @param array<array{string, string, string|null}> $gettextStrings
	 *
	 * @return array<array{string, string, string|null}>
	 */
	public function run( array $gettextStrings, string $htmlString ): array {
		$matchedGettextStrings = [];

		foreach ( $gettextStrings as $gettextString ) {
			if ( $this->doesHtmlStringMatchGettextString( $gettextString, $htmlString ) ) {
				$matchedGettextStrings[] = $gettextString;
				continue;
			}

			$value                     = StringItem::filterOnlyTextFromValue( $gettextString[0] );
			$customPlaceholderStartPos = strpos( $value, '[' );
			$customPlaceholderEndPos   = strpos( $value, ']' );
			$hasCustomPlaceholder      = (
				$customPlaceholderStartPos !== false &&
				$customPlaceholderEndPos   !== false &&
				$customPlaceholderStartPos < $customPlaceholderEndPos
			);

			if ( ! $hasCustomPlaceholder ) {
				continue;
			}

			$pattern              = '/(?<!\[ )\[([^\[\]]+)\]/';
			$hasCustomPlaceholder = preg_match($pattern, $value, $matches);
			if ( ! $hasCustomPlaceholder ) {
				continue;
			}

			$gettextStringCopy    = $gettextString;
			$gettextStringCopy[0] = preg_replace('/\[.*?\]/', '%s', $gettextStringCopy[0] );
			if ( $this->doesHtmlStringMatchGettextString( $gettextStringCopy, $htmlString ) ) {
				$matchedGettextStrings[] = $gettextString;
			}
		}

		return $matchedGettextStrings;
	}

	/**
	 * @param array{string, string, string|null} $gettextString
	 */
	private function doesHtmlStringMatchGettextString( array $gettextString, string $htmlString ): bool {
		$nodes = $this->parseStringTextAndPlaceholders->run( StringItem::filterOnlyTextFromValue( $gettextString[0] ) );
		$nodes = $this->parseTextNodes( $nodes, $htmlString );

		if ( is_null( $nodes ) ) {
			return false;
		}

		$nodes = $this->parsePlaceholderNodes( $nodes, $htmlString );

		$placeholdersCount         = 0;
		$notEmptyPlaceholdersCount = 0;
		$emptyPlaceholdersCount    = 0;
		foreach ( $nodes as $node ) {
			if ( $node['type'] !== 'placeholder' ) {
				continue;
			}
			$placeholdersCount++;

			$words = array_values( array_filter( explode( ' ', $node['htmlStringText'] ) ) );
			if ( count( $words ) === 0 ) {
				$emptyPlaceholdersCount++;
			} else {
				$notEmptyPlaceholdersCount++;
			}
		}

		if ( $placeholdersCount === 0 ) {
			/*
			 * When we match html string to gettext string we should allow redundant text in the start or in the end of the html string.
			 * This should cover the case when gettext string is outputted together with some text near it.
			 * From other side we should disable this behaviour for posts - we could match by accident part of the some post sentence
			 * with some gettext string even if it was never outputted there. Such collisions almost always happen only on gettext strings
			 * which contains only single word, so we should allow redundant text for such cases only for short strings, which should
			 * cover most of the cases.
			 * Example:
			 *     gettext string = 'Older Posts' outputted into html as string and some extra text:
			 *     1) '<div><?php echo __('Older Posts', 'd'); ?> ExtraText</div>'. // Should match
			 *     2) '<div>ExtraText <?php echo __('Older Posts', 'd'); ?></div>'. // Should match
			 *     3) '<div>ExtraText <?php echo __('Older Posts', 'd'); ?> ExtraText</div>'. // Should match
			 *     4) '<p>Some part of the post with Older Posts text part as a part of the post</p>'. // Should not match
			 */
			$gettextStringWordsCount = count( explode( ' ', StringItem::filterOnlyTextFromValue( $gettextString[0] ) ) );
			$htmlStringWordsCount    = count( explode( ' ', $htmlString ) );
			return ( $gettextStringWordsCount >= 2 ) || ( $gettextStringWordsCount === 1 && $htmlStringWordsCount <= 3 );
		}

		/*
		 * Should allow following case:
		 *    Gettext string: 'Ready to publish your first post? %1$sGet started here%2$s.'
		 *    Matches to html string: 'Ready to publish your first post? Get started here.'
		 * So, placeholders can be empty in such case, but we should not allow this on short strings,
		 * otherwise extra redundant matches can occur.
		 * We have >= in condition to cover case when only some of the placeholders are empty.
		 */
		if ( $emptyPlaceholdersCount > 0 && $notEmptyPlaceholdersCount >= 0 ) {
			$words = explode( ' ', $htmlString );
			return count( $words ) >= 3;
		}

		// Normally we do not allow empty placeholders.
		return $emptyPlaceholdersCount === 0;
	}

	/**
	 * @return array|null
	 */
	private function parseTextNodes( array $nodes, string $htmlString ) {
		$lastMatchPos = -1;
		$lastMatchStr = '';
		for ( $i = 0; $i < count( $nodes ); $i++ ) {
			$node = $nodes[ $i ];
			if ( $node['type'] !== 'text' ) {
				continue;
			}

			// All non-placeholder nodes from gettext string should have direct match in html string in the same order.
			$minSearchOffset = ( $lastMatchPos !== -1 ) ? $lastMatchPos + strlen( $lastMatchStr ) - 1 : 0;
			$matchPos        = strpos( $htmlString, $node['text'], $minSearchOffset );
			if ( $matchPos === false || $matchPos < $lastMatchPos ) {
				return null;
			}

			$nodes[ $i ]['offsetInHtmlString']    = $matchPos;
			$nodes[ $i ]['offsetEndInHtmlString'] = $matchPos + strlen( $node['text'] ) - 1;
			$nodes[ $i ]['htmlStringText']        = $node['text'];
			$lastMatchPos = $matchPos;
			$lastMatchStr = $node['text'];
		}

		return $nodes;
	}

	private function parsePlaceholderNodes( array $nodes, string $htmlString ): array {
		$lastTextNode = null;
		for ( $i = 0; $i < count( $nodes ); $i++ ) {
			$node = $nodes[ $i ];
			if ( $node['type'] === 'text' ) {
				$lastTextNode = $node;
				continue;
			}

			$nextTextNode = null;
			for ( $j = $i + 1; $j < count( $nodes ); $j++ ) {
				if ( $nodes[ $j ]['type'] === 'text' ) {
					$nextTextNode = $nodes[ $j ];
					break;
				}
			}

			// Two placeholders in a row '%s%s' will capture the same text(as we cannot determine boundaries in that case).
			$nodes[ $i ]['offsetInHtmlString'] = $lastTextNode ? $lastTextNode['offsetEndInHtmlString'] + 1 : 0;
			$nodes[ $i ]['offsetEndInHtmlString'] = $nextTextNode ? $nextTextNode['offsetInHtmlString'] - 1 : strlen( $htmlString ) - 1;
			$nodes[ $i ]['htmlStringText'] = substr(
				$htmlString,
				$nodes[ $i ]['offsetInHtmlString'],
				$nodes[ $i ]['offsetEndInHtmlString'] - $nodes[ $i ]['offsetInHtmlString'] + 1
			);
		}

		return $nodes;
	}
}