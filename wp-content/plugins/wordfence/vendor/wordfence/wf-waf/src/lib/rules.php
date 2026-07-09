<?php
if (defined('WFWAF_VERSION') && !defined('WFWAF_RUN_COMPLETE')) {

interface wfWAFRuleInterface {

	/**
	 * @return string
	 */
	public function render();

	public function renderRule();

	public function evaluate();
}

class wfWAFRuleException extends wfWAFException {
}

class wfWAFRuleLogicalOperatorException extends wfWAFException {
}

class wfWAFRule implements wfWAFRuleInterface {

	private $ruleID;
	private $type;
	private $category;
	private $score;
	private $description;
	private $whitelist;
	private $action;
	/** @var wfWAFRuleComparisonGroup */
	private $comparisonGroup;
	/**
	 * @var wfWAF
	 */
	private $waf;

	/**
	 * @param wfWAF $waf
	 * @param int $ruleID
	 * @param string $type
	 * @param string $category
	 * @param int $score
	 * @param string $description
	 * @param int $whitelist
	 * @param string $action
	 * @param wfWAFRuleComparisonGroup $comparisonGroup
	 * @return wfWAFRule
	 */
	public static function create() {
		$waf = func_get_arg(0);
		$ruleID = func_get_arg(1);
		$type = func_get_arg(2);
		$category = func_get_arg(3);
		$score = func_get_arg(4);
		$description = func_get_arg(5);
		$whitelist = 1;
		$action = '';
		$comparisonGroup = null;
		//Compatibility with old compiled rules
		if (func_num_args() == 8) { //Pre-whitelist flag
			$action = func_get_arg(6);
			$comparisonGroup = func_get_arg(7);
		}
		else if (func_num_args() == 9) { //Whitelist flag
			$whitelist = func_get_arg(6);
			$action = func_get_arg(7);
			$comparisonGroup = func_get_arg(8);
		}
		return new self($waf, $ruleID, $type, $category, $score, $description, $whitelist, $action, $comparisonGroup);
	}

	/**
	 * @param string $value
	 * @return string
	 */
	public static function exportString($value) {
		return sprintf("'%s'", str_replace("'", "\\'", $value));
	}

	/**
	 * @param wfWAF $waf
	 * @param int $ruleID
	 * @param string $type
	 * @param string $category
	 * @param int $score
	 * @param string $description
	 * @param int $whitelist
	 * @param string $action
	 * @param wfWAFRuleComparisonGroup $comparisonGroup
	 */
	public function __construct($waf, $ruleID, $type, $category, $score, $description, $whitelist, $action, $comparisonGroup) {
		$this->setWAF($waf);
		$this->setRuleID($ruleID);
		$this->setType($type);
		$this->setCategory($category);
		$this->setScore($score);
		$this->setDescription($description);
		$this->setWhitelist($whitelist);
		$this->setAction($action);
		$this->setComparisonGroup($comparisonGroup);
	}

	public function __sleep() {
		return array(
			'ruleID',
			'type',
			'category',
			'score',
			'description',
			'whitelist',
			'action',
			'comparisonGroup',
		);
	}

	/**
	 * @return string
	 */
	public function render() {
		return sprintf('%s::create($this, %d, %s, %s, %s, %s, %d, %s, %s)', get_class($this),
			$this->getRuleID(),
			var_export($this->getType(), true),
			var_export($this->getCategory(), true),
			var_export($this->getScore(), true),
			var_export($this->getDescription(), true),
			var_export($this->getWhitelist(), true),
			var_export($this->getAction(), true),
			$this->getComparisonGroup()->render()
		);
	}

	/**
	 * @return string
	 */
	public function renderRule() {
		return sprintf(<<<RULE
if %s:
	%s(%s)
RULE
			,
			$this->getComparisonGroup()->renderRule(),
			$this->getAction(),
			join(', ', array_filter(array(
				$this->getRuleID() ? 'id=' . (int) $this->getRuleID() : '',
				$this->getCategory() ? 'category=' . self::exportString($this->getCategory()) : '',
				$this->getScore() > 0 ? 'score=' . (int) $this->getScore() : '',
				$this->getDescription() ? 'description=' . self::exportString($this->getDescription()) : '',
				$this->getWhitelist() == 0 ? 'whitelist=0' : '',
			)))
		);
	}

	public function evaluate() {
		$comparisons = $this->getComparisonGroup();
		$waf = $this->getWAF();
		if ($comparisons instanceof wfWAFRuleComparisonGroup && $waf instanceof wfWAF) {
			$comparisons->setRule($this);
			if ($comparisons->evaluate()) {
				$waf->tripRule($this);
				return true;
			}
		}
		return false;
	}

	public function debug() {
		return $this->getComparisonGroup()->debug();
	}

	/**
	 * For JSON.
	 *
	 * @return array
	 */
	public function toArray() {
		return array(
			'ruleID'      => $this->getRuleID(),
			'type'        => $this->getType(),
			'category'    => $this->getCategory(),
			'score'       => $this->getScore(),
			'description' => $this->getDescription(),
			'whitelist'   => $this->getWhitelist(),
			'action'      => $this->getAction(),
		);
	}

	/**
	 * @return int
	 */
	public function getRuleID() {
		return $this->ruleID;
	}

	/**
	 * @param int $ruleID
	 */
	public function setRuleID($ruleID) {
		$this->ruleID = $ruleID;
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @param string $type
	 */
	public function setType($type) {
		$this->type = $type;
	}

	/**
	 * @return string
	 */
	public function getCategory() {
		return $this->category;
	}

	/**
	 * @param string $category
	 */
	public function setCategory($category) {
		$this->category = $category;
	}

	/**
	 * @return int
	 */
	public function getScore() {
		return $this->score;
	}

	/**
	 * @param int $score
	 */
	public function setScore($score) {
		$this->score = $score;
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @param string $description
	 */
	public function setDescription($description) {
		$this->description = $description;
	}

	/**
	 * @return int
	 */
	public function getWhitelist() {
		return $this->whitelist;
	}

	/**
	 * @param string $whitelist
	 */
	public function setWhitelist($whitelist) {
		$this->whitelist = $whitelist;
	}

	/**
	 * @return string
	 */
	public function getAction() {
		return $this->action;
	}

	/**
	 * @param string $action
	 */
	public function setAction($action) {
		$this->action = $action;
	}

	/**
	 * @return wfWAFRuleComparisonGroup
	 */
	public function getComparisonGroup() {
		return $this->comparisonGroup;
	}

	/**
	 * @param wfWAFRuleComparisonGroup $comparisonGroup
	 */
	public function setComparisonGroup($comparisonGroup) {
		$this->comparisonGroup = $comparisonGroup;
	}

	/**
	 * @return wfWAF
	 */
	public function getWAF() {
		return $this->waf;
	}

	/**
	 * @param wfWAF $waf
	 */
	public function setWAF($waf) {
		$this->waf = $waf;
		if ($this->comparisonGroup) {
			$this->comparisonGroup->setWAF($waf);
		}
	}
}

class wfWAFRuleLogicalOperator implements wfWAFRuleInterface {

	/**
	 * @var string
	 */
	private $operator;

	/**
	 * @var array
	 */
	protected $validOperators = array(
		'||',
		'&&',
		'and',
		'or',
		'xor',
	);
	/**
	 * @var bool
	 */
	private $currentValue = false;
	/**
	 * @var wfWAFRuleInterface
	 */
	private $comparison;

	/**
	 * @param string $operator
	 * @param bool $currentValue
	 * @param wfWAFRuleInterface $comparison
	 */
	public function __construct($operator, $currentValue = false, $comparison = null) {
		$this->setOperator($operator);
		$this->setCurrentValue($currentValue);
		$this->setComparison($comparison);
	}

	public function __sleep() {
		return array(
			'operator',
			'currentValue',
			'comparison',
		);
	}

	/**
	 * @return string
	 * @throws wfWAFRuleLogicalOperatorException
	 */
	public function render() {
		if (!$this->isValid()) {
			throw new wfWAFRuleLogicalOperatorException(sprintf('Invalid logical operator "%s", must be one of %s', $this->getOperator(), join(", ", $this->validOperators)));
		}
		return sprintf("new %s(%s)", get_class($this), var_export(trim(wfWAFUtils::strtoupper($this->getOperator())), true));
	}

	/**
	 * @return string
	 * @throws wfWAFRuleLogicalOperatorException
	 */
	public function renderRule() {
		if (!$this->isValid()) {
			throw new wfWAFRuleLogicalOperatorException(sprintf('Invalid logical operator "%s", must be one of %s', $this->getOperator(), join(", ", $this->validOperators)));
		}
		return trim(wfWAFUtils::strtolower($this->getOperator()));
	}

	public function evaluate() {
		$currentValue = $this->getCurrentValue();
		$comparison = $this->getComparison();
		if (is_bool($currentValue) && $comparison instanceof wfWAFRuleInterface) {
			switch (wfWAFUtils::strtolower($this->getOperator())) {
				case '&&':
				case 'and':
					return $currentValue && $comparison->evaluate();

				case '||':
				case 'or':
					return $currentValue || $comparison->evaluate();

				case 'xor':
					return $currentValue xor $comparison->evaluate();
			}
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public function isValid() {
		return in_array(wfWAFUtils::strtolower($this->getOperator()), $this->validOperators);
	}

	/**
	 * @return string
	 */
	public function getOperator() {
		return $this->operator;
	}

	/**
	 * @param string $operator
	 */
	public function setOperator($operator) {
		$this->operator = $operator;
	}

	/**
	 * @return boolean
	 */
	public function getCurrentValue() {
		return $this->currentValue;
	}

	/**
	 * @param boolean $currentValue
	 */
	public function setCurrentValue($currentValue) {
		$this->currentValue = $currentValue;
	}

	/**
	 * @return wfWAFRuleInterface
	 */
	public function getComparison() {
		return $this->comparison;
	}

	/**
	 * @param wfWAFRuleInterface $comparison
	 */
	public function setComparison($comparison) {
		$this->comparison = $comparison;
	}
}

class wfWAFPhpBlock {
	public $open = false;
	public $echoTag;
	public $shortTag;
	public $openParentheses = 0, $closedParentheses = 0;
	public $backtickCount = 0;
	public $badCharacter = false;
	public $mismatchedParentheses = false;

	public function __construct($echoTag = false, $shortTag = false) {
		$this->echoTag = $echoTag;
		$this->shortTag = $shortTag;
	}

	public function hasParentheses() {
		return $this->openParentheses > 0 && $this->closedParentheses === $this->openParentheses;
	}

	public function hasBacktickPair() {
		return $this->backtickCount > 0 && $this->backtickCount % 2 === 0;
	}

	public function hasParenthesesOrBacktickPair() {
		return $this->hasParentheses() || $this->hasBacktickPair();
	}

	public function hasMismatchedParentheses() {
		return $this->mismatchedParentheses || $this->closedParentheses !== $this->openParentheses;
	}

	public function hasSyntaxError() {
		if (version_compare(phpversion(), '8.0.0', '>=')) {
			return $this->badCharacter;
		}
		return $this->hasMismatchedParentheses();
	}

	public static function isValid($phpBlock) {
		return $phpBlock !== null && !$phpBlock->hasSyntaxError();
	}

	public static function extend($phpBlock, $echoTag = false, $shortTag = false) {
		if ($phpBlock === null)
			$phpBlock = new self();
		$phpBlock->open = true;
		$phpBlock->echoTag = $echoTag;
		$phpBlock->shortTag = $shortTag;
		return $phpBlock;
	}
}

class wfWAFRuleComparison implements wfWAFRuleInterface {

	private $matches;
	private $failedSubjects;
	private $result;
	/**
	 * @var wfWAFRule
	 */
	private $rule;

	private static $scalarActions = array(
		'contains',
		'notcontains',
		'match',
		'notmatch',
		'matchcount',
		'containscount',
		'equals',
		'notequals',
		'identical',
		'notidentical',
		'greaterthan',
		'greaterthanequalto',
		'lessthan',
		'lessthanequalto',
		'lengthgreaterthan',
		'lengthlessthan',
		'currentuseris',
		'currentuserisnot',
		'md5equals',
		'filepatternsmatch',
		'filehasphp',
		'islocalurl',
		'isremoteurl',
		'isvalidurl',
		'isnotvalidurl',
		'urlhostequals',
		'urlhostnotequals',
		'urlhostmatches',
		'urlhostnotmatches',
		'urlschemeequals',
		'urlschemenotequals',
		'urlschemematches',
		'urlschemenotmatches',
		'versionequals',
		'versionnotequals',
		'versiongreaterthan',
		'versiongreaterthanequalto',
		'versionlessthan',
		'versionlessthanequalto',
		'exists'
	);

	private static $arrayActions = array(
		'keyexists',
		'keymatches'
	);

	private static $globalActions = array(
		'hasuser',
		'nothasuser',
		'currentusercan',
		'currentusercannot'
	);

	const ACTION_TYPE_SCALAR=0;
	const ACTION_TYPE_ARRAY=1;
	const ACTION_TYPE_GLOBAL=2;

	/**
	 * @var mixed
	 */
	private $expected;
	/**
	 * @var mixed
	 */
	private $subjects;
	/**
	 * @var string
	 */
	private $action;
	private $multiplier;
	/**
	 * @var wfWAF
	 */
	private $waf;

	/**
	 * @param wfWAF $waf
	 * @param string $action
	 * @param mixed $expected
	 * @param mixed $subjects
	 */
	public function __construct($waf, $action, $expected, $subjects = null) {
		$this->setWAF($waf);
		$this->setAction($action);
		$this->setExpected($expected);
		$this->setSubjects($subjects);
	}

	public function __sleep() {
		return array(
			'rule',
			'action',
			'expected',
			'subjects',
		);
	}
	
	public function __wakeup() {
		if (empty($this->getWAF())) {
			$this->setWAF(wfWAF::getInstance());
		}
	}

	/**
	 * @param string|array $subject
	 * @return string
	 */
	public static function getSubjectKey($subject) {
		if (!is_array($subject)) {
			return (string) $subject;
		}
		$return = '';
		$global = array_shift($subject);
		if ($global instanceof wfWAFRuleComparisonSubject) {
			$global = 'filtered';
		}
		foreach ($subject as $key) {
			$return .= '[' . $key . ']';
		}
		return $global . $return;
	}

	/**
	 * @return string
	 * @throws wfWAFRuleException
	 */
	public function render() {
		if (!$this->isActionValid()) {
			throw new wfWAFRuleException('Invalid action passed to ' . get_class($this) . ', action: ' . var_export($this->getAction(), true));
		}
		$subjectExport = '';
		/** @var wfWAFRuleComparisonSubject $subject */
		foreach ($this->getSubjects() as $subject) {
			$subjectExport .= $subject->render() . ",\n";
		}
		$subjectExport = 'array(' . wfWAFUtils::substr($subjectExport, 0, -2) . ')';

		$expected = $this->getExpected();
		return sprintf('new %s($this, %s, %s, %s)', get_class($this), var_export((string) $this->getAction(), true),
			($expected instanceof wfWAFRuleVariable ? $expected->render() : var_export($expected, true)), $subjectExport);
	}

	/**
	 * @return string
	 * @throws wfWAFRuleException
	 */
	public function renderRule() {
		if (!$this->isActionValid()) {
			throw new wfWAFRuleException('Invalid action passed to ' . get_class($this) . ', action: ' . var_export($this->getAction(), true));
		}
		$subjectExport = '';
		/** @var wfWAFRuleComparisonSubject $subject */
		foreach ($this->getSubjects() as $subject) {
			$subjectExport .= $subject->renderRule() . ", ";
		}
		$subjectExport = wfWAFUtils::substr($subjectExport, 0, -2);

		$expected = $this->getExpected();
		return sprintf('%s(%s, %s)', $this->getAction(),
			($expected instanceof wfWAFRuleVariable ? $expected->renderRule() : wfWAFRule::exportString($expected)),
			$subjectExport);
	}

	public function getActionType() {
		$action=wfWAFUtils::strtolower($this->getAction());
		if (in_array($action, self::$scalarActions)) {
			return self::ACTION_TYPE_SCALAR;
		}
		else if(in_array($action, self::$arrayActions)) {
			return self::ACTION_TYPE_ARRAY;
		}
		else if(in_array($action, self::$globalActions)) {
			return self::ACTION_TYPE_GLOBAL;
		}
		else {
			return null;
		}
	}

	public function isActionValid() {
		return $this->getActionType() !== null;
	}

	public function hasSubject() {
		return $this->getActionType() !== self::ACTION_TYPE_GLOBAL;
	}

	private function isWhitelisted($subjectKey = '') {
		return $this->getWAF() && $this->getRule() &&
			$this->getWAF()->isRuleParamWhitelisted($this->getRule()->getRuleID(), $this->getWAF()->getRequest()->getPath(), $subjectKey);
	}

	public function evaluate() {
		$type = $this->getActionType();
		if ($type===null) {
			return false;
		}
		else if ($type===self::ACTION_TYPE_GLOBAL) {
			return (!$this->isWhitelisted()) && ($this->result=call_user_func(array($this, $this->getAction())));
		}
		$subjects = $this->getSubjects();
		if (!is_array($subjects)) {
			return false;
		}

		$this->result = false;
		/** @var wfWAFRuleComparisonSubject $subject */
		foreach ($subjects as $subject) {
			$global = $subject->getValue();
			$subjectKey = $subject->getKey();

			if ($this->_evaluate(array($this, $this->getAction()), $global, $subjectKey, $type===self::ACTION_TYPE_SCALAR)) {
				$this->result = true;
			}
		}
		return $this->result;
	}

	/**
	 * @param callback $callback
	 * @param mixed $global
	 * @param string $subjectKey
	 * @param bool $iterate
	 * @return bool
	 */
	private function _evaluate($callback, $global, $subjectKey, $iterate) {
		$result = false;

		if ($this->isWhitelisted($subjectKey)) {
			return $result;
		}

		if (is_array($global) && $iterate) {
			foreach ($global as $key => $value) {
				if ($this->_evaluate($callback, $value, $subjectKey . '[' . $key . ']', $iterate)) {
					$result = true;
				}
			}
		} else if (call_user_func($callback, $global)) {
			$result = true;
			$this->failedSubjects[] = array(
				'subject'    => $subjectKey,
				'value'      => is_string($global) ? $global : wfWAFUtils::json_encode($global),
				'multiplier' => $this->getMultiplier(),
				'matches'    => $this->getMatches(),
			);
		}
		return $result;
	}

	public function contains($subject) {
		if (is_array($this->getExpected())) {
			return in_array($this->getExpected(), $subject);
		}
		return wfWAFUtils::strpos((string) $subject, (string) $this->getExpected()) !== false;
	}

	public function notContains($subject) {
		return !$this->contains($subject);
	}

	public function match($subject) {
		return preg_match((string) $this->getExpected(), (string) $subject, $this->matches) > 0;
	}

	public function notMatch($subject) {
		return !$this->match($subject);
	}

	public function matchCount($subject) {
		$this->multiplier = preg_match_all((string) $this->getExpected(), (string) $subject, $this->matches);
		return $this->multiplier > 0;
	}

	public function containsCount($subject) {
		if (is_array($this->getExpected())) {
			$this->multiplier = 0;
			foreach ($this->getExpected() as $val) {
				if ($val == $subject) {
					$this->multiplier++;
				}
			}
			return $this->multiplier > 0;
		}
		$this->multiplier = wfWAFUtils::substr_count($subject, $this->getExpected());
		return $this->multiplier > 0;
	}

	public function equals($subject) {
		return $this->getExpected() == $subject;
	}

	public function notEquals($subject) {
		return $this->getExpected() != $subject;
	}

	public function identical($subject) {
		return $this->getExpected() === $subject;
	}

	public function notIdentical($subject) {
		return $this->getExpected() !== $subject;
	}

	public function greaterThan($subject) {
		return $subject > $this->getExpected();
	}

	public function greaterThanEqualTo($subject) {
		return $subject >= $this->getExpected();
	}

	public function lessThan($subject) {
		return $subject < $this->getExpected();
	}

	public function lessThanEqualTo($subject) {
		return $subject <= $this->getExpected();
	}

	public function lengthGreaterThan($subject) {
		return wfWAFUtils::strlen(is_array($subject) ? join('', $subject) : (string) $subject) > $this->getExpected();
	}

	public function lengthLessThan($subject) {
		return wfWAFUtils::strlen(is_array($subject) ? join('', $subject) : (string) $subject) < $this->getExpected();
	}

	public function currentUserIs($subject) {
		if ($authCookie = $this->getWAF()->parseAuthCookie()) {
			return $authCookie['role'] === $this->getExpected();
		}
		return false;
	}

	public function currentUserIsNot($subject) {
		return !$this->currentUserIs($subject);
	}

	public function hasUser() {
		return $this->getWAF()->parseAuthCookie()!==false;
	}

	public function notHasUser() {
		return !$this->hasUser();
	}

	public function currentUserCan() {
		return $this->getWAF()->checkCapability($this->getExpected());
	}

	public function currentUserCannot() {
		return !$this->currentUserCan();
	}

	public function md5Equals($subject) {
		return md5((string) $subject) === $this->getExpected();
	}
	
	public function filePatternsMatch($subject) {
		$request = $this->getWAF()->getRequest();
		$files = $request->getFiles();
		$patterns = $this->getWAF()->getMalwareSignatures();
		$commonStrings = $this->getWAF()->getMalwareSignatureCommonStrings();
		if (!is_array($patterns) || !is_array($files)) {
			return false;
		}
		
		$backtrackLimit = ini_get('pcre.backtrack_limit');
		if (is_numeric($backtrackLimit)) {
			$backtrackLimit = (int) $backtrackLimit;
			if ($backtrackLimit > 10000000) {
				ini_set('pcre.backtrack_limit', 1000000);
			}
		}
		else {
			$backtrackLimit = false;
		}
		
		foreach ($files as $file) {
			if ($file['name'] == (string) $subject) {
				if (!is_file($file['tmp_name'])) {
					continue;
				}
				$fh = @fopen($file['tmp_name'], 'r');
				if (!$fh) {
					continue;
				}
				$totalRead = 0;
				
				$first = true;
				$readsize = max(min(10 * 1024 * 1024, wfWAFUtils::iniSizeToBytes(ini_get('upload_max_filesize'))), 1 * 1024 * 1024);
				while (!feof($fh)) {
					$data = fread($fh, $readsize);
					$totalRead += strlen($data);
					if ($totalRead < 1) {
						return false;
					}
					
					$commonStringsChecked = array();
					foreach ($patterns as $index => $rule) {
						if (@preg_match('/' . $rule . '/iS', '') === false) {
							continue; //This PCRE version can't compile the rule
						}
						
						if (!$first && substr($rule, 0, 1) == '^') {
							continue; //Signature only applies to file beginning
						}
						
						if (isset($commonStrings[$index])) {
							foreach ($commonStrings[$index] as $s) {
								if (!isset($commonStringsChecked[$s])) {
									$commonStringsChecked[$s] = (preg_match('/' . $s . '/iS', $data) == 1);
								}
								
								if (!$commonStringsChecked[$s]) {
									continue 2;
								}
							}
						}
						
						if (preg_match('/(' . $rule . ')/iS', $data, $matches)) {
							if ($backtrackLimit !== false) { ini_set('pcre.backtrack_limit', $backtrackLimit); }
							return true;
						}
					}
					
					$first = false;
				}	
			}
		}
		
		if ($backtrackLimit !== false) { ini_set('pcre.backtrack_limit', $backtrackLimit); }
		return false;
	}

	private function checkForPhp($path) {
		if (!is_file($path))
			return false;
		$fh = @fopen($path, 'r');
		if ($fh === false)
			return false;
		//T_BAD_CHARACTER is only available since PHP 7.4.0 and before 7.0.0
		$T_BAD_CHARACTER = defined('T_BAD_CHARACTER') ? constant('T_BAD_CHARACTER') : 10001;
		$phpBlock = null;
		$wrappedTokenCheckBytes = '';
		$maxTokenSize = 15; //__halt_compiler
		$possibleWrappedTokens = array('<?php', '<?=', '<?', '?>', 'exit', 'new', 'clone', 'echo', 'print', 'require', 'include', 'require_once', 'include_once', '__halt_compiler');

		$readsize = 1024 * 1024; //1MB chunks
		$iteration = 0;
		$shortOpenTagEnabled = (bool) ini_get('short_open_tag');
		do {
			$data = fread($fh, $readsize);
			$actualReadsize = strlen($data);
			if ($actualReadsize === 0)
				break;

			//Make sure we didn't miss PHP split over a chunking boundary
			$wrappedCheckLength = strlen($wrappedTokenCheckBytes);
			if ($wrappedCheckLength > 0) {
				$testBytes = $wrappedTokenCheckBytes . substr($data, 0, min($maxTokenSize, $actualReadsize));
				foreach ($possibleWrappedTokens as $t) {
					$position = strpos($testBytes, $t);
					if ($position !== false && $position < $wrappedCheckLength && $position + strlen($t) >= $wrappedCheckLength) { //Found a token that starts before this segment of data and ends within it
						$data = substr($wrappedTokenCheckBytes, $position) . $data;
						break;
					}
				}
			}

			$prepended = NULL;

			//Make sure it tokenizes correctly if chunked
			if ($phpBlock !== null) {
				if ($phpBlock->echoTag) {
					$data = '<?= ' . $data;
					$prepended = T_OPEN_TAG_WITH_ECHO;
				}
				else {
					$data = '<?php ' . $data;
					$prepended = T_OPEN_TAG;
				}
			}

			//Tokenize the data and check for PHP
			$this->_resetErrors();
			$tokens = @token_get_all($data);
			$error = error_get_last();

			if ($error !== null && feof($fh) && stripos($error['message'], 'Unterminated comment') !== false)
				break;

			$firstToken = reset($tokens);
			if (is_array($firstToken) && $firstToken[0] === $prepended)
				array_shift($tokens); //Ignore the prepended token; it is only relevant for token_get_all

			$offset = 0;
			foreach ($tokens as $token) {
				if (is_array($token)) {
					$offset += strlen($token[1]);
					switch ($token[0]) {
						case T_OPEN_TAG:
							$phpBlock = wfWAFPhpBlock::extend($phpBlock, false, $token[1] === '<?');
							break;
						case T_OPEN_TAG_WITH_ECHO:
							$phpBlock = wfWAFPhpBlock::extend($phpBlock, true);
							break;
						case T_CLOSE_TAG:
							if (wfWAFPhpBlock::isValid($phpBlock) && ($phpBlock->echoTag || $phpBlock->hasParenthesesOrBacktickPair())) {
								fclose($fh);
								return true;
							}
							$phpBlock->open = false;
							break;
						case T_NEW:
						case T_CLONE:
						case T_ECHO:
						case T_PRINT:
						case T_REQUIRE:
						case T_INCLUDE:
						case T_REQUIRE_ONCE:
						case T_INCLUDE_ONCE:
						case T_HALT_COMPILER:
						case T_EXIT:
							if (wfWAFPhpBlock::isValid($phpBlock)) {
								fclose($fh);
								return true;
							}
							break;
						case $T_BAD_CHARACTER:
							if ($phpBlock !== null)
								$phpBlock->badCharacter = true;
							break;
						case T_STRING:
							if (!$phpBlock->shortTag && preg_match('/^[A-z0-9_]{3,}$/', $token[1]) && function_exists($token[1])) {
								fclose($fh);
								return true;
							}
							break;
					}
				}
				else {
					$offset += strlen($token);
					if ($phpBlock !== null) {
						switch ($token) {
							case '(':
								$phpBlock->openParentheses++;
								break;
							case ')':
								if ($phpBlock->openParentheses > $phpBlock->closedParentheses)
									$phpBlock->closedParentheses++;
								else
									$phpBlock->mismatchedParentheses = true;
								break;
							case '`':
								$phpBlock->backtickCount++;
								break;
						}
					}
				}
			}

			if (wfWAFPhpBlock::isValid($phpBlock) && $phpBlock->hasParenthesesOrBacktickPair()) {
				fclose($fh);
				return true;
			}

			$wrappedTokenCheckBytes = substr($data, - min($maxTokenSize, $actualReadsize));
		} while (!feof($fh));

		fclose($fh);
		return false;
	}

	public function fileHasPHP($subject) {
		$request = $this->getWAF()->getRequest();
		$files = $request->getFiles();
		if (!is_array($files)) {
			return false;
		}

		foreach ($files as $file) {
			if ($file['name'] === (string) $subject && $this->checkForPhp($file['tmp_name']))
					return true;
		}

		return false;
	}

	private function _resetErrors() {
		if (function_exists('error_clear_last')) {
			error_clear_last();
		}
		else {
			// set error_get_last() to defined state by forcing an undefined variable error
			set_error_handler(array($this, '_resetErrorsHandler'), 0);
			@$undefinedVariable;
			restore_error_handler();
		}
	}
	
	public function _resetErrorsHandler($errno, $errstr, $errfile, $errline) {
		//Do nothing
	}
	
	public function isLocalURL($subject) {
		if (empty($subject)) {
			return false;
		}
		
		$parsed = wfWAFUtils::parse_url((string) $subject);
		if (!isset($parsed['host'])) {
			return true;
		}
		
		$guessSiteURL = sprintf('%s://%s/', wfWAF::getInstance()->getRequest()->getProtocol(), wfWAF::getInstance()->getRequest()->getHost());
		$siteURL = wfWAF::getInstance()->getStorageEngine()->getConfig('siteURL', null, 'synced') ? wfWAF::getInstance()->getStorageEngine()->getConfig('siteURL', null, 'synced') : $guessSiteURL;
		$homeURL = wfWAF::getInstance()->getStorageEngine()->getConfig('homeURL', null, 'synced') ? wfWAF::getInstance()->getStorageEngine()->getConfig('homeURL', null, 'synced') : $guessSiteURL;
		
		$siteHost = wfWAFUtils::parse_url($siteURL, PHP_URL_HOST);
		$homeHost = wfWAFUtils::parse_url($homeURL, PHP_URL_HOST);
		
		return (is_string($siteHost) && strtolower($parsed['host']) == strtolower($siteHost)) || (is_string($homeHost) && strtolower($parsed['host']) == strtolower($homeHost));
	}
	
	public function isRemoteURL($subject) {
		if (empty($subject)) {
			return false;
		}
		
		return !$this->isLocalURL($subject);
	}
	
	public function isValidURL($subject) {
		if ($subject === null) {
			return false;
		}
		return wfWAFUtils::validate_url((string) $subject) !== false;
	}
	
	public function isNotValidURL($subject) {
		if ($subject === null) {
			return false;
		}
		return !$this->isValidURL($subject);
	}
	
	public function urlHostEquals($subject) {
		if ($subject === null) {
			return false;
		}
		$host = wfWAFUtils::parse_url((string) $subject, PHP_URL_HOST);
		if (!is_string($host)) {
			return wfWAFUtils::strlen($this->getExpected()) == 0;
		}
		
		return strtolower($host) == strtolower($this->getExpected());
	}
	
	public function urlHostNotEquals($subject) {
		if ($subject === null) {
			return false;
		}
		return !$this->urlHostEquals($subject);
	}
	
	public function urlHostMatches($subject) {
		if ($subject === null) {
			return false;
		}
		$host = wfWAFUtils::parse_url((string) $subject, PHP_URL_HOST);
		if (!is_string($host)) {
			return false;
		}
		
		return preg_match((string) $this->getExpected(), $host, $this->matches) > 0;
	}
	
	public function urlHostNotMatches($subject) {
		if ($subject === null) {
			return false;
		}
		return !$this->urlHostMatches($subject);
	}
	
	public function urlSchemeEquals($subject) {
		if ($subject === null) {
			return false;
		}
		$scheme = wfWAFUtils::parse_url((string) $subject, PHP_URL_SCHEME);
		if (!is_string($scheme)) {
			return wfWAFUtils::strlen($this->getExpected()) == 0;
		}
		
		return strtolower($scheme) == strtolower($this->getExpected());
	}
	
	public function urlSchemeNotEquals($subject) {
		if ($subject === null) {
			return false;
		}
		return !$this->urlSchemeEquals($subject);
	}
	
	public function urlSchemeMatches($subject) {
		if ($subject === null) {
			return false;
		}
		$scheme = wfWAFUtils::parse_url((string) $subject, PHP_URL_SCHEME);
		if (!is_string($scheme)) {
			return false;
		}
		
		return preg_match((string) $this->getExpected(), $scheme, $this->matches) > 0;
	}
	
	public function urlSchemeNotMatches($subject) {
		if ($subject === null) {
			return false;
		}
		return !$this->urlSchemeMatches($subject);
	}

	public function versionEquals($subject) {
		if ($subject === null) {
			return false;
		}
		return version_compare($subject, $this->getExpected(), '==');
	}

	public function versionNotEquals($subject) {
		if ($subject === null) {
			return false;
		}
		return version_compare($subject, $this->getExpected(), '!=');
	}

	public function versionGreaterThan($subject) {
		if ($subject === null) {
			return false;
		}
		return version_compare($subject, $this->getExpected(), '>');
	}

	public function versionGreaterThanEqualTo($subject) {
		if ($subject === null) {
			return false;
		}
		return version_compare($subject, $this->getExpected(), '>=');
	}

	public function versionLessThan($subject) {
		if ($subject === null) {
			return false;
		}
		return version_compare($subject, $this->getExpected(), '<');
	}

	public function versionLessThanEqualTo($subject) {
		if ($subject === null) {
			return false;
		}
		return version_compare($subject, $this->getExpected(), '<=');
	}

	public function keyExists($subject) {
		if (!is_array($subject)) {
			return false;
		}
		return array_key_exists($this->getExpected(), $subject);
	}

	public function keyMatches($subject) {
		if (!is_array($subject)) {
			return false;
		}
		foreach($subject as $key=>$value) {
			if (preg_match($this->getExpected(), $key))
				return true;
		}
		return false;
	}

	public function exists($subject) {
		return isset($subject);
	}

	/**
	 * @return mixed
	 */
	public function getAction() {
		return $this->action;
	}

	/**
	 * @param mixed $action
	 */
	public function setAction($action) {
		$this->action = $action;
	}

	/**
	 * @return mixed
	 */
	public function getExpected() {
		return $this->expected;
	}

	/**
	 * @param mixed $expected
	 */
	public function setExpected($expected) {
		$this->expected = $expected;
	}

	/**
	 * @return mixed
	 */
	public function getSubjects() {
		return $this->subjects;
	}

	/**
	 * @param mixed $subjects
	 * @return $this
	 */
	public function setSubjects($subjects) {
		$this->subjects = $subjects;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getMatches() {
		return $this->matches;
	}

	/**
	 * @return mixed
	 */
	public function getFailedSubjects() {
		return (array)$this->failedSubjects;
	}

	/**
	 * @return mixed
	 */
	public function getResult() {
		return $this->result;
	}

	/**
	 * @return mixed
	 */
	public function getMultiplier() {
		return $this->multiplier;
	}

	/**
	 * @return wfWAF
	 */
	public function getWAF() {
		return $this->waf;
	}

	/**
	 * @param wfWAF $waf
	 */
	public function setWAF($waf) {
		$this->waf = $waf;
		if (is_array($this->subjects)) {
			foreach ($this->subjects as $subject) {
				if (is_object($subject) && method_exists($subject, 'setWAF')) {
					$subject->setWAF($waf);
				}
			}
		}
		if (is_object($this->expected) && method_exists($this->expected, 'setWAF')) {
			$this->expected->setWAF($waf);
		}
	}

	/**
	 * @return wfWAFRule
	 */
	public function getRule() {
		return $this->rule;
	}

	/**
	 * @param wfWAFRule $rule
	 */
	public function setRule($rule) {
		$this->rule = $rule;
	}
}

class wfWAFRuleComparisonGroup implements wfWAFRuleInterface {

	private $items = array();
	private $failedComparisons = array();
	private $result = false;
	private $waf;

	/**
	 * @var wfWAFRule
	 */
	private $rule;

	public function __construct() {
		$args = func_get_args();
		foreach ($args as $arg) {
			$this->add($arg);
		}
	}

	public function __sleep() {
		return array(
			'items',
		);
	}

	public function add($item) {
		$this->items[] = $item;
	}

	public function remove($item) {
		$key = array_search($item, $this->items);
		if ($key !== false) {
			unset($this->items[$key]);
		}
	}

	/**
	 *
	 * @throws wfWAFRuleException
	 */
	public function evaluate() {
		if (count($this->items) % 2 != 1) {
			throw new wfWAFRuleException('Invalid number of rules and logical operators.  Should be odd number of rules and logical operators.');
		}

		$this->result = false;
		$operator = null;
		/** @var wfWAFRuleComparison|wfWAFRuleLogicalOperator|wfWAFRuleComparisonGroup $comparison */
		for ($i = 0; $i < count($this->items); $i++) {
			$comparison = $this->items[$i];
			if ($i % 2 == 1 && !($comparison instanceof wfWAFRuleLogicalOperator)) {
				throw new wfWAFRuleException('Invalid WAF rule format, expected wfWAFRuleLogicalOperator, got ' . get_class($comparison));
			}
			if ($i % 2 == 0 && !($comparison instanceof wfWAFRuleComparison || $comparison instanceof wfWAFRuleComparisonGroup)) {
				throw new wfWAFRuleException('Invalid WAF rule format, expected wfWAFRuleComparison or wfWAFRuleComparisonGroup, got ' . get_class($comparison));
			}

			if ($comparison instanceof wfWAFRuleLogicalOperator) {
				$operator = $comparison;
				continue;
			}
			if ($comparison instanceof wfWAFRuleComparison || $comparison instanceof wfWAFRuleComparisonGroup) {
				$comparison->setRule($this->getRule());
				if ($operator instanceof wfWAFRuleLogicalOperator) {
					$operator->setCurrentValue($this->result);
					$operator->setComparison($comparison);
					$this->result = $operator->evaluate();
				} else {
					$this->result = $comparison->evaluate();
				}
			}
			if ($comparison instanceof wfWAFRuleComparison && $comparison->getResult()) {
				if ($comparison->hasSubject()) {
					foreach ($comparison->getFailedSubjects() as $failedSubject) {
						$this->failedComparisons[] = new wfWAFRuleComparisonFailure(
							$failedSubject['subject'], $failedSubject['value'], $comparison->getExpected(),
							$comparison->getAction(), $failedSubject['multiplier'], $failedSubject['matches']
						);
					}
				}
				else {
					$this->failedComparisons[] = new wfWAFRuleComparisonFailure(
						'', '', $comparison->getExpected(),
						$comparison->getAction(), 1, array()
					);
				}
			}
			if ($comparison instanceof wfWAFRuleComparisonGroup && $comparison->getResult()) {
				foreach ($comparison->getFailedComparisons() as $comparisonFail) {
					$this->failedComparisons[] = $comparisonFail;
				}
			}
		}
		return $this->result;
	}

	/**
	 * @return string
	 * @throws wfWAFRuleException
	 */
	public function render() {
		if (count($this->items) % 2 != 1) {
			throw new wfWAFRuleException('Invalid number of rules and logical operators.  Should be odd number of rules and logical operators.');
		}

		$return = array();
		/**
		 * @var wfWAFRuleInterface $item
		 */
		for ($i = 0; $i < count($this->items); $i++) {
			$item = $this->items[$i];
			if ($i % 2 == 1 && !($item instanceof wfWAFRuleLogicalOperator)) {
				throw new wfWAFRuleException('Invalid WAF rule format, expected wfWAFRuleLogicalOperator, got ' . get_class($item));
			}
			if ($i % 2 == 0 && !($item instanceof wfWAFRuleComparison || $item instanceof wfWAFRuleComparisonGroup)) {
				throw new wfWAFRuleException('Invalid WAF rule format, expected wfWAFRule or wfWAFRuleComparisonGroup, got ' . get_class($item));
			}
			$return[] = $item->render();
		}
		return sprintf('new %s(%s)', get_class($this), join(', ', $return));
	}

	/**
	 * @return string
	 * @throws wfWAFRuleException
	 */
	public function renderRule() {
		if (count($this->items) % 2 != 1) {
			throw new wfWAFRuleException('Invalid number of rules and logical operators.  Should be odd number of rules and logical operators.');
		}

		$return = array();
		/**
		 * @var wfWAFRuleInterface $item
		 */
		for ($i = 0; $i < count($this->items); $i++) {
			$item = $this->items[$i];
			if ($i % 2 == 1 && !($item instanceof wfWAFRuleLogicalOperator)) {
				throw new wfWAFRuleException('Invalid WAF rule format, expected wfWAFRuleLogicalOperator, got ' . get_class($item));
			}
			if ($i % 2 == 0 && !($item instanceof wfWAFRuleComparison || $item instanceof wfWAFRuleComparisonGroup)) {
				throw new wfWAFRuleException('Invalid WAF rule format, expected wfWAFRule or wfWAFRuleComparisonGroup, got ' . get_class($item));
			}
			$return[] = $item->renderRule();
		}
		return sprintf('(%s)', join(' ', $return));
	}

	public function debug() {
		$debug = '';
		/** @var wfWAFRuleComparisonFailure $failedComparison */
		foreach ($this->getFailedComparisons() as $failedComparison) {
			$debug .= $failedComparison->getParamKey() . ' ' . $failedComparison->getAction() . ' ' . $failedComparison->getExpected() . "\n";
		}
		return $debug;
	}

	/**
	 * @return array
	 */
	public function getItems() {
		return $this->items;
	}

	/**
	 * @param array $items
	 */
	public function setItems($items) {
		$this->items = $items;
	}

	/**
	 * @return mixed
	 */
	public function getFailedComparisons() {
		return $this->failedComparisons;
	}

	/**
	 * @return boolean
	 */
	public function getResult() {
		return $this->result;
	}

	/**
	 * @return wfWAFRule
	 */
	public function getRule() {
		return $this->rule;
	}

	/**
	 * @param wfWAFRule $rule
	 */
	public function setRule($rule) {
		$this->rule = $rule;
	}

	/**
	 * @return mixed
	 */
	public function getWAF() {
		return $this->waf;
	}

	/**
	 * @param mixed $waf
	 */
	public function setWAF($waf) {
		$this->waf = $waf;
		foreach ($this->items as $item) {
			if (is_object($item) && method_exists($item, 'setWAF')) {
				$item->setWAF($waf);
			}
		}
	}
}

class wfWAFRuleComparisonFailure {

	private $paramKey;
	private $expected;
	private $action;
	/**
	 * @var null|int
	 */
	private $multiplier;
	/**
	 * @var string
	 */
	private $paramValue;
	/**
	 * @var mixed
	 */
	private $matches;

	/**
	 * @param string $paramKey
	 * @param string $paramValue
	 * @param string $expected
	 * @param string $action
	 * @param mixed $multiplier
	 * @param mixed $matches
	 */
	public function __construct($paramKey, $paramValue, $expected, $action, $multiplier = null, $matches = null) {
		$this->setParamKey($paramKey);
		$this->setExpected($expected);
		$this->setAction($action);
		$this->setMultiplier($multiplier);
		$this->setParamValue($paramValue);
		$this->setMatches($matches);
	}

	public function __sleep() {
		return array(
			'paramKey',
			'expected',
			'action',
			'multiplier',
			'paramValue',
			'matches',
		);
	}

	/**
	 * @return mixed
	 */
	public function getParamKey() {
		return $this->paramKey;
	}

	/**
	 * @param mixed $paramKey
	 */
	public function setParamKey($paramKey) {
		$this->paramKey = $paramKey;
	}

	/**
	 * @return mixed
	 */
	public function getExpected() {
		return $this->expected;
	}

	/**
	 * @param mixed $expected
	 */
	public function setExpected($expected) {
		$this->expected = $expected;
	}

	/**
	 * @return mixed
	 */
	public function getAction() {
		return $this->action;
	}

	/**
	 * @param mixed $action
	 */
	public function setAction($action) {
		$this->action = $action;
	}

	/**
	 * @return int|null
	 */
	public function getMultiplier() {
		return $this->multiplier;
	}

	/**
	 * @param int|null $multiplier
	 */
	public function setMultiplier($multiplier) {
		$this->multiplier = $multiplier;
	}

	/**
	 * @return bool
	 */
	public function hasMultiplier() {
		return $this->getMultiplier() > 1;
	}

	/**
	 * @return string
	 */
	public function getParamValue() {
		return $this->paramValue;
	}

	/**
	 * @param string $paramValue
	 */
	public function setParamValue($paramValue) {
		$this->paramValue = $paramValue;
	}

	/**
	 * @return mixed
	 */
	public function getMatches() {
		return $this->matches;
	}

	/**
	 * @param mixed $matches
	 */
	public function setMatches($matches) {
		$this->matches = $matches;
	}
}

class wfWAFRuleComparisonSubject {

	/**
	 * @var array
	 */
	private $subject;
	/**
	 * @var array
	 */
	private $filters;

	/** @var wfWAF */
	private $waf;

	public static function create($waf, $subject, $filters) {
		return new self($waf, $subject, $filters);
	}

	/**
	 * wfWAFRuleComparisonSubject constructor.
	 * @param wfWAF $waf
	 * @param array $subject
	 * @param array $filters
	 */
	public function __construct($waf, $subject, $filters) {
		$this->waf = $waf;
		$this->subject = $subject;
		$this->filters = $filters;
	}

	public function __sleep() {
		return array(
			'subject',
			'filters',
		);
	}

	private function getRootValue($subject) {
		if ($subject instanceof wfWAFRuleComparisonSubject) {
			return $subject->getValue();
		}
		else {
			return $this->getWAF()->getGlobal($subject);
		}
	}

	/**
	 * @return mixed|null
	 */
	public function getValue() {
		$subject = $this->getSubject();
		if (!is_array($subject)) {
			return $this->runFilters($this->getRootValue($subject), $subject);
		}
		else if (count($subject) > 0) {
			$globalKey = array_shift($subject);
			return $this->runFilters($this->_getValue($subject, $this->getRootValue($globalKey)));
		}
		return null;
	}

	/**
	 * @param array $subjectKey
	 * @param array $global
	 * @return null
	 */
	private function _getValue($subjectKey, $global) {
		if (!is_array($global) || !is_array($subjectKey)) {
			return null;
		}

		$key = array_shift($subjectKey);
		if (array_key_exists($key, $global)) {
			if (count($subjectKey) > 0) {
				return $this->_getValue($subjectKey, $global[$key]);
			} else {
				return $global[$key];
			}
		}
		return null;
	}


	/**
	 * @return string
	 */
	public function getKey() {
		return wfWAFRuleComparison::getSubjectKey($this->getSubject());
	}

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	private function runFilters($value) {
		$filters = $this->getFilters();
		if (is_array($filters)) {
			foreach ($filters as $filterArgs) {
				if (method_exists($this, 'filter' . $filterArgs[0])) {
					$value = call_user_func_array(array($this, 'filter' . $filterArgs[0]), array_merge(array($value), array_slice($filterArgs, 1)));
				}
			}
		}
		return $value;
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	public function filterBase64decode($value) {
		if (is_string($value)) {
			return base64_decode($value);
		}
		return $value;
	}

	public function filterReplace($value, $find, $replace) {
		return str_replace($find, $replace, $value);
	}

	public function filterPregReplace($value, $pattern, $replacement, $limit=-1) {
		return preg_replace($pattern, $replacement, $value, $limit);
	}

	private function getMatchingKeys($array, $patterns) {
		if (!is_array($array))
			return array();
		$filtered = array();
		$pattern = array_shift($patterns);
		foreach ($array as $key=>$value) {
			if (preg_match($pattern, $key)) {
				if (empty($patterns)) {
					$filtered[] = $value;
				}
				else {
					$filtered = array_merge($filtered, $this->getMatchingKeys($value, $patterns));
				}
			}
		}
		return $filtered;
	}

	public function filterFilterKeys($values) {
		$patterns = array_slice(func_get_args(), 1);
		return $this->getMatchingKeys($values, $patterns);
	}

	public function filterJson($value) {
		return wfWAFUtils::json_decode(@(string)$value, true);
	}

	private function renderSubject() {
		$subjects = $this->getSubject();
		if (is_array($subjects)) {
			$rendered = array();
			foreach ($subjects as $subject) {
				if ($subject instanceof wfWAFRuleComparisonSubject) {
					array_push($rendered, $subject->render());
				}
				else {
					array_push($rendered, var_export($subject, true));
				}
			}
			return sprintf('array(%s)', implode(', ', $rendered));
		}
		else {
			return var_export($subjects, true);
		}
	}

	/**
	 * @return string
	 */
	public function render() {
		return sprintf('%s::create($this, %s, %s)', get_class($this), $this->renderSubject(),
			var_export($this->getFilters(), true));
	}

	/**
	 * @return string
	 */
	public function renderRule() {
		$subjects = $this->getSubject();
		if (is_array($subjects)) {
			if (strpos($subjects[0], '.') !== false) {
				list($superGlobal, $global) = explode('.', $subjects[0], 2);
				unset($subjects[0]);
				$subjects = array_merge(array($superGlobal, $global), $subjects);
			}
			$rule = '';
			foreach ($subjects as $subject) {
				if (preg_match("/^[a-zA-Z_][a-zA-Z0-9_]*$/", $subject)) {
					$rule .= "$subject.";
				} else {
					$rule = rtrim($rule, '.');
					$rule .= sprintf("['%s']", str_replace("'", "\\'", $subject));
				}
			}
			$rule = rtrim($rule, '.');
		} else {
			$rule = $this->getSubject();
		}

		foreach ($this->getFilters() as $filter) {
			$rule = $filter[0] . '(' . implode(',', array_merge(array($rule), array_slice($filter, 1))) . ')';
		}
		return $rule;
	}

	/**
	 * @return array
	 */
	public function getSubject() {
		return $this->subject;
	}

	/**
	 * @param array $subject
	 */
	public function setSubject($subject) {
		$this->subject = $subject;
	}

	/**
	 * @return array
	 */
	public function getFilters() {
		return $this->filters;
	}

	/**
	 * @param array $filters
	 */
	public function setFilters($filters) {
		$this->filters = $filters;
	}

	/**
	 * @return wfWAF
	 */
	public function getWAF() {
		return $this->waf;
	}

	private static function setWafForSubject($subject, $waf) {
		if (is_array($subject)) {
			foreach ($subject as $child) {
				self::setWafForSubject($child, $waf);
			}
		}
		else if ($subject instanceof wfWAFRuleComparisonSubject) {
			$subject->setWAF($waf);
		}
	}

	/**
	 * @param wfWAF $waf
	 */
	public function setWAF($waf) {
		$this->waf = $waf;
		self::setWafForSubject($this->subject, $waf);
	}
}
}