<?php

namespace WPML\UserInterface\Web\Infrastructure\CompositionRoot\Config\Updates;

use WPML\Core\Port\Persistence\OptionsInterface;
use WPML\Core\Port\PluginInterface;

class Repository {
  const OPTION = 'wpml-updates-log';
  const OPTION_KEY_UPDATED_TO = 'updated_to';
  const OPTION_KEY_UPDATES = 'updates';

  const UPDATES_KEY_STATUS = 'status';

  const STATUS_IN_PROGRESS = 'in-progress';
  const STATUS_COMPLETED = 'completed';
  const STATUS_FAILED = 'failed';

  /** @var OptionsInterface $options */
  private $options;

  /** @var PluginInterface $plugin */
  private $plugin;


  public function __construct( OptionsInterface $options, PluginInterface $plugin ) {
    $this->options = $options;
    $this->plugin = $plugin;
  }


  /**
   * @param array<string, Update> $allUpdates
   * @return array<string, Update>
   */
  public function getUpdatesToPerform( $allUpdates ) {
    $log = $this->getLog();
    $currentVersion = $this->plugin->getVersionWithoutSuffix();
    $updatedTo = $log[ self::OPTION_KEY_UPDATED_TO ];

    if ( version_compare( $currentVersion, $updatedTo ) === 0 ) {
      // Everything updated.
      return [];
    }

    $updatesToPerform = [];
    $updatesInTheLog = isset( $log[ self::OPTION_KEY_UPDATES ] )
      ? $log[ self::OPTION_KEY_UPDATES ]
      : [];

    $updatesInProgress = array_filter(
      $updatesInTheLog,
      function( $update ) {
        return $update[self::UPDATES_KEY_STATUS] === self::STATUS_IN_PROGRESS;
      }
    );

    $updatesFailed = array_filter(
      $updatesInTheLog,
      function( $update ) {
        return $update[self::UPDATES_KEY_STATUS] === self::STATUS_FAILED;
      }
    );

    foreach ( $allUpdates as $update ) {
      if (
        version_compare( $updatedTo, $update->includedIn() ) >= 0
        || version_compare( $currentVersion, $update->includedIn() ) < 0
      ) {
        // Already updated or for a future version.
        // Can't think of a case where a future version is useful, but it's here for completeness.
        continue;
      }

      $updateLog = $updatesInTheLog[ $update->id() ] ?? null;
      if (
        $updateLog
        && in_array( $updateLog[ self::UPDATES_KEY_STATUS ], [ self::STATUS_COMPLETED, self::STATUS_IN_PROGRESS ] )
      ) {
        continue;
      }

      $updatesToPerform[ $update->id() ] = $update;
    }

    if (
      count( $updatesToPerform ) === 0
      && count( $updatesInProgress ) === 0
      && count( $updatesFailed ) === 0
    ) {
      // All updates proceed.
      $this->setUpdatedTo( $currentVersion );
      return [];
    }

    return $updatesToPerform;
  }


  /**
   * @param Update $update
   * @return void
   */
  public function setUpdateInProgress( $update ) {
    $this->setUpdate( $update, self::STATUS_IN_PROGRESS );
  }


  /**
   * @param Update $update
   * @return void
   */
  public function setUpdateComplete( $update ) {
    $this->setUpdate( $update, self::STATUS_COMPLETED );
  }


  /**
   * @param Update $update
   * @return void
   */
  public function setUpdateFailed( $update ) {
    $this->setUpdate( $update, self::STATUS_FAILED );
  }


  /**
   * @return array{updated_to: string, updates?: array<string, array{status: string}>}
   *
   * No need to proof the option structure as it's class internal.
   * @psalm-suppress MoreSpecificReturnType
   * @psalm-suppress LessSpecificReturnStatement
   */
  private function getLog() {
    $option = $this->options->get( self::OPTION, false );
    if ( ! is_array( $option ) ) {
      $installVersion = $this->plugin->getVersionWhenSetupRanWithoutSuffix();
      $this->setUpdatedTo( $installVersion );
      return [ self::OPTION_KEY_UPDATED_TO => $installVersion ];
    }

    $log = [];
    $log[ self::OPTION_KEY_UPDATED_TO ] = $option[ self::OPTION_KEY_UPDATED_TO ] ?? '0.0.0';
    if ( isset( $option[ self::OPTION_KEY_UPDATES ] ) ) {
      $log[ self::OPTION_KEY_UPDATES ] = $option[ self::OPTION_KEY_UPDATES ];
    }

    return $log;
  }


  /**
   * @param Update $update
   * @param string $status
   * @return void
   */
  private function setUpdate( $update, $status ) {
    $validStatuses = [
      self::STATUS_COMPLETED,
      self::STATUS_FAILED,
      self::STATUS_IN_PROGRESS
    ];

    if ( ! in_array( $status, $validStatuses ) ) {
      return;
    }

    $log = $this->options->get( self::OPTION, [] );
    $log = is_array( $log ) ? $log : [];
    $log[ self::OPTION_KEY_UPDATES ] = $log[ self::OPTION_KEY_UPDATES ] ?? [];

    $log[ self::OPTION_KEY_UPDATES ][ $update->id() ] = [
      self::UPDATES_KEY_STATUS => $status
    ];

    $this->options->save( self::OPTION, $log, true );
  }


  /**
   * @param string $version
   *
   * @return void
   */
  private function setUpdatedTo( $version ) {
      $this->options->save(
        self::OPTION,
        // 'updates' key can be removed from the log when everything ran.
        [ self::OPTION_KEY_UPDATED_TO => $version ],
        true
      );
  }


}
