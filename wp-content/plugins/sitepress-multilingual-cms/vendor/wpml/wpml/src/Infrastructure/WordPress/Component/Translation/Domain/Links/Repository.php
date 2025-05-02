<?php

namespace WPML\Infrastructure\WordPress\Component\Translation\Domain\Links;

use WPML\Core\Component\Translation\Domain\Links\Item;
use WPML\Core\Component\Translation\Domain\Links\RepositoryInterface;
use WPML\Infrastructure\WordPress\SharedKernel\Post\Domain\PublicationStatusDefinitions;
use WPML\PHP\Exception\InvalidItemIdException;
use WPML\PHP\Exception\InvalidTypeException;
use WPML\WordPress\Term;

class Repository implements RepositoryInterface {
  const TYPE_POST = 'post';
  const TYPE_TERM = 'term';

  const TABLE_FROM_POST_TO_POST = [
    'name' => 'icl_links_post_to_post',
    'from' => self::TYPE_POST,
    'to' => self::TYPE_POST,
  ];

  const TABLE_FROM_POST_TO_TERM = [
    'name' => 'icl_links_post_to_term',
    'from' => self::TYPE_POST,
    'to' => self::TYPE_TERM,
  ];

  const TABLES = [
    self::TABLE_FROM_POST_TO_POST,
    self::TABLE_FROM_POST_TO_TERM,
  ];

  const TABLES_FROM_BY_TYPE = [
    self::TYPE_POST => [
      self::TABLE_FROM_POST_TO_POST,
      self::TABLE_FROM_POST_TO_TERM
    ],
  ];

  const TABLES_TO_BY_TYPE = [
    self::TYPE_POST => [ self::TABLE_FROM_POST_TO_POST ],
    self::TYPE_TERM => [ self::TABLE_FROM_POST_TO_TERM ],
  ];

  /** @var PublicationStatusDefinitions */
  private $publicationStatusDefinitions;


  public function __construct() {
    // No need to inject PublicationStatusDefinitions here
    // as it MUST be the WordPress implementation.
    $this->publicationStatusDefinitions = new PublicationStatusDefinitions();
  }


  /** @throws InvalidTypeException */
  public function get( int $id, string $type ): Item {
    try {
      switch ( $type ) {
        case self::TYPE_POST:
          $item = get_post( $id, 'ARRAY_A' );

          if ( ! is_array( $item ) ) {
            throw new InvalidItemIdException( "Invalid post ID: $id" );
          }

          $iclTranslationType = 'post_' . $item['post_type'];

          $itemName = $item['post_name'];
          $itemStatus = $item['post_status'];
          $item = new Item(
            (int) $item['ID'],
            self::TYPE_POST,
            $item['post_content'],
            $item['post_excerpt']
          );

          if ( $this->publicationStatusDefinitions->isPublished( $itemStatus ) ) {
            $item->markAsPublished();
          } elseif (
            $itemName // We don't want to mark as publishable if the name is empty. A post is only in this state as long as it's did not get saved by a user (but auto-saved).
            && $this->publicationStatusDefinitions->isPublishable( $itemStatus )
          ) {
            $item->markAsPublishable();
          } else {
            return $item;
          }

          break;
        case self::TYPE_TERM:
          /** @var array<string,string>|null $item */
          $item = Term::get( $id, '', 'ARRAY_A' );

          if ( ! is_array( $item ) ) {
            throw new InvalidItemIdException( "Invalid term ID: $id" );
          }

          $iclTranslationType = 'tax_' . $item['taxonomy'];
          $item = new Item(
            (int) $item['term_id'],
            self::TYPE_TERM
          );
          $item->markAsPublished();

          break;
        default:
          throw new InvalidTypeException( "Invalid item type: $type" );
      }

      $wpdb = $GLOBALS['wpdb'];
      $langAndOriginalId = $wpdb->get_row(
        $wpdb->prepare(
          "SELECT t1.language_code as languageCode, t2.element_id as elementId
          FROM {$wpdb->prefix}icl_translations as t1
          INNER JOIN {$wpdb->prefix}icl_translations as t2 ON t1.trid = t2.trid
          WHERE t1.element_id = %d
          AND t1.element_type = %s
          AND t1.element_id != t2.element_id
          AND t2.source_language_code IS NULL",
          $id,
          $iclTranslationType
        )
      );

      if ( $langAndOriginalId !== null ) {
        $item->setIdOriginal( (int) $langAndOriginalId->elementId );
        $item->setLanguageCode( $langAndOriginalId->languageCode );
      }

      return $item;
    } catch ( InvalidItemIdException $e ) {
      $item = new Item(
        $id,
        $type,
        ''
      );

      $item->markAsDeleted();

      return $item;
    }

  }


  /** @return Item[] */
  public function getFromItemsByToItem( Item $item ) {
    if ( ! array_key_exists( $item->getType(), self::TABLES_TO_BY_TYPE ) ) {
      return [];
    }

    $wpdb = $GLOBALS['wpdb'];
    $items = [];

    foreach ( self::TABLES_TO_BY_TYPE[ $item->getType() ] as $table ) {
      $itemIds = $wpdb->get_col(
        $wpdb->prepare(
          "SELECT id_from
          FROM {$wpdb->prefix}" . $table['name'] . "
          WHERE id_to = %d",
          $item->isOriginal() ? $item->getId() : $item->getIdOriginal()
        )
      );

      if ( ! is_array( $itemIds ) ) {
        continue;
      }

      foreach ( $itemIds as $itemId ) {
        if ( $item->isOriginal() ) {
          /** @phpstan-ignore-next-line At this point we can't have an invalid type. */
          $items[] = $this->get( $itemId, $table['from'] );
          continue;
        }

        $langCode = $item->getLanguageCode();
        if (
          $langCode
          && $translationId = $this->getTranslationIdOfOriginal( $itemId, $table['from'], $langCode )
        ) {
          /** @phpstan-ignore-next-line At this point we can't have an invalid type. */
          $items[] = $this->get( $translationId, $table['from'] );
        }
      }
    }

    return $items;
  }


  /** @return ?int */
  private function getTranslationIdOfOriginal(
    int $originalId,
    string $type,
    string $targetLang
  ) {
    $elementType = null;

    switch ( $type ) {
      case self::TYPE_POST:
        /** @var array<string,string>|null $post */
        $post = get_post( $originalId, 'ARRAY_A' );
        $elementType = $post
          ? 'post_' . $post['post_type']
          : null;
        break;
      case self::TYPE_TERM:
        /** @var array<string,string>|null $term */
        $term = Term::get( $originalId, '', 'ARRAY_A' );
        $elementType = $term
          ? 'tax_' . $term['taxonomy']
          : null;
        break;
    }

    if ( $elementType === null ) {
      return null;
    }

    $wpdb = $GLOBALS['wpdb'];
    return $wpdb->get_var(
      $wpdb->prepare(
        "SELECT t2.element_id as elementId
          FROM {$wpdb->prefix}icl_translations as t1
          INNER JOIN {$wpdb->prefix}icl_translations as t2 ON t1.trid = t2.trid
          WHERE t1.element_id = %d
          AND t1.element_type = %s
          AND t1.element_id != t2.element_id
          AND t2.language_code = %s",
        $originalId,
        $elementType,
        $targetLang
      )
    );
  }


  /** @return Item[] */
  public function getToItemsByFromItem( Item $item ) {
    if ( ! array_key_exists( $item->getType(), self::TABLES_FROM_BY_TYPE ) ) {
      return [];
    }

    $wpdb = $GLOBALS['wpdb'];
    $items = [];

    foreach ( self::TABLES_FROM_BY_TYPE[ $item->getType() ] as $table ) {
      $itemIds = $wpdb->get_col(
        $wpdb->prepare(
          "SELECT id_to
          FROM {$wpdb->prefix}" . $table['name'] . "
          WHERE id_from = %d",
          $item->getId()
        )
      );

      if ( is_array( $itemIds ) ) {
        foreach ( $itemIds as $itemId ) {
          /** @phpstan-ignore-next-line At this point we can't have an invalid type. */
          $items[] = $this->get( $itemId, $table['to'] );
        }
      }
    }

    return $items;
  }


  /** @return void */
  public function addRelationship( Item $from, Item $to ) {
    if ( ! $table = $this->getTableByItems( $from, $to ) ) {
      return;
    }

    $wpdb            = $GLOBALS['wpdb'];
    $tableWithPrefix = $wpdb->prefix . $table['name'];

    $insertRelationshipPrepared = $wpdb->prepare(
      "INSERT INTO {$tableWithPrefix} (id_from, id_to) VALUES (%d, %d) ON DUPLICATE KEY UPDATE id_to = %d",
      $from->getId(),
      $to->getId(),
      $to->getId()
    );

    $wpdb->query( $insertRelationshipPrepared );
  }


  /** @return void */
  public function deleteRelationship( Item $from, Item $to ) {
    if ( ! $table = $this->getTableByItems( $from, $to ) ) {
      return;
    }

    $wpdb = $GLOBALS['wpdb'];
    $wpdb->delete(
      $wpdb->prefix . $table['name'],
      [
        'id_from' => $from->getId(),
        'id_to'   => $to->getId(),
      ],
      '%d'
    );
  }


  /** @return ?self::TABLE_FROM_* */
  private function getTableByItems( Item $from, Item $to ) {
    $tablesFrom = self::TABLES_FROM_BY_TYPE[ $from->getType() ] ?? [];

    foreach ( $tablesFrom as $table ) {
      if ( $table['to'] === $to->getType() ) {
        return $table;
      }
    }

    return null;
  }


  /** @return void */
  public function deleteAllRelationshipsFrom( Item $item ) {
    if ( ! array_key_exists( $item->getType(), self::TABLES_FROM_BY_TYPE ) ) {
      return;
    }

    $wpdb = $GLOBALS['wpdb'];
    foreach ( self::TABLES_FROM_BY_TYPE[ $item->getType() ] as $table ) {
      $wpdb->delete(
        $wpdb->prefix . $table['name'],
        [ 'id_from' => $item->getId() ],
        '%d'
      );
    }
  }


  /** @return void */
  public function deleteAllRelationshipsTo( Item $item ) {
    if ( ! array_key_exists( $item->getType(), self::TABLES_TO_BY_TYPE ) ) {
      return;
    }

    $wpdb = $GLOBALS['wpdb'];
    foreach ( self::TABLES_TO_BY_TYPE[ $item->getType() ] as $table ) {
      $wpdb->delete(
        $wpdb->prefix . $table['name'],
        [ 'id_to' => $item->getId() ],
        '%d'
      );
    }
  }


  /*
  * This is called by legacy CreateLinksTables::run_admin().
  */
  public static function createDatabaseTables(): bool {
    $wpdb = $GLOBALS['wpdb'];

    $result = true;
    foreach ( self::TABLES as $table ) {
      $creation = $wpdb->query(
        "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}" . $table['name'] . " (
        `id_from` bigint(20) unsigned NOT NULL,
        `id_to` bigint(20) unsigned NOT NULL,
        PRIMARY KEY (`id_from`,`id_to`),
        KEY `id_to` (`id_to`)
      ) " . $wpdb->get_charset_collate()
      );

      if ( $creation === false ) {
        $result = false;
      }
    }

    return $result;
  }


}
