<?php

namespace WPOAuth2\Controller;

use WPOAuth2\RequestInterface;
use WPOAuth2\ResponseInterface;

/**
 *  This controller is called when a token is being requested.
 *  it is called to handle all grant types the application supports.
 *  It also validates the client's credentials
 *
 *  ex:
 *  > $tokenController->handleTokenRequest(OAuth2\Request::createFromGlobals(), $response = new OAuth2\Response());
 *  > $response->send();
 */
interface TokenControllerInterface {

	/**
	 * handleTokenRequest
	 *
	 * @param $request
	 * OAuth2\RequestInterface - The current http request
	 * @param $response
	 * OAuth2\ResponseInterface - An instance of OAuth2\ResponseInterface to contain the response data
	 */
	public function handleTokenRequest( RequestInterface $request, ResponseInterface $response);

	public function grantAccessToken( RequestInterface $request, ResponseInterface $response);
}
