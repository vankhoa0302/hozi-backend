<?php

namespace Drupal\hb_cms\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class HbCMSRouteSubscriber extends RouteSubscriberBase {

	/**
	 * {@inheritdoc}
	 */
	protected function alterRoutes(RouteCollection $collection) {
		if ($route = $collection->get('system.csrftoken')) {
			$route->setPath('/api/session/token');
		}

	}

}