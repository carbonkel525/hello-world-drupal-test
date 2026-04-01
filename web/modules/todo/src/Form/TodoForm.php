<?php

declare(strict_types=1);

namespace Drupal\todo\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\todo\Service\TodoManager;

/**
 * Todo form with simple session-backed list.
 */
final class TodoForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'todo_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $todos = $this->todoManager()->getTodos();

    $form['#attached']['library'][] = 'todo/bootstrap';
    $form['#attached']['library'][] = 'todo/todo-ui';
    $form['#prefix'] = '<div class="todo-page container"><p class="todo-intro">' . $this->t('Add, complete, and remove your todos below.') . '</p>';
    $form['#suffix'] = '</div>';

    $form['todo_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'todo-form-wrapper'],
    ];

    $form['todo_container']['new_item'] = [
      '#type' => 'textfield',
      '#title' => $this->t('New todo'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#placeholder' => $this->t('Example: Read 20 pages'),
      '#attributes' => ['class' => ['form-control']],
    ];

    $form['todo_container']['actions'] = ['#type' => 'actions'];
    $form['todo_container']['actions']['add'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add todo'),
      '#submit' => ['::addTodoSubmit'],
      '#attributes' => ['class' => ['btn', 'btn-primary']],
      '#ajax' => [
        'callback' => '::ajaxRefresh',
        'wrapper' => 'todo-form-wrapper',
      ],
    ];

    if (!empty($todos)) {
      $form['todo_container']['list'] = [
        '#type' => 'table',
        '#header' => [$this->t('Done'), $this->t('Task'), $this->t('Actions')],
        '#empty' => $this->t('No todos yet.'),
        '#attributes' => ['class' => ['table', 'table-striped', 'table-hover']],
      ];

      foreach ($todos as $id => $item) {
        $form['todo_container']['list'][$id]['done'] = [
          '#type' => 'checkbox',
          '#default_value' => !empty($item['done']),
          '#disabled' => TRUE,
        ];

        $task = (string) ($item['task'] ?? '');
        $form['todo_container']['list'][$id]['task'] = [
          '#markup' => !empty($item['done']) ? '<span class="todo-done">' . $task . '</span>' : $task,
        ];

        $form['todo_container']['list'][$id]['ops'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['todo-actions']],
        ];
        $form['todo_container']['list'][$id]['ops']['toggle'] = [
          '#type' => 'submit',
          '#value' => !empty($item['done']) ? $this->t('Mark open') : $this->t('Mark done'),
          '#name' => 'toggle_' . $id,
          '#submit' => ['::toggleTodoSubmit'],
          '#todo_id' => (string) $id,
          '#todo_target_state' => empty($item['done']),
          '#limit_validation_errors' => [],
          '#attributes' => ['class' => ['btn', 'btn-sm', 'btn-outline-secondary']],
          '#ajax' => [
            'callback' => '::ajaxRefresh',
            'wrapper' => 'todo-form-wrapper',
          ],
        ];
        $form['todo_container']['list'][$id]['ops']['delete'] = [
          '#type' => 'submit',
          '#value' => $this->t('Delete'),
          '#name' => 'delete_' . $id,
          '#submit' => ['::deleteTodoSubmit'],
          '#todo_id' => (string) $id,
          '#limit_validation_errors' => [],
          '#attributes' => ['class' => ['btn', 'btn-sm', 'btn-outline-danger']],
          '#ajax' => [
            'callback' => '::ajaxRefresh',
            'wrapper' => 'todo-form-wrapper',
          ],
        ];
      }
    }

    return $form;
  }

  /**
   * Adds a todo item.
   */
  public function addTodoSubmit(array &$form, FormStateInterface $form_state): void {
    $task = trim((string) $form_state->getValue('new_item'));
    if ($task === '') {
      return;
    }

    $this->todoManager()->addTodo($task);

    // Clear the textfield value after successful submit.
    $form_state->setValue('new_item', '');
    $user_input = $form_state->getUserInput();
    unset($user_input['new_item']);
    $form_state->setUserInput($user_input);

    $form_state->setRebuild(TRUE);
    $this->messenger()->addStatus($this->t('Todo added.'));
    $this->redirectIfNotAjax($form_state);
  }

  /**
   * Toggles done/open state.
   */
  public function toggleTodoSubmit(array &$form, FormStateInterface $form_state): void {
    $trigger = $form_state->getTriggeringElement();
    $id = (int) ($trigger['#todo_id'] ?? 0);
    $target_state = !empty($trigger['#todo_target_state']);
    if ($id <= 0) {
      return;
    }

    $this->todoManager()->setDone($id, $target_state);
    $form_state->setRebuild(TRUE);
    $this->redirectIfNotAjax($form_state);
  }

  /**
   * Deletes a todo item.
   */
  public function deleteTodoSubmit(array &$form, FormStateInterface $form_state): void {
    $trigger = $form_state->getTriggeringElement();
    $id = (int) ($trigger['#todo_id'] ?? 0);
    if ($id <= 0) {
      return;
    }

    $this->todoManager()->delete($id);
    $form_state->setRebuild(TRUE);
    $this->messenger()->addStatus($this->t('Todo removed.'));
    $this->redirectIfNotAjax($form_state);
  }

  /**
   * Ajax callback to refresh todo UI.
   */
  public function ajaxRefresh(array &$form, FormStateInterface $form_state): array {
    return $form['todo_container'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {}

  /**
   * Applies Post-Redirect-Get fallback for non-AJAX submissions.
   */
  private function redirectIfNotAjax(FormStateInterface $form_state): void {
    if (!$this->getRequest()->isXmlHttpRequest()) {
      $form_state->setRedirect('todo.overview');
    }
  }

  /**
   * Returns the todo manager service.
   */
  private function todoManager(): TodoManager {
    /** @var \Drupal\todo\Service\TodoManager $manager */
    $manager = \Drupal::service('todo.manager');
    return $manager;
  }

}
