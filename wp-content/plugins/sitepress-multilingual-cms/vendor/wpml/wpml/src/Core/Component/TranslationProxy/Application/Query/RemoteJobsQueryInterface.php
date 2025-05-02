<?php

namespace WPML\Core\Component\TranslationProxy\Application\Query;

interface RemoteJobsQueryInterface {


  public function getCount( int $currentTranslationServiceId ): int;


}
