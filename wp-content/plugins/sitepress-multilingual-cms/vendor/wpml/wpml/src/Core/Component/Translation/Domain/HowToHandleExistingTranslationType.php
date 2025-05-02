<?php

namespace WPML\Core\Component\Translation\Domain;

class HowToHandleExistingTranslationType {
  const HANDLE_EXISTING_LEAVE = 'leave';
  const HANDLE_EXISTING_OVERRIDE = 'override';

  const ALL = [
    'leave'    => self::HANDLE_EXISTING_LEAVE,
    'override' => self::HANDLE_EXISTING_OVERRIDE,
  ];
}
