<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
?>
<div class="wordfence-vue-wrapper" data-base-component="Blocking"></div>
<?php if (wfOnboardingController::willShowNewTour(wfOnboardingController::TOUR_BLOCKING)): ?>
<div class="wordfence-vue-wrapper" data-base-component="BlockingNewTour"></div>
<?php endif; ?>
