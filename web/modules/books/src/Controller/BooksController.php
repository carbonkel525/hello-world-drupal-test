<?php

namespace Drupal\books\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for the books overview page.
 */
class BooksController extends ControllerBase {

  /**
   * The node storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * Creates a BooksController instance.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->nodeStorage = $entity_type_manager->getStorage('node');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Builds the /books2 page.
   */
  public function overview(): array {
    $nids = $this->nodeStorage->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', NodeInterface::PUBLISHED)
      ->condition('type', 'book')
      ->sort('created', 'DESC')
      ->execute();

    $build = [
      '#attached' => [
        'library' => [
          'books/bootstrap',
          'books/books-page',
        ],
      ],
      'wrapper_start' => [
        '#markup' => '<div class="container books-page"><div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h2 m-0">Books</h1><a class="btn btn-primary" href="/node/add/book">Add book</a></div><div class="row g-4">',
      ],
    ];

    if (empty($nids)) {
      $build['empty'] = [
        '#markup' => '<div class="col-12"><div class="alert alert-info">No books found yet.</div></div>',
      ];
    }
    else {
      $nodes = $this->nodeStorage->loadMultiple($nids);
      foreach ($nodes as $node) {
        if (!$node instanceof NodeInterface) {
          continue;
        }
        $title = $node->label();
        $author = $node->hasField('field_author') && !$node->get('field_author')->isEmpty() ? $node->get('field_author')->value : 'Unknown author';
        $price = $node->hasField('field_price') && !$node->get('field_price')->isEmpty() ? $node->get('field_price')->value : 'N/A';
        $image_url = $this->resolveImageUrl($node);

        $build['book_' . $node->id()] = [
          '#type' => 'inline_template',
          '#template' => '<div class="col-12 col-sm-6 col-lg-4"><div class="card h-100 shadow-sm">{% if image_url %}<img src="{{ image_url }}" class="card-img-top" alt="{{ image_alt }}">{% endif %}<div class="card-body d-flex flex-column"><h2 class="h5 card-title">{{ title }}</h2><p class="card-text text-muted mb-1">{{ author_label }}</p><p class="card-text fw-semibold mb-3">{{ price_label }}</p><a class="btn btn-outline-primary mt-auto" href="{{ details_url }}">{{ details_label }}</a></div></div></div>',
          '#context' => [
            'image_url' => $image_url,
            'image_alt' => $this->t('Cover for @title', ['@title' => $title]),
            'title' => $title,
            'author_label' => $this->t('Author: @author', ['@author' => $author]),
            'price_label' => '$' . $price,
            'details_url' => $this->nodeAliasUrl($node),
            'details_label' => $this->t('View details'),
          ],
        ];
      }
    }

    $build['wrapper_end'] = [
      '#markup' => '</div></div>',
    ];

    return $build;
  }

  /**
   * Builds an Amazon-like book detail page.
   */
  public function detail(NodeInterface $node): array {
    if ($node->bundle() !== 'book') {
      throw new NotFoundHttpException();
    }

    $title = $node->label();
    $author = $node->hasField('field_author') && !$node->get('field_author')->isEmpty()
      ? $node->get('field_author')->value
      : (string) $this->t('Unknown author');
    $price = $node->hasField('field_price') && !$node->get('field_price')->isEmpty()
      ? '$' . $node->get('field_price')->value
      : (string) $this->t('Price unavailable');
    $stock = $node->hasField('field_stock') && !$node->get('field_stock')->isEmpty()
      ? (int) $node->get('field_stock')->value
      : NULL;
    $description = $node->hasField('field_description') && !$node->get('field_description')->isEmpty()
      ? $node->get('field_description')->value
      : (string) $this->t('No description available yet.');
    $image_url = $this->resolveImageUrl($node);

    $stock_label = $stock === NULL
      ? (string) $this->t('Stock unknown')
      : ($stock > 0 ? (string) $this->t('In stock (@count available)', ['@count' => $stock]) : (string) $this->t('Currently unavailable'));

    return [
      '#attached' => [
        'library' => [
          'books/bootstrap',
          'books/books-page',
        ],
      ],
      '#type' => 'inline_template',
      '#template' => '<div class="container books-page books-detail"><div class="row g-4"><div class="col-12 col-lg-5">{% if image_url %}<img src="{{ image_url }}" class="img-fluid border rounded-3 shadow-sm w-100 books-detail-cover" alt="{{ image_alt }}">{% else %}<div class="border rounded-3 p-5 text-center text-muted">No cover image</div>{% endif %}</div><div class="col-12 col-lg-7"><h1 class="h2 mb-2">{{ title }}</h1><p class="text-muted mb-3">{{ author_label }}</p><hr><div class="mb-3"><span class="books-price">{{ price }}</span></div><p class="mb-4">{{ description }}</p><div class="card books-buy-box border-warning-subtle"><div class="card-body"><p class="mb-2 fw-semibold">{{ stock_label }}</p><a class="btn btn-warning w-100 mb-2 fw-semibold" href="#">{{ buy_now }}</a><a class="btn btn-outline-dark w-100" href="#">{{ add_cart }}</a><p class="small text-muted mt-3 mb-0">{{ shipped_text }}</p></div></div><a class="btn btn-link mt-3 ps-0" href="{{ back_url }}">{{ back_label }}</a></div></div></div>',
      '#context' => [
        'image_url' => $image_url,
        'image_alt' => $this->t('Cover for @title', ['@title' => $title]),
        'title' => $title,
        'author_label' => $this->t('By @author', ['@author' => $author]),
        'price' => $price,
        'description' => $description,
        'stock_label' => $stock_label,
        'buy_now' => $this->t('Buy now'),
        'add_cart' => $this->t('Add to cart'),
        'shipped_text' => $this->t('Secure transaction. Delivered in 1-2 business days.'),
        'back_url' => Url::fromRoute('books.overview')->toString(),
        'back_label' => $this->t('Back to books'),
      ],
    ];
  }

  /**
   * Resolves an image URL from common book cover field names.
   */
  protected function resolveImageUrl(NodeInterface $node): string {
    $image_field_names = [
      'field_cover',
      'field_cover_image',
      'field_image',
      'field_book_cover',
    ];

    foreach ($image_field_names as $field_name) {
      if (!$node->hasField($field_name) || $node->get($field_name)->isEmpty()) {
        continue;
      }

      $entity = $node->get($field_name)->entity;
      if (!$entity) {
        continue;
      }

      // Direct image/file field on node.
      if (method_exists($entity, 'createFileUrl')) {
        return $entity->createFileUrl();
      }

      // Media reference field on node.
      if (method_exists($entity, 'hasField') && $entity->hasField('field_media_image')) {
        $media_image = $entity->get('field_media_image');
        if (!$media_image->isEmpty() && $media_image->entity && method_exists($media_image->entity, 'createFileUrl')) {
          return $media_image->entity->createFileUrl();
        }
      }
    }

    return '';
  }

  /**
   * Returns the best URL for a node, preferring its alias.
   */
  protected function nodeAliasUrl(NodeInterface $node): string {
    $system_path = '/node/' . $node->id();
    $language = $node->language()->getId();
    $alias = \Drupal::service('path_alias.manager')->getAliasByPath($system_path, $language);

    if ($alias && $alias !== $system_path) {
      return $alias;
    }

    return $system_path;
  }

}
