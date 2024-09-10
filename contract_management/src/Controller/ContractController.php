<?php
namespace Drupal\contract_management\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use \Drupal\Component\Utility\Html;

class ContractController extends ControllerBase {

  /**
   * Lists all contracts.
   */
  public function contractsPage() {
    $add_contract_url = Url::fromRoute('contract_management.contract_add');
    $add_contract_link = Link::fromTextAndUrl($this->t('Add Contract'), $add_contract_url)
      ->toRenderable();
    $add_contract_link['#attributes']['class'][] = 'button';
   
    $connection = \Drupal::database();
    $query = $connection->select('contract_management', 'n')
      ->fields('n', ['id', 'name', 'description']);
    $result = $query->execute();

    $route_id = 'contract_management.document_automation';
    $url = Url::fromRoute($route_id, [], ['absolute' => TRUE])->toString();

    $rows = [];
    foreach ($result as $record) {
      $newUrl = $url . "?id=".$record->id;
      $rows[] = [$this->t('<a href="'.$newUrl.'">'.$record->name .'</a>'), $this->t($record->description)];
    }
    
    // Define the table with select fields
    $table = [
      '#type' => 'table',
      '#header' => ['Name', 'Description'],
      '#rows' => $rows,
    ];


    return [
      '#markup' => $this->t('List of contracts will appear here.'),
      'table' => $table,
      'add_contract' => $add_contract_link,
    ];
  }

}
