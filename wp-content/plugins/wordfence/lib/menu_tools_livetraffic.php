<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
?>
<div class="wordfence-vue-wrapper" data-base-component="OptionsLinkBlock"></div>
<div class="wordfence-vue-wrapper" data-base-component="LiveTraffic"></div>
<?php if (wfOnboardingController::willShowNewTour(wfOnboardingController::TOUR_LIVE_TRAFFIC)): ?>
<div class="wordfence-vue-wrapper" data-base-component="LiveTrafficNewTour"></div>
<?php endif; ?>
