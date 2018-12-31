<?php

namespace Drupal\http451\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Implements a field type for HTTP451
 *
 * @FieldType(
 *   id = "http451_fieldtype",
 *   label = @Translation("HTTP 451 Status code"),
 *   description = @Translation("Enable censorship"),
 *   category = @Translation("Censorship"),
 *   default_widget = "http451_widget",
 *   default_formatter = "http451_formatter",
 * )
 */
class Http451FieldType extends FieldItemBase {
  
  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'status' => FALSE,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */

  public static function schema(FieldStorageDefinitionInterface $field_definition) {
      return array(
        'columns' => array(
          'status' => array(
            'type' => 'text',
            'not null' => FALSE,
          ),

          'countries_affected' => array(
            'type' => 'text',
            'not null' => FALSE,
          ),

          'blocking_authority' => array(
            'type' => 'text',
            'not null' => FALSE,
          ),

          'page_title' => array(
            'type' => 'text',
            'not null' => FALSE,
          ),

          'page_content' => array(
            'type' => 'text',
            'not null' => FALSE,
          ),
        ),
      );
  }
  
  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
      $properties = [];

      $properties['status'] = DataDefinition::create('string')
      ->setLabel(t('Node censorship status'))
      ->setRequired(FALSE);

      $properties['countries_affected'] = DataDefinition::create('string')
      ->setLabel(t('List of countries affected by this censorship'))
      ->setRequired(FALSE);

      $properties['blocking_authority'] = DataDefinition::create('string')
      ->setLabel(t('URL of Authority'))
      ->setRequired(FALSE);

      $properties['page_title'] = DataDefinition::create('string')
      ->setLabel(t('New Title'))
      ->setRequired(FALSE);

      $properties['page_content'] = DataDefinition::create('string')
      ->setLabel(t('Message'))
      ->setRequired(FALSE);

      return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
      $status = $this->get('status')->getValue();
      $enabled = FALSE;

      if(isset($status)) {
          $enabled = TRUE;
      }

      return !$enabled;
  }
}

?>