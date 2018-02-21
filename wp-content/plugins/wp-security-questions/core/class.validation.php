<?php
/**
 * WPGMP Validator class File.
 * @package Core
 * @author Flipper Code <hello@flippercode.com>
 */

if ( ! class_exists( 'FlipperCode_Validator' ) ) {

	/**
	 * FlipperCode_Validator Class
	 * @author Flipper Code <hello@flippercode.com>
	 * @package Core
	 */
	class FlipperCode_Validator {
		/**
		 * FlipperCode_Validator Constructer.
		 */
		public function __construct() {

			$this->id = 0;
		}
		/**
		 * Check if rule already exists.
		 * @param  string $varname Element name.
		 * @param  string $authType Validation Type.
		 * @return boolean           True or False.
		 */
		function is_exist($varname, $authType) {

			for ( $i = 0;$i < $this->id;$i++ ) {
				if ( $this->check_vars[ $i ]['name'] == $varname	&& $this->check_vars[ $i ]['authtype'] == $authType ) {
					return true; }
			}

			return false;
		}
		/**
		 * Add rule in queue.
		 * @param string $varname  Element name.
		 * @param array  $postVar Post Variable.
		 * @param string $authType Validation Type.
		 * @param string $error    Error message.
		 */
		public function add($varname, $postVar, $authType, $error) {

			global $frmdata;

			$is_exist = $this->is_exist( $varname,$authType );

			if ( true == $is_exist ) {
				return; }

			$index = $this->id++;
			$this->check_vars[ $index ]['name'] = $varname;
			$this->check_vars[ $index ]['data'] = $postVar;
			$this->check_vars[ $index ]['authtype'] = $authType;
			$this->check_vars[ $index ]['error'] = $error;
		}

		/**
		 * Validate all rules.
		 * @return string Validation response.
		 */
		public function validate() {

			$errordata = array();

			for ( $i = 0; $i < $this->id; $i++ ) {

				$errorMsg = '';
				$name = $this->check_vars[ $i ]['name'];
				$postVar  = $this->check_vars[ $i ]['data'];
				$authType = $this->check_vars[ $i ]['authtype'];
				$error    = $this->check_vars[ $i ]['error'];
				$pos = strpos( $authType, '=' );

				if ( false != $pos ) {
					$authType = substr( $this->check_vars[ $i ]['authtype'], 0, $pos );
					$value    = substr( $this->check_vars[ $i ]['authtype'], $pos + 1 );
				}

				switch ( $authType ) {

					case 'req': {

						if ( isset( $postVar['name'] ) and is_array( $postVar['name'] ) ) {

							$count = count( $postVar['name'] );

							for ( $j = 0; $j < $count; $j++ ) {
								$length = strlen( trim( $postVar['name'][ $j ] ) );
								if ( ! $length ) {
									$errorMsg .= $error.' :File '.($j + 1).''; }
							}
						} elseif ( isset( $postVar['name'] ) && empty( $postVar['name'] ) ) {

								$length = strlen( trim( $postVar['name'] ) );
							if ( ! $length ) {
								$errorMsg .= $error.''; }
						} else {

							$length = strlen( trim( $postVar ) );
							if ( ! $length ) {
								$errorMsg .= $error.''; }
						}

						break;
					}

					case 'alpha': {
						$regexp = '/^[A-za-z]$/';
						if ( ! preg_match( $regexp, trim( $postVar ) ) ) {
	   						$length = strlen( trim( $postVar ) );
							if ( $length ) {
								$errorMsg .= $error.'<br>'; }
	                    }
						break;
					}

					case 'alphanum': {
						$regexp = '/^[A-za-z0-9]$/';
						if ( ! preg_match( $regexp, trim( $postVar ) ) ) {
	   						$length = strlen( trim( $postVar ) );
							if ( $length ) {
								$errorMsg .= $error.'<br>'; }
	                    }
						break;
					}

					case 'num': {
						$regexp = '/^[0-9]*$/';
						if ( ! preg_match( $regexp, trim( $postVar ) ) ) {
	   						$length = strlen( trim( $postVar ) );
							if ( $length ) {
								$errorMsg .= $error.'<br>'; }
	                    }
						break;
					}

	    			case 'max': {
						$length = strlen( trim( $postVar ) );
						if ( $length > $value ) {
							$errorMsg .= $error.'<br>'; }
						break;
					}

					case 'min': {
						$length = strlen( trim( $postVar ) );
						if ( $length < $value && 0 != $length ) {
							$errorMsg .= $error.'<br>'; }
						break;
					}

					case 'lte': {
						if ( is_array( $postVar ) ) {
							$count = count( $postVar );
							if ( $count > $value ) {
								$errorMsg .= $error.'<br>'; }
						} else {
							if ( $postVar > $value ) {
								$errorMsg .= $error.'<br>'; }
						}
					    break;
					}

					case 'gte':{
						if ( is_array( $postVar ) ) {
						   	$count = count( $postVar );
							if ( $count < $value ) {
								$errorMsg .= $error.'<br>'; }
						} else {
							if ( $postVar < $value ) {
								$length = strlen( trim( $postVar ) );
								if ( $length ) {
									$errorMsg .= $error.'<br>'; }
	                        }
						}
						break;
					}

					case 'username': {
						$regexp1 = '/^[0-9]$/';
						$regexp2 = '/^[a-zA-Z]+[a-zA-Z0-9\.\_]*[a-zA-Z0-9]+$/';
						if ( ! preg_match( $regexp1, trim( $postVar ) ) && ! preg_match( $regexp2, trim( $postVar ) ) ) {
	   						$length = strlen( trim( $postVar ) );
							if ( $length ) {
								$errorMsg .= $error.'<br>'; }
	                    }
						break;
					}

					case 'name':{
						$regexp = '/^[a-zA-Z]+[a-zA-Z\.\- ]*[a-zA-Z]+$/';
						if ( ! preg_match( $regexp, trim( $postVar ) ) ) {
	   						$length = strlen( trim( $postVar ) );
							if ( $length ) {
								$errorMsg .= $error.'<br>'; }
	                    }
						break;
					}

					case 'address':{
						$regexp = '/^[a-zA-Z0-9]+.*$/';
						if ( ! preg_match( $regexp, trim( $postVar ) ) ) {
	   						$length = strlen( trim( $postVar ) );
							if ( $length ) {
								$errorMsg .= $error.'<br>'; }
	                    }
						break;
					}

					case 'phone': {

						if ( isset( $value ) ) {
							$found = strpos( $value, ',' );
							if ( false === $found ) {
								$options[0] = $value;
							} else {
								$options = explode( ',', $value );
							}
						}

						$patternMatch = 0;
						foreach ( $options as $opt ) {
							$type = $this->available_phone_type( $opt );
							foreach ( $type as $regexp ) {
								if ( preg_match( $regexp, $postVar ) ) {
									$patternMatch = 1;
								}
							}
						    if ( $patternMatch ) { break; }
						}

						if ( ! $patternMatch ) {
	  						$length = strlen( trim( $postVar ) );
							if ( $length ) {
								$errorMsg .= $error.'<br>'; }
						}
						break;
					}

					case 'allphone': {

						$regexp1 = '/^[0-9]{8,15}$/';
						// (+91)1111111111
						$regexp2 = '/^[\(][\+][0-9]{2}[\)][0-9]{8,15}$/';
						// +911111111111
						$regexp3 = '/^[\+][0-9]{2}[0-9]{8,15}$/';
						// 91-1111111111
						$regexp4 = '/^[0-9]{2}[\-][0-9]{10}$/';
						$regexp5 = '/^[0-9,\-]{8,15}$/';
						$regexp6 = '/^[0-9,\(][0-9,\-,\(,\)][0-9,\)]{10,15}$/';

						if ( ! preg_match( $regexp1, trim( $postVar ) ) && ! preg_match( $regexp2, trim( $postVar ) ) && ! preg_match( $regexp3, trim( $postVar ) ) && ! preg_match( $regexp4, trim( $postVar ) ) && ! preg_match( $regexp5, trim( $postVar ) ) && ! preg_match( $regexp6, trim( $postVar ) ) ) {
							$length = strlen( trim( $postVar ) );
							if ( $length ) {
								$errorMsg .= $error.'<br>'; }
	                    }
						break;
					}

					case 'zip':{
						$regexp = '/^[0-9]{6,10}$/';
						if ( ! preg_match( $regexp, trim( $postVar ) ) ) {
	   						$length = strlen( trim( $postVar ) );
							if ( $length ) {
								$errorMsg .= $error.'<br>'; }
	                    }
						break;
					}

					case 'uszip':{
						// 12345-6789
						$regexp = '/^[0-9]{5}[\-]{1}[0-9]{4}$/';
						if ( ! preg_match( $regexp, trim( $postVar ) ) ) {
	   						$length = strlen( trim( $postVar ) );
							if ( $length ) {
								$errorMsg .= $error.'<br>'; }
	                    }
						break;
					}

					case 'ukzip':{

						$regexp = '/^[a-zA-Z]{2}[0-9]{1}[ ]{1}[0-9]{1}[a-zA-Z]{2}$/';
						if ( ! preg_match( $regexp, trim( $postVar ) ) ) {
	   						$length = strlen( trim( $postVar ) );
							if ( $length ) {
								$errorMsg .= $error.'<br>'; }
	                    }
						break;
					}

					case 'ssn':{
						$regexp = '/^(?!000)([0-6][0-9]{2}|7([0-6][0-9]|7[012]))([ -]?)(?!00)[0-9][0-9]\3(?!0000)[0-9]{4}$/';
						if ( ! preg_match( $regexp, trim( $postVar ) ) ) {
	   						$length = strlen( trim( $postVar ) );
							if ( $length ) {
								$errorMsg .= $error.'<br>'; }
	                    }
						break;
					}

					case 'currency':{
						$regexp1 = '/^[0-9]+\.[0-9]+$/';
						$regexp2 = '/^[0-9]+$/';
						if ( ! preg_match( $regexp1, trim( $postVar ) ) && ! preg_match( $regexp2, trim( $postVar ) ) ) {
	   						$length = strlen( trim( $postVar ) );
							if ( $length ) {
								$errorMsg .= $error.'<br>'; }
	                    }
						break;
					}

					case 'email':{
					    if ( function_exists( 'is_email' ) ) {

						  	if ( ! is_email( trim( $postVar ) ) ) {

								$errorMsg .= $error.'<br>';
						  	}
						} else {
						    $regexp = '/^([0-9a-zA-Z]([-.\w]*[0-9a-zA-Z])*@([0-9a-zA-Z][-\w]*[0-9a-zA-Z]\.)+[a-zA-Z]{2,9})$/';
							if ( ! preg_match( $regexp, trim( $postVar ) ) ) {
								$length = strlen( trim( $postVar ) );
								if ( $length ) {
									$errorMsg .= $error.'<br>'; }
							}
						}
						break;
					}

					case 'url':{
						$regexp = '|^http(s)?://[a-z0-9-]+(\.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i';
						if ( ! preg_match( $regexp, trim( $postVar ) ) ) {
	   						$length = strlen( trim( $postVar ) );
							if ( $length ) {
								$errorMsg .= $error.'<br>'; }
						}
						break;
					}

					case 'ip':{
						$regexp = '/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/';
						if ( ! preg_match( $regexp, trim( $postVar ) ) ) {
	   						$length = strlen( trim( $postVar ) );
							if ( $length ) {
								$errorMsg .= $error.'<br>'; }
						}
						break;
					}

	    			case 'date':{
						$errorMsg .= $this->validate_date( trim( $postVar ), $value, $error );
						break;
					}

					case 'ftype':{
						$errorMsg .= $this->validate_file_type( $postVar, $value, $error );
						break;
					}

					case 'fsize':{
		                $errorMsg .= $this->validate_file_size( $postVar, $value, $error );
						break;
					}

					case 'imgwh':{
	                	$errorMsg .= $this->validate_image_height_width( $postVar, $value, $error );
						break;
					}

					case 'custom':{
						if ( ! preg_match( $value, trim( $postVar ) ) ) {
	   						$length = strlen( trim( $postVar ) );
							if ( $length ) {
								$errorMsg .= $error.'<br>'; }
	                    }
						break;
					}
				}

				if ( $errorMsg ) {
					$errordata[ $name ] = $error; }
			}

			if ( $errordata ) {
				return $errordata;
			} else { 			return false; }
		}
		/**
		 * Validate data input.
		 * @param  string $postVar Element name.
		 * @param  string $value   Element value.
		 * @param  string $error   Error message.
		 * @return string          Error message if not valid.
		 */
	    function validate_date($postVar, $value, $error) {

	    	$errorMsg = '';

			$length = strlen( trim( $postVar ) );
			if ( $length ) {

				if ( isset( $value ) ) {

					$found = strpos( $value, ',' );
					if ( false === $found ) {
						$options[0] = $value;
					} else {
						$options = explode( ',', $value );
					}
				} else {

					$options[0] = 'dd-mm-yyyy';
				}

				$patternMatch = 0;
				foreach ( $options as $opt ) {

					$pos1 = strpos( $opt, '-' );
					$pos2 = strpos( $opt, '/' );
					$pos3 = strpos( $opt, '.' );

					if ( false !== $pos1 ) {
						if ( 2 == $pos1 ) {
							if ( strlen( $opt ) == 8 ) {
								$regexp = '/^[0-9]{2}[\-][0-9]{2}[\-][0-9]{2}$/';
							} else { 							$regexp = '/^[0-9]{2}[\-][0-9]{2}[\-][0-9]{4}$/'; }
						}
						if ( 4 == $pos1 ) {
							$regexp = '/^[0-9]{4}[\-][0-9]{2}[\-][0-9]{2}$/'; }
					}

					if ( false !== $pos2 ) {
						if ( 2 == $pos2 ) {
							if ( strlen( $opt ) == 8 ) {
								$regexp = '/^[0-9]{2}[\/][0-9]{2}[\/][0-9]{2}$/';
							} else { 							$regexp = '/^[0-9]{2}[\/][0-9]{2}[\/][0-9]{4}$/'; }
						}
						if ( 4 == $pos2 ) {
							$regexp = '/^[0-9]{4}[\/][0-9]{2}[\/][0-9]{2}$/'; }
					}

					if ( false !== $pos3 ) {
						if ( 2 == $pos3 ) {
							if ( 8 == strlen( $opt ) ) {
								$regexp = '/^[0-9]{2}[\.][0-9]{2}[\.][0-9]{2}$/';
							} else { 							$regexp = '/^[0-9]{2}[\.][0-9]{2}[\.][0-9]{4}$/'; }
						}
						if ( 4 == $pos3 ) {
							$regexp = '/^[0-9]{4}[\.][0-9]{2}[\.][0-9]{2}$/'; }
					}

					if ( preg_match( $regexp, $postVar ) ) {

						$patternMatch = 1;
						if ( (isset( $pos1 ) && 2 == $pos1 ) || (isset( $pos2 ) && 2 == $pos2 ) || (isset( $pos3 ) && 2 == $pos3 ) ) {
							$str1 = substr( $opt, 0, 2 );
							$str2 = substr( $opt, 3, 2 );

							if ( 'dd' == $str1 ) {
								$DD = substr( $postVar, 0, 2 );
								$MM = substr( $postVar, 3, 2 );
								$YY = substr( $postVar, 6 );
							}
							if ( 'mm' == $str1 ) {
								$MM = substr( $postVar, 0, 2 );
								$DD = substr( $postVar, 3, 2 );
								$YY = substr( $postVar, 6 );
							}
							if ( 'yy' == $str1 ) {
								if ( 'mm' == $str2 ) {
									$YY = substr( $postVar, 0, 2 );
									$MM = substr( $postVar, 3, 2 );
									$DD = substr( $postVar, 6 );
								} else {
									$MM = substr( $postVar, 0, 2 );
									$DD = substr( $postVar, 3, 2 );
									$YY = substr( $postVar, 6 );
								}
							}
						}

						if ( (isset( $pos1 ) && 4 == $pos1) || (isset( $pos2 ) && 4 == $pos2) || (isset( $pos3 ) && 4 == $pos3) ) {
							$str = substr( $opt, 5, 2 );

							if ( 'dd' == $str ) {
								$YY = substr( $postVar, 0, 4 );
								$DD = substr( $postVar, 6, 2 );
								$MM = substr( $postVar, 8, 2 );
							}
							if ( 'mm' == $str ) {
								$YY = substr( $postVar, 0, 4 );
								$MM = substr( $postVar, 6, 2 );
								$DD = substr( $postVar, 6, 2 );
							}
						}

						if ( 0 == $DD || 0 == $MM || 0 == $YY ) {
							$errorMsg .= 'Invalid Date...<br>';
						}

						if ( $MM <= 12 ) {
							switch ( $MM ) {
								case 4:
								case 6:
								case 9:
								case 11:
									if ( $DD > 30 ) {
										$errorMsg .= 'Selected month has maximum 30 days.<br>';
									}
								default:
									if ( $DD > 31 ) {
										$errorMsg .= 'Selected month has maximum 31 days.<br>';
									}
								break;
							}
						}

						if ( ($YY % 4) == 0 ) {
							if ( (2 == $MM) && ($DD > 29) ) {
								$errorMsg .= 'Invalid days in February for leap year.<br>';
							}
						} else {
							if ( (2 == $MM) && ($DD > 28) ) {
								$errorMsg .= 'Invalid days in February for non leap year.<br>';
							}
						}
					}

					if ( $patternMatch ) {           break; }
				}

				if ( ! $patternMatch ) {	$errorMsg .= $error.'<br>'; }
			}
	        return $errorMsg;
	    }
	    /**
	     * Validate file type.
	     * @param  string $postVar Element name.
	     * @param  string $value   Element value.
	     * @param  string $error   Error message.
	     * @return string          Error message.
	     */
	    function validate_file_type($postVar, $value, $error) {

			$errorMsg = '';
			if ( isset( $value ) ) {
				$found = strpos( $value, ',' );
				if ( false === $found ) {
					$options[0] = $value;
				} else {
					$options = explode( ',', $value );
				}
			}

			if ( is_array( $postVar['name'] ) ) {
				$totalFiles = count( $postVar['name'] );

				for ( $i = 0; $i < $totalFiles; $i++ ) {
					if ( $postVar['name'][ $i ] ) {
	                			$fileTypeMatch = 0;
						foreach ( $options as $id => $type ) {
							$typeArray = $this->available_file_types( $type );
							if ( in_array( $postVar['type'][ $i ], $typeArray ) ) {
	                        			$fileTypeMatch = 1;
							}
							if ( $fileTypeMatch ) {	break; }
						}

						if ( ! $fileTypeMatch ) {
							$errorMsg .= $error.' ('.$postVar['name'][ $i ].')<br>';
						}
					}
				}
			} else {
		        if ( $postVar['name'] ) {
		            $fileTypeMatch = 0;
		            foreach ( $options as $id => $type ) {
		                $typeArray = $this->available_file_types( $type );
		                if ( in_array( $postVar['type'], $typeArray ) ) {
		                    $fileTypeMatch = 1;
		                }
		                if ( $fileTypeMatch ) {  break; }
		            }

		            if ( ! $fileTypeMatch ) {
		                $errorMsg .= $error.' ('.$postVar['name'].')<br>';
		            }
	            }
			}

	        return $errorMsg;
		}
		/**
		 * Available file valid extensions.
		 * @param  string $ext Extension.
		 * @return array      File types.
		 */
		function available_file_types($ext) {

			switch ( $ext ) {

				case 'txt':
					$type[0] = 'text/plain';
					break;

				case 'xml':
					$type[0] = 'text/xml';
					$type[1] = 'application/xml';
					break;

				case 'csv':
					$type[0] = 'text/x-comma-separated-values';
					$type[1] = 'application/octet-stream';
					$type[2] = 'text/plain';
					break;

				case 'zip':
					$type[0] = 'application/zip';
					break;

				case 'tar':
					$type[0] = 'application/x-gzip';
					break;

				case 'ctar':
					$type[0] = 'application/x-compressed-tar';
					break;

				case 'pdf':
					$type[0] = 'application/pdf';
					break;

				case 'doc':
					$type[0] = 'application/msword';
					$type[1] = 'application/octet-stream';
					break;

				case 'xls':
					$type[0] = 'application/vnd.ms-excel';
					$type[1] = 'application/vnd.oasis.opendocument.spreadsheet';
					break;

				case 'ppt':
					$type[0] = 'application/vnd.ms-powerpoint';
					break;

				case 'jpg':
					$type[0] = 'image/jpg';
					$type[1] = 'image/jpeg';
					$type[2] = 'image/pjpeg';
					break;

				case 'gif':
					$type[0] = 'image/gif';
					break;

				case 'png':
					$type[0] = 'image/png';
					break;

				case 'bmp':
					$type[0] = 'image/bmp';
					break;

				case 'icon':
					$type[0] = 'image/x-ico';
					break;

				case 'font':
					$type[0] = 'application/x-font-ttf';
					break;
			}

			return $type;
		}
		/**
		 * Validate file size.
		 * @param  string $postVar Element name.
		 * @param  string $value   Element value.
		 * @param  string $error   Error message.
		 * @return string          Error message.
		 */
	    function validate_file_size($postVar, $value, $error) {

	       	$errorMsg = '';
	        if ( is_array( $postVar['name'] ) ) {
				$totalFiles = count( $postVar['name'] );

		        for ( $i = 0; $i < $totalFiles; $i++ ) {
		            if ( $postVar['name'][ $i ] ) {
		                if ( $postVar['size'][ $i ] > $value ) {
		                    $errorMsg .= $error.' ('.$postVar['name'][ $i ].')<br>';
		                }
		            }
		        }
			} else {
				if ( $postVar['size'] > $value ) {
					$errorMsg .= $error.' ('.$postVar['name'].')<br>';
				}
			}

	        return $errorMsg;
	    }
		/**
		 * Validate image height and width.
		 * @param  string $postVar Element name.
		 * @param  string $value   Element value.
		 * @param  string $error   Error message.
		 * @return string          Error message.
		 */
	    function validate_image_height_width($postVar, $value, $error) {

	       	$errorMsg = '';
	    	if ( isset( $value ) ) {
				$found = strpos( $value, ',' );
				if ( false === $found ) {
					$options[0] = $value;
				} else {
					$options = explode( ',', $value );
					$W = $options[0];
					$H = $options[1];
				}
			}

			if ( is_array( $postVar['name'] ) ) {

				$totalFiles = count( $postVar['name'] );

				for ( $i = 0; $i < $totalFiles; $i++ ) {

					if ( $postVar['name'][ $i ] ) {

		                list($width, $height) = getimagesize( $postVar['tmp_name'][ $i ] );

		                if ( ($height > $W || $width > $H) && $postVar['tmp_name'][ $i ] ) {
		                    $errorMsg .= $error.' ('.$postVar['name'][ $i ].')<br>';
		                }
					}
				}
			} else {

				list($width, $height) = getimagesize( $postVar['tmp_name'] );
				if ( ($height < $H || $width < $W) && $postVar['tmp_name'] ) {
					$errorMsg .= $error.' ('.$postVar['name'].')<br>';
				}
			}

	        return $errorMsg;
	    }
		/**
		 * Available phone type.
		 * @param  string $country Country name.
		 * @return array          Phone type expressions.
		 */
	    function available_phone_type($country) {

			switch ( $country ) {

				case 'in': // India.
					$type[0]  = '/^[0-9]{6,10}$/';
					// (+91)[022]111111.
					$type[1]  = '/^[\(][\+][0-9]{2}[\)][\[][0-9]{3,5}[\]][0-9]{6,10}$/';
					// +91022111111.
					$type[2]  = '/^[\+][0-9]{2}[0-9]{3,5}[0-9]{6,10}$/';
					// 91-111111.
					$type[3]  = '/^[0-9]{2}[\-][0-9]{6,10}$/';
					break;

				case 'br': // Brazil.
					$type[0] = '/^([0-9]{2})?(\([0-9]{2})\)([0-9]{3}|[0-9]{4})-[0-9]{4}$/';
					break;

				case 'fr': // France.
					$type[0] = '/^([0-9]{2})?(\([0-9]{2})\)([0-9]{3}|[0-9]{4})-[0-9]{4}$/';
					break;

				case 'us': // US.
					$type[0] = '/^[\(][0-9]{3}[\)][0-9]{3}[\-][0-9]{4}$/';
					break;

				case 'sw': // Swedish.
					$type[0] = '/^(([+][0-9]{2}[ ][1-9][0-9]{0,2}[ ])|([0][0-9]{1,3}[-]))(([0-9]{2}([ ][0-9]{2}){2})|([0-9]{3}([ ][0-9]{3})*([ ][0-9]{2})+))$/';
					break;
			}

			return $type;
	    }
	}
}
