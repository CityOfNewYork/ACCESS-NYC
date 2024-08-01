<?php

namespace WordfenceLS;

class Model_Notice {
	const SEVERITY_CRITICAL = 'critical';
	const SEVERITY_WARNING = 'warning';
	const SEVERITY_INFO = 'info';
	
	private $_id;
	private $_severity;
	private $_messageHTML;
	private $_category;
	
	public function __construct($id, $severity, $messageHTML, $category) {
		$this->_id = $id;
		$this->_severity = $severity;
		$this->_messageHTML = $messageHTML;
		$this->_category = $category;
	}
	
	public function display_notice() {
		$severityClass = 'notice-info';
		if ($this->_severity == self::SEVERITY_CRITICAL) {
			$severityClass = 'notice-error';
		}
		else if ($this->_severity == self::SEVERITY_WARNING) {
			$severityClass = 'notice-warning';
		}
		
		echo '<div class="wfls-notice notice ' . $severityClass . '" data-notice-id="' . esc_attr($this->_id) . '" data-notice-type="' . esc_attr($this->_category) . '"><p>' . $this->_messageHTML . '</p><p>' . sprintf(__('<a class="wfls-btn wfls-btn-default wfls-btn-sm wfls-dismiss-link" href="#" onclick="GWFLS.dismiss_notice(\'%s\'); return false;">Dismiss</a>', 'wordfence-login-security'), esc_attr($this->_id)) . '</p></div>';
	}
}