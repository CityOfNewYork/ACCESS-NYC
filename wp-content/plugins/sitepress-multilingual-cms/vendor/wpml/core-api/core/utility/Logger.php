<?php
namespace WPML\Utilities;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\InvalidArgumentException;

class Logger implements LoggerInterface
{

	/** @inheritDoc */
	public function emergency($message, array $context = [])
	{
		$this->log(LogLevel::EMERGENCY, $message, $context);
	}

	/** @inheritDoc */
	public function alert($message, array $context = [])
	{
		$this->log(LogLevel::ALERT, $message, $context);
	}

	/** @inheritDoc */
	public function critical($message, array $context = [])
	{
		$this->log(LogLevel::CRITICAL, $message, $context);
	}

	/** @inheritDoc */
	public function error($message, array $context = [])
	{
		$this->log(LogLevel::ERROR, $message, $context);
	}

	/** @inheritDoc */
	public function warning($message, array $context = [])
	{
		$this->log(LogLevel::WARNING, $message, $context);
	}

	/** @inheritDoc */
	public function notice($message, array $context = [])
	{
		$this->log(LogLevel::NOTICE, $message, $context);
	}

	/** @inheritDoc */
	public function info($message, array $context = [])
	{
		$this->log(LogLevel::INFO, $message, $context);
	}

	/** @inheritDoc */
	public function debug($message, array $context = [])
	{
		$this->log(LogLevel::DEBUG, $message, $context);
	}

	/** @inheritDoc */
	public function log($level, $message, array $context = [])
	{
		$levels = [
			LogLevel::EMERGENCY => true,
			LogLevel::ALERT => true,
			LogLevel::CRITICAL => true,
			LogLevel::ERROR => true,
			LogLevel::WARNING => true,
			LogLevel::NOTICE => true,
			LogLevel::INFO => true,
			LogLevel::DEBUG => true,
		];

		if (!isset($levels[$level])) {
			throw new InvalidArgumentException('Invalid log level.');
		}

		error_log( $level.' '.$this->interpolate($message, $context)."\n" );
	}

	/**
	 * Interpolates context values into the message placeholders.
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return string
	 */
	protected function interpolate($message, array $context = [])
	{
		$replace = array();
		foreach ($context as $key => $val) {
			if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
				$replace['{'.$key.'}'] = $val;
			}
		}

		// interpolate replacement values into the message and return
		return strtr((string)$message, $replace);
	}
}