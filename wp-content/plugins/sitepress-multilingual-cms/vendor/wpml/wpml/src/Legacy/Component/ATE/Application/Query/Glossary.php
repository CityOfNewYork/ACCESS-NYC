<?php

namespace WPML\Legacy\Component\ATE\Application\Query;

use WPML\Core\Component\ATE\Application\Query\GlossaryException;
use WPML\Core\Component\ATE\Application\Query\GlossaryInterface;

class Glossary implements GlossaryInterface
{


    /**
     * @inheritDoc
     */
  public function getGlossaryCount(): int {
    $glossaryApi = \WPML\Container\make( \WPML\TM\API\ATE\Glossary::class );

    $apiResponse = $glossaryApi->getGlossaryCount();

    /**
     * @psalm-suppress MissingClosureReturnType
     * @psalm-suppress MissingClosureParamType
     */
    $errorHandler = function ( $error ) {
        throw new GlossaryException(
          $error['error'] ?? __( 'Error getting glossary data', 'wpml' )
        );
    };

    /**
     * @psalm-suppress MissingClosureReturnType
     * @psalm-suppress MissingClosureParamType
     */
    $identity = function ( array $result ) {
        return $result;
    };

    if ( is_object( $apiResponse ) && method_exists( $apiResponse, 'bimap' ) ) {
        $apiResponse = $apiResponse->bimap( $errorHandler, $identity );
    }

    if ( ! is_object( $apiResponse ) || ! method_exists( $apiResponse, 'getOrElse' ) ) {
        return 0;
    }

    $apiResult = $apiResponse->getOrElse( [] );

    if ( ! is_array( $apiResult ) ) {
        return 0;
    }

    return $apiResult['glossary_entries_count'] ?? 0;
  }


}
