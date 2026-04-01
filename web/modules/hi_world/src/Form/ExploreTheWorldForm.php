<?php

declare(strict_types=1);

namespace Drupal\hi_world\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * Demo form for the explore-the-world page.
 */
final class ExploreTheWorldForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'hi_world_explore_the_world_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['tell the world'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Tell the world'),
      '#description' => $this->t('Tell the world something.'),
      '#required' => TRUE,
    ];

    $form['explore the world'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Explore the world'),
      '#description' => $this->t('Explore the world something.'),
      '#required' => TRUE,
    ];

    $form['discover the world'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Discover the world'),
      '#description' => $this->t('Discover the world and share your discoveries.'),
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->messenger()->addMessage($this->t('Thank you for your submission, @name.', ['@name' => $form_state->getValue('name')]));
  }
}