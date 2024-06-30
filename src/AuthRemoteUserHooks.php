<?php

namespace MediaWiki\Extension\AuthRemoteUser;

class AuthRemoteUserHooks {
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