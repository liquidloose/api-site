<?php

namespace WPOAuth2\GrantType;

use WPOAuth2\ClientAssertionType\HttpBasic;
use WPOAuth2\ResponseType\AccessTokenInterface;
use WPOAuth2\Storage\ClientCredentialsInterface;

/**
 * @author Brent Shaffer <bshafs at gmail dot com>
 *
 * @see OAuth2\ClientAssertionType_HttpBasic
 */
class ClientCredentials extends HttpBasic implements GrantTypeInterface {


	private $clientData;

	public function __construct( ClientCredentialsInterface $storage, array $config = array() ) {

		/**
		 * The client credentials grant type MUST only be used by confidential clients
		 *
		 * @see http://tools.ietf.org/html/rfc6749#section-4.4
		 */
		$config['allow_public_clients'] = false;

		parent::__construct( $storage, $config );
	}

	public function getQuerystringIdentifier() {
		return 'client_credentials';
	}

	public function getScope() {
		$this->loadClientData();

		return isset( $this->clientData['scope'] ) ? $this->clientData['scope'] : null;
	}

	public function getUserId() {
		$this->loadClientData();

		return isset( $this->clientData['user_id'] ) ? $this->clientData['user_id'] : null;
	}

	public function createAccessToken( AccessTokenInterface $accessToken, $client_id, $user_id, $scope ) {
		/**
		 * Client Credentials Grant does NOT include a refresh token
		 *
		 * @see http://tools.ietf.org/html/rfc6749#section-4.4.3
		 *
		 * @todo look into making this a feature and allowing refresh tokens in the
		 */
		$includeRefreshToken = false;

		return $accessToken->createAccessToken( $client_id, $user_id, $scope, $includeRefreshToken );
	}

	private function loadClientData() {
		if ( ! $this->clientData ) {
			$this->clientData = $this->storage->getClientDetails( $this->getClientId() );
		}
	}
}
