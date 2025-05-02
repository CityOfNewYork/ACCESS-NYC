<?php

namespace WPML\MediaTranslation\MediaCollector;

return [
	new CollectorBlock(
		'core/image',
		[ new PathResolverByString( 'attrs' ), new PathResolverByString( 'id' ) ],
		[ new PathResolverByString( 'innerHTML' ), new PathResolverByRegex( '#src="(.*?)"#' ) ]
	),
];
