<?php

/**
 *  Updates
 *   - [arrayKey]                  Id of the update.
 *      - handler                  Class to execute. Must implement UpdateInterface.
 *      - includedIn               Version where the change is already included.
 *                                 The update won't run on installations
 *                                 starting with this version.
 *      - tryOnlyOnce (optional)   Default: false.
 *                                 If true, the update will run only once no
 *                                 matter if it works or not.
 */

use WPML\Core\Component\Translation\Application\Update\Database\TranslationStatus\AddIndexForReviewStatus;

return [
  'Database/TranslationStatus/AddIndexForReviewStatus' => [
    'handler'     => AddIndexForReviewStatus::class,
    'includedIn'  => '4.7.2',
    'tryOnlyOnce' => true,
  ],
];
