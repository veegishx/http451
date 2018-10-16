<?php

namespace Drupal\http451\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class Http451Form extends ConfigFormBase {
    public function getFormId() {
        return 'http451_form';
    }

    protected function getEditableConfigNames() {
        return ['http451.settings'];
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        /**
         *
         * List of fields
         * -----------------------------------
         * 
         * Authority Requesting the censorship
         * Custom page title
         * Custom page message
         * -----------------------------------
         * 
         * */
        // Invoking Form constructor
        $form = parent::buildForm($form, $form_state);

        // Default parameters
        $config = \Drupal::config('http451.settings');
        $default_title = $config->get('page_title');
        $default_content = $config->get('page_content');
        $form['content_id'] = array (
            '#type' => 'textfield',
            '#title' => $this->t('Blocked Post ID: '),
            '#description' => $this->t('Enter the ID of the resource you wish to block'),
            '#required' => TRUE,
        );

        $form['blocking_authority'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('URL of Authority: '),
            '#required' => TRUE,
            '#description' => $this->t('You need to specify the URL of the authority who requested the takedown.'),
        );

        $form['page_title'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Censored page title: '),
            '#default_value' => $default_title,
            '#description' => $this->t('If you wish to use a custom non-standard title to show up on this page, you can set it here.'),
        );

        $form['page_content'] = array(
            '#type' => 'textarea',
            '#title' => $this->t('Reason for censorship: '),
            '#default_value' => $default_content,
            '#description' => $this->t('If you wish to use a custom message to show up on this page, you can set it here.'),
        );

        $form['save'] = array (
            '#type' => 'submit',
            '#value' => $this->t('Block Item')
        );

        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
        return;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $root_dir = realpath(dirname(__FILE__));
        $filename = 'blocked_ids.json';
        $id = $form_state->getValue('content_id');
        $authority = $form_state->getValue('blocking_authority');
        $title = $form_state->getValue('page_title');
        $content = $form_state->getValue('page_content');

        // Make sure the directory is writable
        // sudo chown -R www-data:www-data Form 
        // Append data to blocked_ids.json
        if(is_writable("$root_dir")) {
            $current_data = file_get_contents("$root_dir/$filename");
            $data_array = json_decode($current_data, true);
            $data = array(
                "nid" => $id,
                "authority" => $authority,
                "title" => $title,
                "content" => $content,
            );
            $data_array[] = $data;
            $data_array = json_encode($data_array, JSON_PRETTY_PRINT);
            file_put_contents("$root_dir/$filename", $data_array);
            drupal_set_message(t('WARNING: Resource has been successfully blocked!'), 'warning');
            return true;
        } else {
            drupal_set_message(t('Error: Please make sure that the module directory is writable. PATH:' . "$root_dir/$filename"), 'error');
        }
        return parent::submitForm($form, $form_state);
        
    }
}