<?php

namespace WPML\Infrastructure\Composer;

/**
 * Holds the package name and a helper function to run npm commands.
 */
class Node {
  const PACKAGE_NAME = 'wpml/wpml';


  /**
   * Run given npm command on this project.
   *
   * @param string $command
   *
   * @return bool
   */
  public static function runNpmCommand( $command ) {
    $requiredNodeVersion = 'v20';
    $preInstalledNodeVersion = exec( "node -v" );
    $queryPath = 'vendor/' . self::PACKAGE_NAME;

    if ( strpos( $preInstalledNodeVersion, $requiredNodeVersion ) === false ) {
      // Switch to node version 20.
      $result = self::runWithNodeVersion(
        $requiredNodeVersion,
        "cd $queryPath && npm $command"
      );

      if ( ! $result ) {
        return false;
      }
    } else {
      // Run NPM command.
      exec( "cd $queryPath && npm $command" );
    }

    return true;
  }


  /**
   * Switch to given node version.
   *
   * @param string $version Must start with 'v', i.e. 'v20'.
   * @param string $command
   *
   * @return bool
   */
  private static function runWithNodeVersion( $version, $command ) {
    $nvm_dir = getenv( 'NVM_DIR' );
    if ( ! $nvm_dir ) {
      echo "\nNVM_DIR not set. Aborting.\n";
      return false;
    }

    echo "\nTrying to switch to node $version.\n";

    $result = exec(
      '[ -s "$NVM_DIR/nvm.sh" ] '.
      '&& \. "$NVM_DIR/nvm.sh" ' .
      "&& nvm install $version && $command"
    );

    return ! empty( $result );
  }


}
