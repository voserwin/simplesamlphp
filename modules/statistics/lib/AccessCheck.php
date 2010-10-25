<?php

/**
 * Class implementing the access checker function for the statistics module.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_statistics_AccessCheck {


	/**
	 * Check that the user has access to the statistics.
	 *
	 * If the user doesn't have access, send the user to the login page.
	 */
	public static function checkAccess(SimpleSAML_Configuration $statconfig) {
		$session = SimpleSAML_Session::getInstance();
		$protected = $statconfig->getBoolean('protected', FALSE);
		$authsource = $statconfig->getString('auth', NULL);
		$allowedusers = $statconfig->getValue('allowedUsers', NULL);
		$useridattr = $statconfig->getString('useridattr', 'eduPersonPrincipalName');

		$acl = $statconfig->getValue('acl', NULL);
		if ($acl !== NULL && !is_string($acl) && !is_array($acl)) {
			throw new SimpleSAML_Error_Exception('Invalid value for \'acl\'-option. Should be an array or a string.');
		}

		if ($protected) {

			if (SimpleSAML_Utilities::isAdmin()) {
				// User logged in as admin. OK.
				SimpleSAML_Logger::debug('Statistics auth - logged in as admin, access granted');

			} elseif(isset($authsource) && $session->isValid($authsource) ) {

				// User logged in with auth source.
				SimpleSAML_Logger::debug('Statistics auth - valid login with auth source [' . $authsource . ']');

				// Retrieving attributes
				$attributes = $session->getAttributes();

				$allow = FALSE;
				if (!empty($allowedusers)) {
					// Check if userid exists
					if (!isset($attributes[$useridattr][0]))
						throw new Exception('User ID is missing');

					// Check if userid is allowed access..
					if (!in_array($attributes[$useridattr][0], $allowedusers)) {
						SimpleSAML_Logger::debug('Statistics auth - User denied access by user ID [' . $attributes[$useridattr][0] . ']');
					} else {
						SimpleSAML_Logger::debug('Statistics auth - User granted access by user ID [' . $attributes[$useridattr][0] . ']');
						$allow = TRUE;
					}
				} else {
					SimpleSAML_Logger::debug('Statistics auth - no allowedUsers list.');
				}

				if (!$allow && !is_null($acl)) {
					$acl = new sspmod_core_ACL($acl);
					if (!$acl->allows($attributes)) {
						SimpleSAML_Logger::debug('Statistics auth - denied access by ACL.');
					} else {
						SimpleSAML_Logger::debug('Statistics auth - allowed access by ACL.');
						$allow = TRUE;
					}
				} else {
					SimpleSAML_Logger::debug('Statistics auth - no ACL configured.');
				}

				if (!$allow) {
					throw new SimpleSAML_Error_Exception('Access denied to the current user.');
				}

			} elseif(isset($authsource)) {
				// If user is not logged in init login with authrouce if authsousrce is defined.
				SimpleSAML_Auth_Default::initLogin($authsource, SimpleSAML_Utilities::selfURL());

			} else {
				// If authsource is not defined, init admin login.
				SimpleSAML_Utilities::requireAdmin();
			}
		}
	}

}