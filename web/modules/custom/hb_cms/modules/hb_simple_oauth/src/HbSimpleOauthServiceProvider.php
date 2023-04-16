<?php

namespace Drupal\hb_simple_oauth;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

// @note: You only need Reference, if you want to change service arguments.
use Symfony\Component\DependencyInjection\Reference;

/**
 * @inheritDoc
 */
class HbSimpleOauthServiceProvider extends ServiceProviderBase {

	/**
	 * {@inheritdoc}
	 */
	public function alter(ContainerBuilder $container) {
		if ($container->hasDefinition('simple_oauth.http_middleware.basic_auth_swap')) {
			$definition = $container->getDefinition('simple_oauth.http_middleware.basic_auth_swap');
			$definition->setClass('Drupal\hb_simple_oauth\HttpMiddleware\HbBasicAuthSwap');
		}
	}
}