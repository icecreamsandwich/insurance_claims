<?php

declare(strict_types=1);

namespace Drupal\insurance_claims\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\views\Views;
use Drupal\node\Entity\Node;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Xss;

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

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];
    return $build;
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

  /**
   * Handles autocompletiion of claim numbers
   */
  function handleAutocomplete(Request $request){
    $results = [];
    $input = $request->query->get('q');
    if (!$input) {
      return new JsonResponse($results);
    }
    $input = Xss::filter($input);
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'insurance_claims')
      ->condition('field_claims_number', $input, 'CONTAINS')
      ->groupBy('nid')
      ->sort('created', 'DESC')
      ->range(0, 10);
    $query->accessCheck(TRUE);
    $ids = $query->execute();

    $nodes = $ids ? Node::loadMultiple($ids) : [];
    foreach ($nodes as $node) {
      $results[] = [
        'value' => $node->field_claims_number->value,
        'label' => $node->field_claims_number->value,
      ];
    }
    return new JsonResponse($results);
  }

}

