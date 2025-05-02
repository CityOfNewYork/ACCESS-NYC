<?php

namespace WPML\Core\Component\Translation\Domain\Links;

interface CollectorInterface {


  /** @return Item[] */
  public function getItemsLinkedInContent( string $content );


  /** @return void */
  public function addItemByIdAndType( int $id, string $type );


}
