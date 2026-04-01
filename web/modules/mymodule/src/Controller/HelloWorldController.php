<?php

namespace Drupal\mymodule\Controller;

use Drupal\Core\Controller\ControllerBase;

class HelloWorldController extends ControllerBase {
    public function page() {
        return [
            '#markup' => '<h1>Hello World!</h1>',
        ];
    }
}