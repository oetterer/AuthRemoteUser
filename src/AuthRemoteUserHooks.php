<?php

namespace MediaWiki\Extension\AuthRemoteUser;

/**
 * Class AuthRemoteUserHooks
 *
 * This class contains hooks for handling user registration with the AuthRemoteUser plugin.
 */
class AuthRemoteUserHooks {

	/**
	 * Adds the AuthRemoteUser plugin configuration to PluggableAuth's
	 * global $wgPluggableAuth_Config array.
	 *
	 * @param array $info Information about the plugin.
	 *                    - name: The name of the plugin.
	 *
	 * @return void
	 */
	public static function onRegistration( array $info ) {

		if ( !isset( $GLOBALS['wgPluggableAuth_Config'] ) ) {
			$GLOBALS['wgPluggableAuth_Config'] = [];
		}
		$GLOBALS['wgPluggableAuth_Config'][$info['name']] = [
			'plugin' => 'AuthRemoteUser',
			'buttonLabelMessage' => 'auth-remote-user-login-button-label',
		];
		if ( !isset( $GLOBALS['wgAuthRemoteUserDomain'] ) ) {
			return;
		}
		$domain = trim( $GLOBALS['wgAuthRemoteUserDomain'] );
		if ( strlen( $domain ) ) {
			$GLOBALS['wgPluggableAuth_Config']['AuthRemoteUser']['data'] = [
				'domain' => $domain
			];
		}
	}
}