<?php

namespace WPML\StringTranslation\Application\Debug\Service;

use WPML\StringTranslation\Application\StringGettext\Repository\QueueRepositoryInterface;
use WPML\StringTranslation\Application\StringCore\Repository\ComponentRepositoryInterface;
use WPML\StringTranslation\Application\Debug\Repository\ComponentDebugRepository;

class DebugService {

	/** @var QueueRepositoryInterface */
	private $queueRepository;

	/** @var ComponentRepositoryInterface */
	private $componentRepository;

	private static $checkpoints = [];

	public function __construct( QueueRepositoryInterface $queueRepository, ComponentRepositoryInterface $componentRepository ) {
		$this->queueRepository     = $queueRepository;
		$this->componentRepository = $componentRepository;
	}

	public function trackPerformanceStart( $group, $id ) {
		static::$checkpoints[ $group ][ $id ] = [
			'initialMemoryUsage' => memory_get_usage(),
			'startTime' => microtime(true ),
		];
	}

	public function trackPerformanceEnd( $group, $id ) {
		static::$checkpoints[ $group ][ $id ] = [
			'memoryUsage' => memory_get_usage() - static::$checkpoints[ $group ][ $id ]['initialMemoryUsage'],
			'time' => microtime( true ) - static::$checkpoints[ $group ][ $id ]['startTime'],
		];
	}

	public function displayPerformanceInfo() { return;
		if ( ( strpos( $_SERVER['REQUEST_URI'], '/wp-json/' ) !== false ) || wpml_is_ajax() ) {
			return;
		}

		$groups = array_keys( static::$checkpoints );
		$aggregationMemory = [];
		$aggregationTime = [];
		$totalMemory = 0;
		$totalTime = 0;

		echo '<div style="padding-left: 300px;">';
		echo '<h2>Performance Info</h2>';
		echo '<table>';
		echo '<tr><th>Group</th><th>calls</th><th>memory</th><th>time</th></tr>';

		foreach ( $groups as $group ) {
			$ids = array_keys( static::$checkpoints[ $group ] );
			$aggregationMemory[ $group ]  = 0;
			$aggregationTime[ $group ]  = 0;

			foreach ( $ids as $id ) {
				$aggregationMemory[ $group ] += static::$checkpoints[ $group ][ $id ]['memoryUsage'];
				$aggregationTime[ $group ] += static::$checkpoints[ $group ][ $id ]['time'];
				$totalMemory += static::$checkpoints[ $group ][ $id ]['memoryUsage'];
				$totalTime += static::$checkpoints[ $group ][ $id ]['time'];
			}

			echo '<tr>';
			echo '<td>' . $group . '</td>';
			echo '<td width="200">' . count( static::$checkpoints[ $group ] ) . '</td>';
			echo '<td width="200">' . $this->formatBytes($aggregationMemory[ $group ] ) . '</td>';
			echo '<td>' . $this->formatMicrotime( $aggregationTime[ $group ] ) . 's </td>';
			echo '</tr>';
		}

		echo '</table>';

		echo '<hr />';
		echo "Total Memory by autoregistering strings: " . $this->formatBytes( $totalMemory ) . "<br />";
		echo "Total time by autoregistering strings: " . $this->formatMicrotime( $totalTime ) . '<br />';
		echo "Total Memory Usage: " . $this->formatBytes( memory_get_usage() ) . "<br />";
		echo "Total Peak Memory Usage: " . $this->formatBytes( memory_get_peak_usage() ) . "<br />";
		global $start_php_time;
		echo "Total Time: " . $this->formatMicrotime( microtime( true ) - $start_php_time ) . " s<br />";

		echo '<h2>Total traces count: ' . ComponentDebugRepository::$tracesCount . ' / Total calls count: ' . ComponentDebugRepository::$callsCount . '</h2>';

		echo "</div>";
	}

	private function formatBytes($bytes, $precision = 2) {
		$units = array('B', 'KB', 'MB', 'GB', 'TB');
		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);
		$bytes /= pow(1024, $pow);
		return round($bytes, $precision) . ' ' . $units[$pow];
	}

	private function formatMicrotime( $milliseconds ) {
		$seconds = floor($milliseconds / 1000);
		$minutes = floor($seconds / 60);
		$seconds %= 60.0;
		$milliseconds = $milliseconds - $seconds*1000;

		$formattedTime = "";
		if ($minutes > 0) {
			$formattedTime .= "{$minutes}m ";
		}
		if ($seconds > 0 || $minutes > 0) { // Including minutes check to handle cases like "1m 0s"
			$formattedTime .= "{$seconds}s ";
		}
		$formattedTime .= "{$milliseconds}ms";

		$time = trim($formattedTime);

		if ( strpos( $time, '-' ) !== false ) {
			$time = 'very small';
		}

		return $time;
	}
}