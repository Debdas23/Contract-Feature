<?php

namespace Drupal\contract_management\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use PhpOffice\PhpWord\IOFactory;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Core\Link;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Text;

class DocumentAutomation extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'document_automation';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $data = null;
    if(isset($_GET['id'])) {
      $connection = \Drupal::database();
      $query = $connection->select('contract_management', 'n')
      ->fields('n', ['data', 'file'])
      ->condition('n.id', $_GET['id']);
      $result = $query->execute();
      foreach ($result as $record) {
          $data = $record->data;
          $form_state->set('file', $record->file);
      }
    }

    if(!$data) {
      $form_state->set('no_data', TRUE);
      return;
    }
    
    

    $serialized_data = $data;
    
    // Unserialize the data to get the array
    $data_array = unserialize($serialized_data);

    // Generate form fields based on the unserialized data
    foreach($data_array as $key => $value) {
      if (isset($value['type'])) {
        $form[$key] = [
          '#type' => $value['type'],
          '#title' => $this->t($value['display_title']),
          '#default_value' => $value['default_value'],
          '#description' => $value['help_text'],
          '#required' => TRUE,
        ];
      }
    }
    
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

 
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get the name from the form input
    //$name = $form_state->getValue('name');
    $name = "Ganesh";
    $values = $form_state->getValues();

    // Hardcoded file path to the contract DOCX file
    $file_path = $form_state->get('file');
    $real_path = \Drupal::service('file_system')->realpath($file_path);

    // Ensure the file exists
    if (!file_exists($real_path)) {
      \Drupal::messenger()->addError($this->t('File not found.'));
      return;
    }

    // Load the .docx file using PHPWord
    $phpWord = IOFactory::load($real_path, 'Word2007');

    // Loop through sections to find and replace placeholders
    foreach ($phpWord->getSections() as $section) {
      foreach ($section->getElements() as $element) {
        // Handle TextRun (most text in Word docs is stored here)
        if ($element instanceof TextRun) {
          foreach ($element->getElements() as $textElement) {
            if ($textElement instanceof Text) {
              // Get the current text
              $full_text = $new_text = ''; // Variable to store concatenated text

                // Concatenate text elements in the TextRun
                foreach ($element->getElements() as $textElement) {
                  if ($textElement instanceof Text) {
                    $full_text .= $textElement->getText(); 
                    
                    $textElement->setText('');
                  }
                }

                foreach ($values as $key => $value) {
                  $new_text = str_replace('{{'.$key.'}}', $value, $full_text);
                  $full_text = $new_text;
                }

                if($new_text) {
                  $textElement->setText($new_text);
                  }
              } 
            }
          }
        }

      }


    
    // Save the modified document
    $new_file_name = 'modified_contract_' . time() . '.docx';
    $new_file_path = 'public://contract_documents/' . $new_file_name;
    $new_real_path = \Drupal::service('file_system')->realpath($new_file_path);
    $phpWord->save($new_real_path, 'Word2007');

    // Generate the download link for the modified file
    $download_url = \Drupal::service('file_url_generator')->generateAbsoluteString($new_file_path);
    \Drupal::messenger()->addStatus($this->t('The document has been generated. <a href="@url" target="_blank">Download it here</a>', ['@url' => $download_url]));
   
    $form_state->setRedirect('contract_management.contracts');


  }
}
