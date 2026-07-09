<?php

if (!class_exists('ParagonIE_Sodium_Compat')) {
	require_once WORDFENCE_PATH . '/crypto/vendor/paragonie/sodium_compat/autoload.php';
}
ParagonIE_Sodium_Compat::$fastMult = true;