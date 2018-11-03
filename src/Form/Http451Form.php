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
         * Authority Implementing the censorship
         * Authority Requesting the censorship
         * Custom page title
         * Custom page message
         * -----------------------------------
         *
         *
        */
        // Invoking Form constructor
        $form = parent::buildForm($form, $form_state);

        // Default parameters
        $config = \Drupal::config('http451.settings');
        $default_title = $config->get('page_title');
        $default_content = $config->get('page_content');
        $form['page_id'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Blocked Post ID: '),
            '#description' => $this->t('Enter the ID of the resource you wish to block'),
            '#required' => TRUE,
        ];

        $form['blocked_by'] = [
            '#type' => 'url',
            '#title' => $this->t('URL of Authority Implementing Takedown '),
            '#description' => $this->t('You need to specify the URL of the entity who implemented the takedown.'),
            '#required' => TRUE,
        ];

        $form['blocking_authority'] = [
            '#type' => 'url',
            '#title' => $this->t('URL of Authority: '),
            '#description' => $this->t('You need to specify the URL of the authority who requested the takedown.'),
            '#required' => TRUE,
        ];

        $form['page_title'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Censored page title: '),
            '#default_value' => $default_title,
            '#description' => $this->t('If you wish to use a custom non-standard title to show up on this page, you can set it here.'),
        ];

        $form['page_content'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Reason for censorship: '),
            '#default_value' => $default_content,
            '#description' => $this->t('If you wish to use a custom message to show up on this page, you can set it here.'),
        ];

        $form['save'] = [
            '#type' => 'submit',
            '#value' => $this->t('Block Item')
        ];

        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
        // TODO Implement validation.
        return;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $root_dir = realpath(dirname(__FILE__));
        $filename = 'blocked_ids.json';
        $values = $form_state->getValues();

        // Make sure the directory is writable
        // sudo chown -R www-data:www-data Form
        // Append data to blocked_ids.json
        // TODO Either find a way to change permissions programatically, or
        //  specify to run the above command in the README.
        if(!is_writable("$root_dir")) {
            drupal_set_message($this->t('Error: Please make sure that the module directory is writable. PATH:' . "$root_dir/$filename"), 'error');
            return parent::submitForm($form, $form_state);
        }

        $is_page_already_blocked = FALSE;
        // Check if file exists
        if(file_exists("$root_dir/$filename")) {
            $current_data = file_get_contents("$root_dir/$filename");
            $data_array = json_decode($current_data, TRUE);

            // Check if this page was already blocked before; if so update details.
            foreach($data_array as $node => $attribute) {
                if($attribute['page_id'] == $values['page_id']) {
                    $is_page_already_blocked = TRUE;
                    $data_array[$node] = $values;
                }
            }
        }

        if (!$is_page_already_blocked) {
            $data_array[] = $values;
        }

        $data_array = json_encode($data_array, JSON_PRETTY_PRINT);
        $is_file_write_successful = (bool) file_put_contents("$root_dir/$filename", $data_array);
        if($is_file_write_successful) {
            drupal_set_message($this->t('SUCCESS: Message for blocked resource updated!'), 'status');
            return TRUE;
        } else {
            drupal_set_message($this->t('ERROR: Could not update the page for this blocked resource'), 'error');
        }
    }
}