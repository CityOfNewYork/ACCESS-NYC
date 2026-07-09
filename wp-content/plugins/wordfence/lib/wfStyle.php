<?php
class wfStyle {
	/**
	 * Returns the classes for the main content body of the page, adjusting for the paid status.
	 * 
	 * @return string
	 */
	public static function contentClasses() {
		if (wfConfig::get('isPaid')) {
			return 'wf-col-xs-12';
		}
		return 'wf-col-xs-12';
	}
	
	/**
	 * Returns the class for an audit log event based on its type.
	 * 
	 * @param string $type One of the wfAuditLog::AUDIT_LOG_CATEGORY_* constants
	 * @return string
	 */
	public static function auditEventTypeClass($type) {
		switch ($type) {
			case wfAuditLog::AUDIT_LOG_CATEGORY_AUTHENTICATION:
				return 'wf-audit-type-authentication';
			case wfAuditLog::AUDIT_LOG_CATEGORY_USER_PERMISSIONS:
				return 'wf-audit-type-user-permissions';
			case wfAuditLog::AUDIT_LOG_CATEGORY_PLUGINS_THEMES_UPDATES:
				return 'wf-audit-type-plugins-themes-updates';
			case wfAuditLog::AUDIT_LOG_CATEGORY_SITE_SETTINGS:
				return 'wf-audit-type-site-settings';
			case wfAuditLog::AUDIT_LOG_CATEGORY_MULTISITE:
				return 'wf-audit-type-multisite';
			case wfAuditLog::AUDIT_LOG_CATEGORY_CONTENT:
				return 'wf-audit-type-content';
			case wfAuditLog::AUDIT_LOG_CATEGORY_FIREWALL:
				return 'wf-audit-type-firewall';
		}
		return 'wf-audit-type-unknown';
	}
}