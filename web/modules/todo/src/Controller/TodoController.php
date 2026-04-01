<?php

declare(strict_types=1);

namespace Drupal\todo\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\todo\Form\TodoForm;

/**
 * Returns responses for Todo routes.
 */
final class TodoController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function __invoke(): array {
    return [
      '#attached' => [
        'library' => [
          'todo/bootstrap',
          'todo/todo-ui',
        ],
      ],
      'wrapper_start' => [
        '#markup' => '<div class="todo-page container">',
      ],
      'intro' => [
        '#markup' => '<p class="todo-intro">' . $this->t('Add, complete, and remove your todos below.') . '</p>',
      ],
      'form' => $this->formBuilder()->getForm(TodoForm::class),
      'wrapper_end' => [
        '#markup' => '</div>',
      ],
    ];
  }

}
