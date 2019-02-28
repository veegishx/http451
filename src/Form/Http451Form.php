<?php

namespace Drupal\http451\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration Form for ipstack API.
 */
class Http451Form extends ConfigFormBase {

  /**
   * Get form Id.
   */
  public function getFormId() {
    return 'http451_form';
  }

  /**
   * Get the configurations.
   */
  protected function getEditableConfigNames() {
    return ['http451.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Invoking Form constructor.
    $form = parent::buildForm($form, $form_state);

    // Default parameters.
    $config = \Drupal::config('http451.settings');

    // Get api key if already set.
    $api_key = $config->get('geoip_api_key');

    $form['geoip_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $api_key,
      '#description' => $this->t('Enter your API Key for ipstack'),
      '#required' => TRUE,
    ];

    $form['link'] = [
      '#type' => 'markup',
      '#markup' => '<strong>You can register a new API Key by clicking <a href="https://ipstack.com/product">here</a>.</strong>',
    ];

    return $form;
  }

  /**
   * Validate form inputs.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // TODO Implement validation.
    return;
  }

  /**
   * Form submission callback.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // The Messenger service.
    $values = $form_state->getValues();

    $messenger = \Drupal::messenger();

    \Drupal::configFactory()->getEditable('http451.settings')->set('geoip_api_key', $values['geoip_api_key'])->save();

    $messenger->addStatus($this->t('Your API Key key has been set.'));
  }

}
