<?php

if ( ! class_exists('PMXE_Zip')){

	class PMXE_Zip
	{
		/**
		 * Add files and sub-directories in a folder to zip file.
		 * @param string $folder
		 * @param ZipArchive|PclZip $zipFile
		 * @param int $exclusiveLength Number of text to be exclusived from the file path.
		 */
		private static function folderToZip($folder, &$zipFile, $exclusiveLength, $type = 'zip', $removePath = '') {
			$handle = opendir($folder);
			if($handle !== false) {
				while (false !== $f = readdir($handle)) {
					if ($f != '.' && $f != '..') {
						$filePath = "$folder/$f";
						// Remove prefix from file path before add to zip.
						$localPath = substr($filePath, $exclusiveLength);
						// Fall back to PclZip if ZipArchive is unavailable.
						if( 'zip' === $type ) {
							if ( is_file( $filePath ) ) {
								$zipFile->addFile( $filePath, $localPath );
							} elseif ( is_dir( $filePath ) ) {
								// Add sub-directory.
								$zipFile->addEmptyDir( $localPath );
								self::folderToZip( $filePath, $zipFile, $exclusiveLength );
							}
						}else{
							if( is_file($filePath)) {
								$zipFile->add( $filePath, '', $removePath );
							}
						}
					}
				}
				closedir($handle);
			}
		}

		/**
		 * Zip a folder (include itself).
		 * Usage:
		 *   PMXE_Zip::zipDir('/path/to/sourceDir', '/path/to/out.zip');
		 *
		 * @param string $sourcePath Path of directory to be zip.
		 * @param string $outZipPath Path of output zip file.
		 */
		public static function zipDir($sourcePath, $outZipPath)
		{
			$pathInfo = pathInfo($sourcePath);
			$parentPath = $pathInfo['dirname'];
			$dirName = $pathInfo['basename'];

			// Fall back to PclZip if ZipArchive is unavailable.
			if(class_exists('ZipArchive')){
				$z = new ZipArchive();
				$z->open($outZipPath, ZIPARCHIVE::CREATE);
				$z->addEmptyDir($dirName);
				self::folderToZip($sourcePath, $z, strlen("$parentPath/"));
				$z->close();
			}else{
				require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';

				$z = new PclZip($outZipPath);
				self::folderToZip($sourcePath, $z, strlen("$parentPath/"), 'pcl', $parentPath);


			}
		}
	}

}