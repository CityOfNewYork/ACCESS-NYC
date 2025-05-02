<?php

namespace WPML\Core\SharedKernel\Component\TranslationProxy\Domain;

/**
 * @phpstan-type TranslationServiceExtraFieldArray array{
 * type: string,
 * label: string,
 * name: string,
 * items: string[]|null
 * }
 */
class RemoteTranslationServiceExtraField {

  /** @var string */
  private $type;

  /** @var string */
  private $label;

  /** @var string */
  private $name;

  /** @var ExtraFieldItems | null */
  private $items;


  /**
   * @param string $type
   * @param string $label
   * @param string $name
   * @param string[] | object | null $items
   */
  public function __construct( string $type, string $label, string $name, $items ) {
    $this->type  = $type;
    $this->label = $label;
    $this->name  = $name;
    $this->items = $this->prepareItems( $items );
  }


  public function getType(): string {
    return $this->type;
  }


  public function getLabel(): string {
    return $this->label;
  }


  public function getName(): string {
    return $this->name;
  }


  /**
   * @return ExtraFieldItems|null
   */
  public function getItems() {
    return $this->items;
  }


  /**
   * @param string[] | object | null $items
   *
   * @return ExtraFieldItems | null
   */
  private function prepareItems( $items ) {
    $preparedItems = new ExtraFieldItems();

    if ( ! $items ) {
      return null;
    }

    $items = is_object( $items ) ? (array) $items : $items;

    foreach ( $items as $key => $val ) {
      $preparedItems->{$key} = $val;
    }

    return $preparedItems;
  }


  /**
   * @return array{
   * type: string,
   * label: string,
   * name: string,
   * items: ExtraFieldItems|null
   * }
   */
  public function toArray(): array {
    return [
      'type'  => $this->type,
      'label' => $this->label,
      'name'  => $this->name,
      'items' => $this->items
    ];
  }


}
