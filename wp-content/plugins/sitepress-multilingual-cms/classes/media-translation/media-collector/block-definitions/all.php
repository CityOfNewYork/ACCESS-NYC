<?php

namespace WPML\MediaTranslation\MediaCollector;

return array_merge(
	require __DIR__ . '/core.php',
	require __DIR__ . '/essential.php',
	require __DIR__ . '/kadence.php',
	require __DIR__ . '/spectra.php',
	require __DIR__ . '/toolset.php'
);
