<?php

declare(strict_types=1);

namespace Drupal\insurance_claims\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\views\Views;

/**
 * Returns responses for insurance_claims routes.
 */
final class InsuranceClaimsController extends ControllerBase
{

  /**
   * Builds the response.
   */
  public function __invoke(): array
  {

    // $build['content'] = [
    //   '#type' => 'item',
    //   '#markup' => $this->t('It works!'),
    // ];
    // return $build;
    return [];
    // $node = \Drupal\node\Entity\Node::create(['type' => 'insurance_claims']);
    // $form = \Drupal::service('entity.form_builder')->getForm($node);
    // return $form;
  }

  public function show_claims()
  {
    $view_id = 'submitted_claims';
    $view = Views::getView($view_id);

    if ($view) {
      $view->initDisplay();
      $view->setDisplay('page_1');
      $view->execute();
      $result = \Drupal::service('renderer')->render($view->render());
      // return $result;
      return [
        '#markup' => $result,
      ];
    }
  }
}
