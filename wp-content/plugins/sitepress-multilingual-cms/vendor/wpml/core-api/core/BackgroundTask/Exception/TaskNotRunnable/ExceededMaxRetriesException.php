<?php

namespace WPML\Core\BackgroundTask\Exception\TaskNotRunnable;

use WPML\Core\BackgroundTask\Exception\TaskIsNotRunnableException;

class ExceededMaxRetriesException extends TaskIsNotRunnableException {
}