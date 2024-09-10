<?php

namespace Drupal\contract_management\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use PhpOffice\PhpWord\IOFactory;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;

class ContractForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contract_management_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    if ($form_state->has('page_num') && $form_state->get('page_num') == 2) {
      return $this->contractPageTwo($form, $form_state);
    }

    if ($form_state->has('page_num') && $form_state->get('page_num') == 3) {
      return $this->contractPageThree($form, $form_state);
    }


    // Create Step 1
    $form_state->set('page_num', 1);
    $form['#attached']['library'][] = 'contract_management/contract_management';

    $form['info_text'] = [
      '#type' => 'markup',
      '#markup' => $this->t('<div class="info-text"><h3>Contract Name</h3></div>'),
    ];


    $form['contract_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Contract Name'),
      '#default_value' => $form_state->getValue('contract_name', ''),
      '#required' => TRUE,
    ];

    $form['contract_description'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Contract Description'),
      '#default_value' => $form_state->getValue('contract_description') ? $form_state->getValue('contract_description')['value'] : null,
      '#format' => $form_state->getValue('format', 'basic_html'),
      '#allowed_formats' => ['basic_html', 'full_html'],
    ];
    
    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['next'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Next'),
      '#submit' => ['::contractFirstNextSubmit'],
    ];

   
    $form['#prefix'] = $this->getFormPrefix(1);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $configurations = [];

    $page_values = $form_state->get('page_values');

    if (empty($page_values['variables'])) {
      $form_state->setErrorByName('', $this->t('Contract variables are not available.'));
      return;
    }

    foreach ($page_values['variables'] as $variable) {
      $configurations[$variable] = [
        'type' => $form_state->getValue($variable.'_type'),
        'display_title' => $form_state->getValue($variable.'_display_title'),
        'help_text' => $form_state->getValue($variable.'_help_text'),
        'default_value' => $form_state->getValue($variable.'_default_value'),
      ];
    }

    if($configurations[$variable]) {
      try {
        $connection = Database::getConnection();
        $connection->insert('contract_management')
          ->fields([
            'name' => $page_values['contract_name'],
            'description' => $page_values['contract_description']['value'] ?? '',
            'file' => $page_values['file'],
            'fileid' => $page_values['file_id'],
            'data' => serialize($configurations),
            'created' => \Drupal::time()->getRequestTime(),
          ])
          ->execute();
  
        \Drupal::messenger()->addStatus($this->t('Contract details saved successfully.'));
        $url = Url::fromRoute('contract_management.contracts');
        $form_state->setRedirectUrl($url);
      } catch (\Exception $e) {
        \Drupal::messenger()->addError($this->t('An error occurred while saving details:'. $e));
      }


    } else {
      $form_state->setErrorByName('', $this->t('oops! Something went wrong'));
      return;
    }
   
    }


  /**
   * Provides custom submission handler for page 1.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function contractFirstNextSubmit(array &$form, FormStateInterface $form_state) {
    $form_state
      ->set('page_values', [
        // Keep only first step values to minimize stored data.
        'contract_name' => $form_state->getValue('contract_name'),
        'contract_description' => $form_state->getValue('contract_description'),
      ])
      ->set('page_num', 2)
      ->setRebuild(TRUE);
  }

  /**
   * Builds the second step form (page 2).
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The render array defining the elements of the form.
   */
  public function contractPageTwo(array &$form, FormStateInterface $form_state) {

    $form['#attached']['library'][] = 'contract_management/contract_management';

    $form['info_text'] = [
      '#type' => 'markup',
      '#markup' => $this->t('<div class="info-text"><h3>Contract Documents</h3></div>'),
    ];

    $form['document'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload Documents (.docx)'),
      '#upload_location' => 'public://contract_documents/',
      '#required' => TRUE,
      '#multiple' => FALSE,
      '#default_value' => $form_state->getValue('document') ? [$form_state->getValue('document')[0]] : [],
      '#description' => $this->t('Upload a .docx file with the contract template.'),
      '#upload_validators' => [
        'file_validate_extensions' => ['docx'], 
      ],
    ];

    $form['back'] = [
      '#type' => 'submit',
      '#value' => $this->t('Back'),
      '#submit' => ['::contractPageTwoBack'],
      '#limit_validation_errors' => [],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Next'),
      '#submit' => ['::contractSecondNextSubmit']
    ];
    $form['#prefix'] = $this->getFormPrefix(2);
    

    return $form;
  }

  public function contractSecondNextSubmit(array &$form, FormStateInterface $form_state) {
    $name = $form_state->get('page_values');
    $variables = [];
    //logic for read file
    $file_id = $form_state->getValue('document')[0];
    $file = File::load($file_id);
    if ($file) {
      $file->setPermanent();
      $file->save();

      $file_uri = $file->getFileUri();
      $absolute_path = \Drupal::service('file_system')->realpath($file_uri);

      try {
        $variables = $this->extractVariablesFromWordDoc($absolute_path);
      }
      catch (\Exception $e) {
        \Drupal::messenger()->addError($this->t('Error: @message', ['@message' => $e->getMessage()]));
      }
    }
    else {
      \Drupal::messenger()->addError($this->t('The file could not be loaded.'));
    }

    $form_state
      ->set('page_values', [ 
        'variables' => $variables,
        'contract_name' => $name['contract_name'],
        'contract_description' => $name['contract_description'],
        'file' => $file_uri ?? '-',
        'file_id' => $file_id ?? '0',

      ])
      ->set('page_num', 3)
      ->setRebuild(TRUE);
  }

  public function contractPageThree(array &$form, FormStateInterface $form_state) {

    $form['#attached']['library'][] = 'contract_management/contract_management';

    $page_values = $form_state->get('page_values');

    foreach ($page_values['variables'] as $key=>$variable) {
      $form[$variable] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Field Setting:  @variable', ['@variable' => $variable]),
      ];

    $form[$variable][$variable.'_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Field type'),
      '#options' => [
        'textfield' => 'Text',
        'number' => 'Number',
        'date' => 'Date',
        'email' => 'Email',
      ],
      '#required' => TRUE,
    ];

    $form[$variable][$variable.'_display_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Display Title'),
      '#required' => TRUE,
    ];

    $form[$variable][$variable.'_help_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Help Text'),
      '#required' => FALSE,
    ];


    $form[$variable][$variable.'_default_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Value'),
    ];
  }


    $form['back'] = [
      '#type' => 'submit',
      '#value' => $this->t('Back'),
      '#submit' => ['::contractPageThreeBack'],
      '#limit_validation_errors' => [],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Save'),
    ];
    $form['#prefix'] = $this->getFormPrefix(3);

    return $form;
  }

  /**
   * Provides custom submission handler for 'Back' button (page 2).
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function contractPageTwoBack(array &$form, FormStateInterface $form_state) {
    $form_state
      // Restore values for the first step.
      ->setValues($form_state->get('page_values'))
      ->set('page_num', 1)
      ->setRebuild(TRUE);
  }

  public function contractPageThreeBack(array &$form, FormStateInterface $form_state) {
    $form_state
      ->setValues($form_state->get('page_values'))
      ->set('page_num', 2)
      ->setRebuild(TRUE);
  }

  public function getFormPrefix($step){
      
      switch ($step) {
        case 1:
         return '<div class="my-form-wrapper">
              <ul id="progressbar">
                <li class="active" id="account"><span><strong>Name</strong></span></li>
                <li id="personal"><span><strong>Documents</strong></span></li>
                <li id="confirm"><span><strong>Field Setting</strong></span></li>
              </ul>
          </div>';
          break;
        case 2:
          return '<div class="my-form-wrapper">
            <ul id="progressbar">
              <li  id="account"><span><strong>Name</strong></span></li>
              <li class="active" id="personal"><span><strong>Documents</strong></span></li>
              <li id="confirm"><span><strong>Field Setting</strong></span></li>
          </ul>
          </div>';
          break;
        case 3:
          return '<div class="my-form-wrapper">
            <ul id="progressbar">
              <li  id="account"><span><strong>Name</strong></span></li>
              <li id="personal"><span><strong>Documents</strong></span></li>
              <li id="confirm" class="active"><span><strong>Field Setting</strong></span></li>
          </ul>
          </div>';
          break;
        default:
           return '';


  }
}

/**
   * Extract variables enclosed in {{ }} from the Word document.
   *
   * @param string $file_path
   *   The path to the uploaded Word document.
   *
   * @return array
   *   An array of variables found in the document.
   */
  function extractVariablesFromWordDoc($file_path) {
    // Load the document using PHPWord.
    $phpWord = IOFactory::load($file_path);

    // Get the document content as plain text.
    $docText = '';
    foreach ($phpWord->getSections() as $section) {
      foreach ($section->getElements() as $element) {
        if (method_exists($element, 'getText')) {
          $docText .= $element->getText() . ' ';
        }
      }
    }

    // Use regex to find all variables enclosed in {{ }}.
    preg_match_all('/\{\{(.*?)\}\}/', $docText, $matches);

    // Return unique variables.
    return array_unique($matches[1]);
  }


}