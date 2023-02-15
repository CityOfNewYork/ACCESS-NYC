<?php

namespace OTGS\Installer\AdminNotices;

class MessageTexts {
	/**
	 * @var array
	 */
	private $messages;

	/**
	 * MessageTexts constructor.
	 *
	 * @param array $messages
	 */
	public function __construct( array $messages ) {
		$this->messages = $messages;
	}

	/**
	 * @param string $repo
	 * @param string $messageId
	 *
	 * @return string|null
	 */
	public function get( $repo, $messageId, $parameters = [] ) {
		if ( isset( $this->messages['repo'][ $repo ][ $messageId ] ) ) {
			if ( ! empty( $parameters ) ) {
				return call_user_func(
					$this->messages['repo'][ $repo ][ $messageId ],
					$parameters
				);
			} else {
				return call_user_func( $this->messages['repo'][ $repo ][ $messageId ], $messageId );
			}
		}

		return null;
	}

}
