<?php

namespace WPML\TM\ATE\TranslateEverything\TranslatableData;

class Calculate {
	const AVERAGE_CHARS_PER_WORD = 5;

	/**
	 * @param string $content
	 *
	 * @return int
	 */
	public function chars( $content ) {
		$content = strlen(
			preg_replace(
				[
					'/[^@\s]*@[^@\s]*\.[^@\s]*/', // Emails.
					'/[0-9\t\n\r\s]+/', // Spaces.
				],
				'',
				wp_strip_all_tags(
					strip_shortcodes(
						htmlspecialchars_decode( $content )
					)
				)
			)
		);

		return ! $content
			? 0
			: $content;

	}

	/**
	 * @param string $content
	 *
	 * @return int|float
	 */
	public function words( $content ) {
		return $this->chars( $content ) / self::AVERAGE_CHARS_PER_WORD;
	}
}
