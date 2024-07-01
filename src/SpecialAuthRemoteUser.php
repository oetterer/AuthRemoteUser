<?php

namespace MediaWiki\Extension\AuthRemoteUser;

use MediaWiki\Auth\AuthManager;
use MediaWiki\Extension\PluggableAuth\Group\GroupProcessorRunner;
use MediaWiki\Extension\PluggableAuth\HookRunner;
use MediaWiki\Extension\PluggableAuth\PluggableAuthFactory;
use MediaWiki\Extension\PluggableAuth\PluggableAuthLogin;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\User\UserIdentityValue;
use Message;
use Psr\Log\LoggerInterface;
use UnlistedSpecialPage;
use Wikimedia\Timestamp\ConvertibleTimestamp;

/**
 * This is a verbatim copy of \MediaWiki\Extension\PluggableAuth\PluggableAuthLogin
 * except for its name and logger id.
 *
 * It evokes AuthRemoteUser's authenticate() function.
 */
class SpecialAuthRemoteUser extends UnlistedSpecialPage {

	/**
	 * @var string
	 */
	const NAME_OF_SPECIAL_PAGE = 'AuthRemoteUser';

	/**
	 * @var PluggableAuthFactory
	 */
	private $pluggableAuthFactory;

	/**
	 * @var AuthManager
	 */
	private $authManager;

	/**
	 * @var GroupProcessorRunner
	 */
	private $groupProcessorRunner;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var HookRunner
	 */
	private $hookRunner;


	public function __construct(
		PluggableAuthFactory $pluggableAuthFactory, AuthManager $authManager, GroupProcessorRunner $groupProcessorRunner
	) {
		parent::__construct( self::NAME_OF_SPECIAL_PAGE );
		$this->pluggableAuthFactory = $pluggableAuthFactory;
		$this->authManager = $authManager;
		$this->groupProcessorRunner = $groupProcessorRunner;
		$this->logger = LoggerFactory::getInstance( 'AuthRemoteUser' );
		$this->getLogger()->debug( 'Constructed Special Page AuthRemoteUser' );
	}

	/**
	 * Will be called automatically by `MediaWiki\SpecialPage\SpecialPageFactory::getPage`
	 * @inheritDoc
	 */
	public function setHookContainer( HookContainer $hookContainer ) {
		parent::setHookContainer( $hookContainer );
		$this->hookRunner = new HookRunner( $hookContainer );
	}

	/**
	 * @param string|null $subPage *
	 *
	 * @return void
	 */
	public function execute( $subPage) {
		$this->logger->debug( 'In execute()' );
		$user = $this->getUser();
		$pluggableauth = $this->pluggableAuthFactory->getInstance();
		$error = null;
		if ( $pluggableauth ) {
			if ( $pluggableauth->authenticate( $id, $username, $realname, $email, $error ) ) {
				if ( !$id ) {
					$user->loadDefaults( $username );
					if ( $realname !== null ) {
						$user->setRealName( $realname );
					}
					$user->mName = $username;
					$user->mEmail = $email;
					$now = ConvertibleTimestamp::now( TS_UNIX );
					$user->mEmailAuthenticated = $now;
					$user->mTouched = $now;
					$this->logger->debug( 'Authenticated new user: ' . $username );
					// Group sync is done in `LocalUserCreated` hook
				} else {
					$user->mId = $id;
					$user->loadFromId();
					$this->logger->debug( 'Authenticated existing user: ' . $user->mName );
					$userIdentity = new UserIdentityValue( $user->getId(), $user->getName() );
					$this->groupProcessorRunner->run( $userIdentity, $pluggableauth );
				}
				$authorized = true;
				$this->hookRunner->onPluggableAuthUserAuthorization( $user, $authorized );
				if ( $authorized ) {
					$this->authManager->setAuthenticationSessionData( PluggableAuthLogin::USERNAME_SESSION_KEY, $username );
					$this->authManager->setAuthenticationSessionData( PluggableAuthLogin::REALNAME_SESSION_KEY, $realname );
					$this->authManager->setAuthenticationSessionData( PluggableAuthLogin::EMAIL_SESSION_KEY, $email );
					$this->logger->debug( 'User is authorized.' );
				} else {
					$this->logger->debug( 'Authorization failure.' );
					$error = ( new Message( 'pluggableauth-not-authorized', [ $username ] ) )->parse();
				}
			} else {
				$this->logger->debug( 'Authentication failure.' );
				if ( $error === null ) {
					$error = ( new Message( 'pluggableauth-authentication-failure' ) )->text();
				} else {
					if ( !is_string( $error ) ) {
						$error = strval( $error );
					}
					$this->logger->debug( 'ERROR: ' . $error );
				}
			}
		} else {
			$error = ( new Message( 'pluggableauth-authentication-plugin-failure' ) )->text();
		}
		if ( $error !== null ) {
			$this->authManager->setAuthenticationSessionData( PluggableAuthLogin::ERROR_SESSION_KEY, $error );
		}
		$returnToUrl = $this->authManager->getRequest()->getSessionData( PluggableAuthLogin::RETURNTOURL_SESSION_KEY );
		if ( $returnToUrl === null || strlen( $returnToUrl ) === 0 ) {
			// This should never happen unless there is an issue in the authentication plugin, most
			// likely resulting in session corruption. Since it is unclear if it is safe to continue,
			// an error message is shown to the user and the authentication flow is terminated.
			$this->logger->debug( 'ERROR: return to URL is null or empty' );
			$this->getOutput()->wrapWikiMsg( "<div class='error'>\n$1\n</div>", 'pluggableauth-fatal-error' );
		} else {
			$this->getOutput()->redirect( $returnToUrl );
		}
	}

	/**
	 * Override the parent to set where the special page appears on Special:SpecialPages
	 * 'other' is the default. If that's what you want, you do not need to override.
	 *
	 * @return string
	 */
	protected function getGroupName() {
		return 'login';
	}

	/**
	 * @return LoggerInterface
	 */
	protected function getLogger(): LoggerInterface {
		if ( !empty( $this->logger ) ) {
			return $this->logger;
		}
		return $this->logger = LoggerFactory::getInstance( 'AuthRemoteUser' );
	}
}