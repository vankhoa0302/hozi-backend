<?php

namespace Drupal\hb_cms\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class HbRouteSubscriber extends RouteSubscriberBase {

	/**
	 * {@inheritdoc}
	 */
	protected function alterRoutes(RouteCollection $collection) {
		if ($route = $collection->get('locale.translate_page')) {
			$route->setDefault('_controller', '\Drupal\hb_cms\Controller\HbLocaleController::translatePage');
		}
	}

}