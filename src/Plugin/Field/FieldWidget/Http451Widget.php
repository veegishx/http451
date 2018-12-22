<?php

namespace Drupal\http451\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements a field widget for HTTP451
 *
 * @FieldWidget(
 *   id = "http451_widget",
 *   label = @Translation("HTTP451 Widget"),
 *   field_types = {
 *     "http451_fieldtype",
 *   }
 * )
 */

class Http451Widget extends WidgetBase implements WidgetInterface {
    /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node instanceof \Drupal\node\NodeInterface) {
    // You can get nid and anything else you need from the node object.
        $nid = $node->id();
    }



    $element += array(
        '#type' => 'fieldset',
    );

    $config = \Drupal::config('http451.settings');

    $element['status'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Enable censorship'),
        '#description' => $this->t('Click to enable or disable censorship of this node'),
    );

    $element['page_title'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Censored page title: '),
        '#default_value' => 'Error 451: Unavailable For Legal Reasons',
        '#description' => $this->t('If you wish to use a custom non-standard title to show up on this page, you can set it here.'),
    );
        
    $element['blocking_authority'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('URL of Authority Implementing Takedown: '),
        '#required' => FALSE,
        '#description' => $this->t('You need to specify the URL of the entity who implemented the takedown.'),
    );

    $element['page_content'] = array(
        '#type' => 'textarea',
        '#title' => $this->t('Reason for censorship: '),
        '#default_value' => '<html><head><title>Unavailable For Legal Reasons</title></head><body><h1>Unavailable For Legal Reasons</h1><p>This request may not be serviced in the Roman Province of Judea due to the Lex Julia Majestatis, which disallows access to resources hosted on servers deemed to be operated by the People\'s Front of Judea.</p></body></html>',
        '#description' => $this->t('If you wish to use a custom message to show up on this page, you can set it here.'),
    );
    
    return $element;
    
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $messenger = \Drupal::messenger();

    $form_dir = realpath(__DIR__ . DIRECTORY_SEPARATOR . '../../../Form');
    $filename = 'blocked_ids.json';
    $values = $form_state->getValues();

    if(!is_writable("$form_dir")) {
        $messenger->addError($this->t('Error: Please make sure that the module directory is writable. PATH:' . "$root_dir/$filename"));
    }
    $is_page_already_blocked = FALSE;
    // Check if file exists
    if(file_exists("$form_dir/$filename")) {
        $current_data = file_get_contents("$form_dir/$filename");
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
    $is_file_write_successful = (bool) file_put_contents("$form_dir/$filename", $data_array);
    if($is_file_write_successful) {
        $messenger->addStatus($this->t('SUCCESS: Message for blocked resource updated!'));
        return TRUE;
    } else {
        $messenger->addError($this->t('ERROR: Could not update the page for this blocked resource'));
    }
  }

}
  

?>