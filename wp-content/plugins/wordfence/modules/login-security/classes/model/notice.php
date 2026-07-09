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
	private $_buttons;
	
	public function __construct($id, $severity, $messageHTML, $category, $buttons = array()) {
		$this->_id = $id;
		$this->_severity = $severity;
		$this->_messageHTML = $messageHTML;
		$this->_category = $category;
		$this->_buttons = $buttons;
	}
	
	public function display_notice() {
		$severityClass = 'notice-info';
		if ($this->_severity == self::SEVERITY_CRITICAL) {
			$severityClass = 'notice-error';
		}
		else if ($this->_severity == self::SEVERITY_WARNING) {
			$severityClass = 'notice-warning';
		}
		
		if (!preg_match('/^<p>/', $this->_messageHTML)) {
			$this->_messageHTML = '<p>' . $this->_messageHTML . '</p>';
		}
		
		echo '<div class="wfls-notice notice ' . $severityClass . '" data-notice-id="' . esc_attr($this->_id) . '" data-notice-type="' . esc_attr($this->_category) . '">' .
				$this->_messageHTML .
				'<p>' .
					implode('', array_map(function($b) { return sprintf('<a class="wfls-btn wfls-btn-default wfls-btn-sm" href="%1$s">%2$s</a>&nbsp;', esc_url($b['href']), esc_html($b['label'])); }, $this->_buttons)) .
					sprintf('<a class="wfls-btn wfls-btn-default wfls-btn-sm wfls-dismiss-link" href="#" onclick="GWFLS.dismiss_notice(\'%s\'); return false;">' . __('Dismiss', 'wordfence') . '</a>', esc_attr($this->_id)) .
				'</p>' .
			'</div>';
	}
}