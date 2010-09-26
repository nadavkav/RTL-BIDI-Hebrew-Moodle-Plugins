<?php
/**
 * SharingCart_FileSystem
 */

class SharingCart_FileSystem
{
	const RECURSIVE = 1;
	const OVERWRITE = 2;
	const NEWERONLY = 4;
	
	/**
	 * Copy file or directory
	 */
	public static function copy($source, $target, $options = 0)
	{
		if (!file_exists($source))
			return FALSE;
		if (is_file($source)) {
			if (!file_exists($target)) {
				if (!is_dir($dirname = dirname($target)) && !mkdir($dirname, 0777, TRUE))
					return FALSE;
				return copy($source, $target);
			}
			if (is_dir($target)) {
				$target .= DIRECTORY_SEPARATOR . basename($source);
				if (is_dir($target))
					return FALSE;
			}
			if (is_file($target)) {
				if (!($options & self::OVERWRITE))
					return FALSE;
				if (($options & self::NEWERONLY) && filemtime($target) > filemtime($source))
					return TRUE;
			}
			return copy($source, $target);
		}
		if (is_dir($source) && ($options & self::RECURSIVE)) {
			if (is_link($source))
				return FALSE;
			if (!is_dir($target) && !mkdir($target, 0777, TRUE))
				return FALSE;
			foreach (scandir($source) as $e) {
				if ($e == '.' || $e == '..')
					continue;
				if (!self::copy($source . DIRECTORY_SEPARATOR . $e,
								$target . DIRECTORY_SEPARATOR . $e,
								$options)) {
					return FALSE;
				}
			}
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * Move file or directory
	 */
	public static function move($source, $target, $options = 0)
	{
		if (!file_exists($source))
			return FALSE;
		if (is_file($source)) {
			if (!file_exists($target)) {
				if (!is_dir($dirname = dirname($target)) && !mkdir($dirname, 0777, TRUE))
					return FALSE;
				return rename($source, $target);
			}
			if (is_dir($target)) {
				$target .= DIRECTORY_SEPARATOR . basename($source);
				if (is_dir($target))
					return FALSE;
			}
			if (is_file($target)) {
				if (!($options & self::OVERWRITE))
					return FALSE;
				if (($options & self::NEWERONLY) && filemtime($target) > filemtime($source))
					return unlink($source);
			}
			return unlink($target) && rename($source, $target);
		}
		if (is_dir($source) && ($options & self::RECURSIVE)) {
			if (is_link($source))
				return FALSE;
			if (!is_dir($target) && !mkdir($target, 0777, TRUE))
				return FALSE;
			foreach (scandir($source) as $e) {
				if ($e == '.' || $e == '..')
					continue;
				if (!self::move($source . DIRECTORY_SEPARATOR . $e,
								$target . DIRECTORY_SEPARATOR . $e,
								$options)) {
					return FALSE;
				}
			}
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * Remove file or directory
	 */
	public static function remove($target, $options = 0)
	{
		if (!file_exists($target))
			return TRUE;
		if (is_file($target) || is_link($target))
			return unlink($target);
		if (is_dir($target) && ($options & self::RECURSIVE)) {
			foreach (scandir($target) as $e) {
				if ($e == '.' || $e == '..')
					continue;
				if (!self::remove($target . DIRECTORY_SEPARATOR . $e, $options))
					return FALSE;
			}
			return rmdir($target);
		}
		return FALSE;
	}
	
	/**
	 * Check directory empty
	 */
	public static function is_empty($dir)
	{
		if (!is_dir($dir))
			return FALSE;
		$d = dir($dir);
		while (($e = $d->read()) !== FALSE) {
			if ($e == '.' || $e == '..')
				continue;
			$d->close();
			return FALSE;
		}
		$d->close();
		return TRUE;
	}
	
	/**
	 * Remove empty directory
	 */
	public static function rmdir($dir)
	{
	//	return self::is_empty($dir) && rmdir($dir);
		return is_dir($dir) && @rmdir($dir);
	}
}

?>