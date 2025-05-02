<?php

namespace WPML\MediaTranslation\MediaCollector;

return [
	new CollectorBlock(
		'kadence/image',
		[ new PathResolverByString( 'attrs' ), new PathResolverByString( 'id' ) ],
		[ new PathResolverByString( 'innerHTML' ), new PathResolverByRegex( '#src="(.*?)"#' ) ]
	),
	new CollectorBlock(
		'kadence/imageoverlay',
		[ new PathResolverByString( 'attrs' ), new PathResolverByString( 'imgID' ) ],
		[ new PathResolverByString( 'innerHTML' ), new PathResolverByRegex( '#src="(.*?)"#' ) ]
	),
	new CollectorBlock(
		'kadence/advancedgallery',
		[ new PathResolverByString( 'id' ) ],
		[ new PathResolverByString( 'url' ) ],
		[ new PathResolverByString( 'attrs' ), new PathResolverByString( 'imagesDynamic' ) ]
	),
	new CollectorBlock(
		'kadence/slide',
		[ new PathResolverByString( 'id' ) ],
		[ new PathResolverByString( 'img' ) ],
		[ new PathResolverByString( 'attrs' ), new PathResolverByString( 'backgroundImg' ) ]
	),
	new CollectorBlock(
		'kadence/splitcontent',
		[ new PathResolverByString( 'attrs' ), new PathResolverByString( 'mediaId' ) ],
		[ new PathResolverByString( 'attrs' ), new PathResolverByString( 'mediaUrl' ) ]
	),
];
