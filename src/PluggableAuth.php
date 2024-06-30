<?php

namespace MediaWiki\Extension\AuthRemoteUser;

use MediaWiki\Auth\AuthManager;
use MediaWiki\Extension\PluggableAuth\PluggableAuth as PA_Base;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserIdentity;
use MWException;
use RequestContext;
use SpecialPage;
use Title;

class PluggableAuth extends PA_Base {

	/**
	 * @var AuthManager
	 */
	private $authManager;

	/**
	 * @var UserFactory
	 */
	private $userFactory;


	/**
	 * @param UserFactory $userFactory
	 * @param AuthManager $authManager
	 */
	public function __construct( UserFactory $userFactory, AuthManager $authManager ) {
		$this->userFactory = $userFactory;
		$this->authManager = $authManager;
		$this->setLogger( LoggerFactory::getInstance( 'AuthRemoteUser' ) );
		$this->getLogger()->debug( 'Constructed ' . self::class );
	}

	/**
	 * @inheritDoc
	 * @throws MWException
	 */
	public function authenticate( ?int &$id, ?string &$username, ?string &$realname, ?string &$email, ?string &$errorMessage ): bool {
		$this->getLogger()->debug( 'Entering authenticate' );

		$currentTitle = RequestContext::getMain()->getTitle();
		$titleOfMySpecialPage = SpecialPage::getTitleFor( SpecialAuthRemoteUser::NAME_OF_SPECIAL_PAGE );
		if ( $currentTitle->getPrefixedText() != $titleOfMySpecialPage->getPrefixedText() ) {
			$this->redirectToSpecialPage( $currentTitle, $titleOfMySpecialPage );
		}
		// from this point on assume, that we are on our special page.
		$principal = $_SERVER['REMOTE_USER'] ?? $_SERVER['PHP_AUTH_USER'] ?? $_SERVER['REDIRECT_REMOTE_USER'] ?? null;
		$validDomain = $this->getData()->has( 'domain' ) ? $this->getData()->get( 'domain' ) : null;
		$this->getLogger()->debug( 'Received principal: ' . $principal );
		$this->getLogger()->debug( 'Configured valid domain is: ' . $validDomain );

		if ( $principal !== null ) {
        	list( $providedUsername, $providedDomain ) = $this->processPrincipal( $principal );
			$this->getLogger()->debug( 'Working with username "' . $providedUsername . '" and domain "' . $providedDomain . '"' );

			if ( $validDomain && ($validDomain != $providedDomain) ) {
				return false;
			}
			$username = $providedUsername;
			$user = $this->userFactory->newFromName( $username );
			if ( $user !== false && $user->getId() !== 0 ) {
				$id = $user->getId();
			}
		}
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function deauthenticate( UserIdentity &$user ): void {
		// Nothing to do, really
		$user = null;
	}

	/**
	 * @inheritDoc
	 */
	public function saveExtraAttributes( int $id ): void {
		// Nothing to do, really
	}

	/**
	 * Processes the given principal to extract the username and domain.
	 *
	 * @param string $principal The principal to process.
	 *
	 * @return array An array containing the username and domain extracted from the principal.
	 */
	protected function processPrincipal( string $principal ) {
		$myConfig = MediaWikiServices::getInstance()->getMainConfig();

		$parts = explode( '@', $principal );
		$username = reset($parts );
		$domain = $parts[1] ?? null;

		$normalizer = $myConfig->has( 'AuthRemoteUserUsernameNormalizer' )
				&& is_callable( $myConfig->get( 'AuthRemoteUserUsernameNormalizer' ) )
			? $myConfig->get( 'AuthRemoteUserUsernameNormalizer' )
			: 'strtolower';

		$username = $normalizer( $username );

		return [ $username, $domain ];
	}


	/**
	 * This redirects to REMOTE_USER authentication special page
	 *
	 * @param Title $currentTitle
	 * @param Title $destinationTitle
	 *
	 * @throws MWException
	 * @return void
	 */
	protected function redirectToSpecialPage( Title $currentTitle, Title $destinationTitle ): void {
		$url = $destinationTitle->getFullURL( [
			'returnto' => $currentTitle,
			'returntoquery' => $this->authManager->getRequest()->getRawPostString(),	// see PluggableAuthLogin line 149 for a different idea
		] );
		if ( $url ) {
			$this->getLogger()->debug( 'Redirecting to ' . $url );
			header( 'Location: ' . $url );
		} else {
			throw new MWException( "Could not determine URL for " . $destinationTitle->getPrefixedText() );
		}
		exit;
	}
}