<?php

namespace WPML\MediaTranslation\MediaCollector;

class Collector {
	/** @var array<string,CollectorBlock> */
	private $collector_blocks = [];

	public function addCollectorBlock( CollectorBlock $block ) {
		$this->collector_blocks[ $block->getName() ] = $block;
	}

	public function addCollectorBlocks( $blocks ) {
		foreach ( $blocks as $block ) {
			$this->addCollectorBlock( $block );
		}
	}

	public function collectMediaFromBlocks( $blocks, &$mediaCollection = [] ) {
		foreach ( $blocks as $block ) {
			$block = (object) $block;

			foreach ( $this->collector_blocks as $collector ) {
				if ( ! isset( $block->blockName ) || $block->blockName !== $collector->getName() ) {
					continue;
				}

				$collector->collectIdsAndUrls( $block, $mediaCollection );
			}

			if ( ! empty( $block->innerBlocks ) ) {
				$this->collectMediaFromBlocks( $block->innerBlocks, $mediaCollection );
			}
		}
	}
}
