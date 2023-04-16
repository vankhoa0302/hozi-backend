<?php

namespace Drupal\hb_simple_oauth\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class HbSimpleOauthRouteSubscriber extends RouteSubscriberBase {

	/**
	 * {@inheritdoc}
	 */
	protected function alterRoutes(RouteCollection $collection) {
		if ($route = $collection->get('oauth2_token.token')) {
			$route->setPath('/api/oauth/token');
		}

	}

}