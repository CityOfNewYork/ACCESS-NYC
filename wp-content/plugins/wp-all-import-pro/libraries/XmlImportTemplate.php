<?php
/**
 * @author Olexandr Zanichkovsky <olexandr.zanichkovsky@zophiatech.com>
 * @author Pavel Kulbakin <p.kulbakin@gmail.com>
 * @package General
 */

require_once dirname(__FILE__) . '/XmlImportConfig.php';

/**
 * Represents a template
 */
class XmlImportTemplate {
	/**
	 * Root element of the record that is being parsed
	 *
	 * @var SimpleXMLElement
	 */
	protected $xml;

	/**
	 * file name of the cached template
	 *
	 * @var string
	 */
	protected $cachedTemplate;

	/**
	 * Creates new instance
	 *
	 * @param SimpleXmlElement $xml
	 * @param string $cachedTemplate
	 */
	public function __construct($xml, $cachedTemplate)
	{
		$this->xml = $xml;
		$this->cachedTemplate = $cachedTemplate;
	}

	/**
	 * Parses a template using {@see $this->xml}
	 *
	 * @return string
	 */
	public function parse()
	{			
		
		ob_start();
		$err_lvl = error_reporting(E_ALL);
		include $this->cachedTemplate;
		error_reporting($err_lvl);
		
		return ob_get_clean();
	}

	/**
	 * Get the value by XPath expression
	 *
	 * @param SimpleXmlElement $xpath XPath result
	 * @return mixed
	 */
	protected function getValue($xpath = array())
	{
				
		if (is_array($xpath) && count($xpath) > 0) {
			$result = array();
			foreach ($xpath as $xp) { // cancatenate multiple elements into 1 string				
				ob_start();
				echo $xp;
				$result[] = trim(ob_get_clean());
			}			
			return implode(XmlImportConfig::getInstance()->getMultiGlue(), $result);
		} else {
			// return null if nothing found
			return null;
		}
	}
}