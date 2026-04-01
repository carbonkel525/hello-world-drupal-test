<?php

declare(strict_types=1);

namespace Drupal\hi_world\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\hi_world\Form\ShowTheWorldForm;
use Drupal\hi_world\Form\ExploreTheWorldForm;

/**
 * Returns responses for Hi world routes.
 */
final class HiWorldController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function overview(): array {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Hi World'),
    ];
  }


  /**
   * Builds the response.
   */
  public function __invoke(): array {
    return [
      '#theme' => 'hi_world_show_the_world',
      '#form' => $this->formBuilder()->getForm(ShowTheWorldForm::class),
      '#explore_form' => $this->formBuilder()->getForm(ExploreTheWorldForm::class),
    ];
  }

  /**
   * Builds the response.
   */
  public function exploreTheWorld(): array {
    return [
      '#theme' => 'explore_the_world',
      '#form' => $this->formBuilder()->getForm(ExploreTheWorldForm::class),
    ];
  }
}
