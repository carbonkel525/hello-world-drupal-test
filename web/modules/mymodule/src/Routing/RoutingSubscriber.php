<?php

namespace Drupal\mymodule\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

class RoutingSubscriber extends RouteSubscriberBase {
    public function alterRoutes(RouteCollection $collection) {
        if ($route = $collection->get('mymodule.hello_world')) {
            $route->setPath('/hello-world');
        }
    }
}