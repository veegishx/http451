<?php

namespace Drupal\http451\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;

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

    // Save the custom field machine name to allow other functions to access it for DB queries 
    \Drupal::configFactory()->getEditable('http451.settings')->set('http451.custom_field_name', $this->fieldDefinition->getName())->save();

    $element['#uid'] = Html::getUniqueId('http451-' . $this->fieldDefinition->getName());

    $element += array(
        '#type' => 'fieldset',
    );

    $element['status'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Enable censorship'),
        '#default_value' => isset($items[$delta]->status) ? $items[$delta]->status : 0,
        '#return_value' => 1,
        '#description' => $this->t('Click to enable or disable censorship of this node'),
    );

    $element['page_title'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Censored page title: '),
        '#default_value' => isset($items[$delta]->page_title) ? $items[$delta]->page_title : '451: Unavailable For Legal Reasons',
        '#description' => $this->t('If you wish to use a custom non-standard title to show up on this page, you can set it here.'),
    );
        
    $element['blocking_authority'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('URL of Authority Implementing Takedown: '),
        '#required' => FALSE,
        '#default_value' => isset($items[$delta]->blocking_authority) ? $items[$delta]->blocking_authority : NULL,
        '#description' => $this->t('You need to specify the URL of the entity who implemented the takedown.'),
    );

    $element['page_content'] = array(
        '#type' => 'textarea',
        '#title' => $this->t('Reason for censorship: '),
        '#default_value' => isset($items[$delta]->page_content) ? $items[$delta]->page_content : '<html><head><title>Unavailable For Legal Reasons</title></head><body><h1>451: Unavailable For Legal Reasons</h1><p>This request may not be serviced in the Roman Province of Judea due to the Lex Julia Majestatis, which disallows access to resources hosted on servers deemed to be operated by the People\'s Front of Judea.</p></body></html>',
        '#description' => $this->t('If you wish to use a custom message to show up on this page, you can set it here.'),
    );

    return $element;
  }
}
  

?>