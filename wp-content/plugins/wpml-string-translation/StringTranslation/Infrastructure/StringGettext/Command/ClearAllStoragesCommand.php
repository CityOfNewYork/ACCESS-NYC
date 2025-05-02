<?php

namespace WPML\StringTranslation\Infrastructure\StringGettext\Command;

use WPML\StringTranslation\Application\StringGettext\Command\ClearAllStoragesCommandInterface;

class ClearAllStoragesCommand implements ClearAllStoragesCommandInterface {
	public function run() {
		$files = glob(WP_LANG_DIR . '/wpml/queue/*');
		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				unlink($file);
			}
		}
	}
}