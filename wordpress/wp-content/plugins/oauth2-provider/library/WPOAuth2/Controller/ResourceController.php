<?php

namespace WPOAuth2\Controller;

use WPOAuth2\RequestInterface;
use WPOAuth2\ResponseInterface;
use WPOAuth2\Scope;
use WPOAuth2\ScopeInterface;
use WPOAuth2\Storage\AccessTokenInterface;
use WPOAuth2\TokenType\TokenTypeInterface;

/**
 * @see WPOAuth2\Controller\ResourceControllerInterface
 */
class ResourceController implements ResourceControllerInterface {

	private $token;

	protected $tokenType;
	protected $tokenStorage;
	protected $config;
	protected $scopeUtil;

	public function __construct( TokenTypeInterface $tokenType, AccessTokenInterface $tokenStorage, $config = array(), ScopeInterface $scopeUtil = null ) {
		$this->tokenType    = $tokenType;
		$this->tokenStorage = $tokenStorage;

		$this->config = array_merge(
			array(
				'www_realm' => 'Service',
			),
			$config
		);

		if ( is_null( $scopeUtil ) ) {
			$scopeUtil = new Scope();
		}
		$this->scopeUtil = $scopeUtil;
	}

	public function verifyResourceRequest( RequestInterface $request, ResponseInterface $response, $scope = null ) {
		$token = $this->getAccessTokenData( $request, $response );

		// Check if we have token data
		if ( is_null( $token ) ) {
			return false;
		}

		/**
		 * Check scope, if provided
		 * If token doesn't have a scope, it's null/empty, or it's insufficient, then throw 403
	  *
		 * @see http://tools.ietf.org/html/rfc6750#section-3.1
		 */
		if ( $scope && ( ! isset( $token['scope'] ) || ! $token['scope'] || ! $this->scopeUtil->checkScope( $scope, $token['scope'] ) ) ) {
			$response->setError( 403, 'insufficient_scope', 'The request requires higher privileges than provided by the access token' );
			$response->addHttpHeaders(
				array(
					'WWW-Authenticate' => sprintf(
						'%s realm="%s", scope="%s", error="%s", error_description="%s"',
						$this->tokenType->getTokenType(),
						$this->config['www_realm'],
						$scope,
						$response->getParameter( 'error' ),
						$response->getParameter( 'error_description' )
					),
				)
			);

			return false;
		}

		// allow retrieval of the token
		$this->token = $token;

		return (bool) $token;
	}

	public function getAccessTokenData( RequestInterface $request, ResponseInterface $response ) {

		// Get the token parameter
		if ( $token_param = $this->tokenType->getAccessTokenParameter( $request, $response ) ) {
			if ( ! $token = $this->tokenStorage->getAccessToken( $token_param ) ) {
				$response->setError( 401, 'invalid_token', 'The access token provided is invalid' );
			} elseif ( ! isset( $token['expires'] ) || ! isset( $token['client_id'] ) ) {
				$response->setError( 401, 'malformed_token', 'Malformed token (missing "expires")' );
			} elseif ( current_time( 'timestamp' ) > $token['expires'] ) {
				$response->setError( 401, 'expired_token', 'The access token provided has expired' );
			} else {
				return $token;
			}
		}

		$authHeader = sprintf( '%s realm="%s"', $this->tokenType->getTokenType(), $this->config['www_realm'] );

		if ( $error = $response->getParameter( 'error' ) ) {
			$authHeader = sprintf( '%s, error="%s"', $authHeader, $error );
			if ( $error_description = $response->getParameter( 'error_description' ) ) {
				$authHeader = sprintf( '%s, error_description="%s"', $authHeader, $error_description );
			}
		}

		$response->addHttpHeaders( array( 'WWW-Authenticate' => $authHeader ) );

		return null;
	}

	// convenience method to allow retrieval of the token
	public function getToken() {
		return $this->token;
	}
}
