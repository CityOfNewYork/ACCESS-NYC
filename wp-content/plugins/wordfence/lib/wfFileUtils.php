<?php

require_once __DIR__ . "/wfInvalidPathException.php";
require_once __DIR__ . "/wfInaccessibleDirectoryException.php";

class wfFileUtils {

	const CURRENT_DIRECTORY = '.';
	const PARENT_DIRECTORY = '..';
	const DIRECTORY_SEPARATOR = '/';

	public static function isCurrentOrParentDirectory($file) {
		return $file === self::CURRENT_DIRECTORY || $file === self::PARENT_DIRECTORY;
	}

	public static function getContents($directory) {
		$contents = @scandir($directory);
		if ($contents === false)
			throw new wfInaccessibleDirectoryException("Unable to read contents", $directory);
		return array_filter($contents, function ($file) { return !wfFileUtils::isCurrentOrParentDirectory($file); });
	}

	public static function trimSeparators($path, $trimLeft = true, $trimRight = true) {
		if ($trimLeft)
			$path = ltrim($path, self::DIRECTORY_SEPARATOR);
		if ($trimRight)
			$path = rtrim($path, self::DIRECTORY_SEPARATOR);
		return $path;
	}

	public static function joinPaths() {
		$paths = func_get_args();
		$count = count($paths);
		$filtered = array();
		$trailingSeparator = false;
		for ($i = 0; $i < $count; $i++) {
			$path = self::trimSeparators($paths[$i], !empty($filtered));
			if (!empty($path)) {
				$filtered[] = $path;
				$trailingSeparator = substr($paths[$i], -1) === self::DIRECTORY_SEPARATOR;
			}
		}
		return implode(self::DIRECTORY_SEPARATOR, $filtered) . ($trailingSeparator ? self::DIRECTORY_SEPARATOR : '');
	}

	public static function splitPath($path, &$count = null) {
		$components = array_values(array_filter(explode(self::DIRECTORY_SEPARATOR, $path)));
		$count = count($components);
		return $components;
	}

	public static function isReadableFile($file) {
		return @is_file($file) && @is_readable($file);
	}

	public static function belongsTo($child, $parent) {
		$childComponents = self::splitPath($child, $childCount);
		$parentComponents = self::splitPath($parent, $parentCount);
		if ($childCount < $parentCount)
			return false;
		for ($i = 0; $i < $parentCount; $i++) {
			if ($childComponents[$i] !== $parentComponents[$i])
				return false;
		}
		return true;
	}

	public static function matchPaths($a, $b, $allowChild = false) {
		$aComponents = self::splitPath($a, $aCount);
		$bComponents = self::splitPath($b, $bCount);
		if ($allowChild ? ($bCount < $aCount) : ($aCount !== $bCount))
			return false;
		for ($i = 0; $i < $aCount; $i++) {
			if ($aComponents[$i] !== $bComponents[$i])
				return false;
		}
		return true;
	}

	public static function realPath($path) {
		$realPath = realpath($path);
		if ($realPath === false)
			throw new wfInvalidPathException("Realpath resolution failed", $path);
		return $realPath;
	}

	public static function isChild($parent, $child) {
		return self::matchPaths($parent, $child, true);
	}

}