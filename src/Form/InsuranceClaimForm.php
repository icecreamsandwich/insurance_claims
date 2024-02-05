<?php

declare(strict_types=1);

namespace Drupal\insurance_claims\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;

/**
 * Provides a Insurance claims form.
 */
final class InsuranceClaimForm extends FormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 'insurance_claim_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $form['claim_number'] = [
      '#type' => 'number',
      '#title' => $this->t('Claims Number'),
      '#required' => TRUE,
      '#attributes' => array('disabled' => TRUE),
      '#default_value' => rand(100000000, 999999999),
    ];

    $form['patient_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Patient Name'),
    ];
    $form['service_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Service Type'),
      '#required' => TRUE,
      '#options' => array(
        'Medical' =>  t('Medical'),
        'Dental' =>  t('Dental')
      )
    ];
    $form['provider_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Provider Name'),
    ];
    $form['claim_value'] = [
      '#type' => 'textfield',
      '#size' => 50,
      '#maxlength' => 15,
      '#title' => $this->t('Claims Value'),
      '#prefix' => '$',
    ];
    
    $form['submission_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Submission Date'),
      '#date_date_format' => 'Y-m-d\TH:i:s',
      '#attributes' => ['style' => 'width:250px;'],
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Submit Claim'),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void
  {
    // Validations.
    $patient_name = $form_state->getValue('patient_name');
    if (!preg_match('/^[a-zA-Z\.\s]+$/', $patient_name)) {
      $form_state->setErrorByName(
        'patient_name',
        $this->t('Patient name should only contain alphabets'),
      );    
    }
    //Claim value validation
    $claim_value = str_replace(',', '', $form_state->getValue('claim_value'));
    if (!is_numeric($claim_value) || floatval($claim_value) != $claim_value) {
      $form_state->setErrorByName(
        'claim_value', 
        $this->t('Please enter a valid decimal value.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    //Save data to a json file:Start
    try {
      $host = \Drupal::request()->getHost();
      $destination = 'public://';
      $data = file_get_contents($host.'/claims-data-export?page&_format=json');
      $filepath = 'claims-data.json';

      if (!\Drupal::service('file_system')->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY)) {
        \Drupal::logger('insurance_claims')->error('Directory not acessible');
      }
      \Drupal::service('file_system')->saveData($data, $destination.$filepath, FileSystemInterface::EXISTS_REPLACE);

      $new_file = File::create([
        'uri' => $destination . $filepath,
        'status' => 1,
        'uid' => 1,
      ]);
      if($new_file->save()){
        \Drupal::logger('insurance_claims')->notice('File successfull saved');
      }
    } catch (\Exception $e) {
      \Drupal::logger('insurance_claims')->error('Error:'.$e);
    }
    //Save data to a json file:End

    $claim_number = $form_state->getValue('claim_number');
    //Save the insurance_claims (CT) data
    $node = \Drupal::entityTypeManager()->getStorage('node')->create([
      'type' => 'insurance_claims',
      'title' => 'Claim-' . $form_state->getValue('claim_number'),
      'status' => 1,
    ]);
    if(empty($form_state->getValue('submission_date'))){
      $current_time = \Drupal::time()->getCurrentTime();
      $submission_date = date('Y-m-d\TH:i:s', $current_time);
    }else{
      $submitted_date = $form_state->getValue('submission_date');
      $submission_date = \Drupal::service('date.formatter')->format(
        $submitted_date->getTimestamp(), 'custom', 'Y-m-d\TH:i:s'
      );
    }
   
    $node->field_claims_number->value = $form_state->getValue('claim_number');
    $node->field_claims_value->value = $form_state->getValue('claim_value');
    $node->field_patient_name->value = $form_state->getValue('patient_name');
    $node->field_provider_name->value = $form_state->getValue('provider_name');
    $node->field_service_type->value = $form_state->getValue('service_type');
    $node->field_submission_date->value = $submission_date;
    $node->save();
    $this->messenger()->addStatus($this->t('Insurance claim @claim_no has been submitted', ["@claim_no" => $claim_number]));
  }
}
