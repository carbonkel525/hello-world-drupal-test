<?php

declare(strict_types=1);

namespace Drupal\todo\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

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
    $todos = $this->getTodos();

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

    $todos = $this->getTodos();
    $id = (string) (time() . random_int(100, 999));
    $todos[$id] = [
      'task' => $task,
      'done' => FALSE,
    ];
    $this->setTodos($todos);

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
    $id = (string) ($trigger['#todo_id'] ?? '');
    $target_state = !empty($trigger['#todo_target_state']);
    $todos = $this->getTodos();
    if (!isset($todos[$id])) {
      return;
    }

    $todos[$id]['done'] = $target_state;
    $this->setTodos($todos);
    $form_state->setRebuild(TRUE);
    $this->redirectIfNotAjax($form_state);
  }

  /**
   * Deletes a todo item.
   */
  public function deleteTodoSubmit(array &$form, FormStateInterface $form_state): void {
    $trigger = $form_state->getTriggeringElement();
    $id = (string) ($trigger['#todo_id'] ?? '');
    $todos = $this->getTodos();
    if (!isset($todos[$id])) {
      return;
    }

    unset($todos[$id]);
    $this->setTodos($todos);
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
   * Gets todos from session.
   */
  private function getTodos(): array {
    $todos = $this->getRequest()->getSession()->get('todo.items', []);
    return is_array($todos) ? $todos : [];
  }

  /**
   * Persists todos in session.
   */
  private function setTodos(array $todos): void {
    $this->getRequest()->getSession()->set('todo.items', $todos);
  }

  /**
   * Applies Post-Redirect-Get fallback for non-AJAX submissions.
   */
  private function redirectIfNotAjax(FormStateInterface $form_state): void {
    if (!$this->getRequest()->isXmlHttpRequest()) {
      $form_state->setRedirect('todo.overview');
    }
  }

}
