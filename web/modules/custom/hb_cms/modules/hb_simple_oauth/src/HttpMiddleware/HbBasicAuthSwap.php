<?php

namespace Drupal\hb_simple_oauth\HttpMiddleware;

use Drupal\simple_oauth\HttpMiddleware\BasicAuthSwap;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Uses the basic auth information to provide the client credentials for OAuth2.
 */
class HbBasicAuthSwap extends BasicAuthSwap {

  /**
   * Handles a Request to convert it to a Response.
   *
   * If the request appears to be an OAuth2 token request with Basic Auth,
   * swap the Basic Auth credentials into the request body and then remove the
   * Basic Auth credentials from the request so that core authentication is
   * not performed later.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The input request.
   * @param int $type
   *   The type of the request. One of HttpKernelInterface::MASTER_REQUEST or
   *   HttpKernelInterface::SUB_REQUEST.
   * @param bool $catch
   *   Whether to catch exceptions or not.
   *
   * @throws \Exception
   *   When an Exception occurs during processing.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A Response instance
   */
  public function handle(Request $request, $type = self::MAIN_REQUEST, $catch = true): Response {
    if (
      strpos($request->getPathInfo(), '/api/oauth/token') !== FALSE &&
      $request->headers->has('PHP_AUTH_USER') &&
      $request->headers->has('PHP_AUTH_PW')
    ) {
      // Swap the Basic Auth credentials into the request data.
      $request->request->set('client_id', $request->headers->get('PHP_AUTH_USER'));
      $request->request->set('client_secret', $request->headers->get('PHP_AUTH_PW'));

      // Remove the Basic Auth credentials to prevent later authentication.
      $request->headers->remove('PHP_AUTH_USER');
      $request->headers->remove('PHP_AUTH_PW');
    }

    return $this->httpKernel->handle($request, $type, $catch);
  }

}
