<?php

declare(strict_types=1);

namespace Drupal\todo\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\NodeInterface;
use Psr\Log\LoggerInterface;

/**
 * Business logic for todo storage using nodes.
 */
final class TodoManager {

  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $nodeStorage;

  /**
   * The current user.
   */
  private AccountProxyInterface $currentUser;

  /**
   * Logger channel.
   */
  private LoggerInterface $logger;

  /**
   * Creates a manager instance.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user, LoggerInterface $logger) {
    $this->nodeStorage = $entity_type_manager->getStorage('node');
    $this->currentUser = $current_user;
    $this->logger = $logger;
  }

  /**
   * Returns current user's todos.
   */
  public function getTodos(): array {
    $query = $this->nodeStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'todo_item')
      ->condition('uid', (int) $this->currentUser->id())
      ->condition('status', NodeInterface::PUBLISHED)
      ->sort('created', 'DESC');

    $nids = $query->execute();
    if (empty($nids)) {
      return [];
    }

    $nodes = $this->nodeStorage->loadMultiple($nids);
    $todos = [];
    foreach ($nodes as $node) {
      if (!$node instanceof NodeInterface) {
        continue;
      }
      $done = $node->hasField('field_todo_done') && !$node->get('field_todo_done')->isEmpty()
        ? (bool) $node->get('field_todo_done')->value
        : FALSE;

      $todos[(string) $node->id()] = [
        'task' => $node->label(),
        'done' => $done,
      ];
    }

    return $todos;
  }

  /**
   * Creates a todo item.
   */
  public function addTodo(string $task): void {
    $task = trim($task);
    if ($task === '') {
      return;
    }

    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->nodeStorage->create([
      'type' => 'todo_item',
      'title' => $task,
      'uid' => (int) $this->currentUser->id(),
      'status' => NodeInterface::PUBLISHED,
      'field_todo_done' => 0,
    ]);
    $node->save();
    $this->logger->notice('Todo added: nid=@nid by uid=@uid', [
      '@nid' => (int) $node->id(),
      '@uid' => (int) $this->currentUser->id(),
    ]);
  }

  /**
   * Updates done state.
   */
  public function setDone(int $nid, bool $done): void {
    $node = $this->loadOwnedTodo($nid);
    if (!$node) {
      return;
    }
    if ($node->hasField('field_todo_done')) {
      $node->set('field_todo_done', $done ? 1 : 0);
      $node->save();
      $this->logger->notice('Todo updated: nid=@nid done=@done by uid=@uid', [
        '@nid' => (int) $node->id(),
        '@done' => $done ? '1' : '0',
        '@uid' => (int) $this->currentUser->id(),
      ]);
    }
  }

  /**
   * Deletes a todo item.
   */
  public function delete(int $nid): void {
    $node = $this->loadOwnedTodo($nid);
    if ($node) {
      $deleted_nid = (int) $node->id();
      $node->delete();
      $this->logger->notice('Todo deleted: nid=@nid by uid=@uid', [
        '@nid' => $deleted_nid,
        '@uid' => (int) $this->currentUser->id(),
      ]);
    }
  }

  /**
   * Loads todo node owned by current user.
   */
  private function loadOwnedTodo(int $nid): ?NodeInterface {
    $node = $this->nodeStorage->load($nid);
    if (!$node instanceof NodeInterface) {
      return NULL;
    }
    if ($node->bundle() !== 'todo_item') {
      return NULL;
    }
    if ((int) $node->getOwnerId() !== (int) $this->currentUser->id()) {
      return NULL;
    }
    return $node;
  }

}
