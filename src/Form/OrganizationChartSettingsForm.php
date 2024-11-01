<?php

namespace Drupal\tpf_organization_chart\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Core\Cache\Cache;

/**
 * Configuration form for TPF Organization Chart.
 */
class OrganizationChartSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['tpf_organization_chart.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tpf_organization_chart_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('tpf_organization_chart.settings');

    $form['page_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page Title'),
      '#description' => $this->t('Enter the title for the organization chart page.'),
      '#default_value' => $config->get('page_title') ?: 'Organization Chart',
      '#required' => TRUE,
    ];

    $form['chart_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Chart Type'),
      '#options' => [
        'treegraph' => $this->t('Treegraph'),
        'organization' => $this->t('Organization Chart'),
        'table' => $this->t('Table View'),
      ],
      '#default_value' => $config->get('chart_type') ?: 'treegraph',
    ];

    // Get all vocabularies
    $vocabularies = Vocabulary::loadMultiple();
    $vocab_options = [];
    foreach ($vocabularies as $vid => $vocabulary) {
      $vocab_options[$vid] = $vocabulary->label();
    }

    $form['vocabulary'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Taxonomy'),
      '#options' => $vocab_options,
      '#default_value' => $config->get('vocabulary'),
      '#ajax' => [
        'callback' => '::updateFieldOptions',
        'wrapper' => 'field-wrapper',
      ],
    ];

    $form['field_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'field-wrapper'],
    ];

    $selected_vocab = $form_state->getValue('vocabulary') ?: $config->get('vocabulary');
    if ($selected_vocab) {
      $field_options = $this->getFieldOptions($selected_vocab);
      
      $form['field_wrapper']['hierarchy_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Select Hierarchy Field'),
        '#options' => $field_options,
        '#default_value' => $config->get('hierarchy_field'),
      ];

      // Add logo field selection
      $image_fields = $this->getImageFieldOptions($selected_vocab);
      if (!empty($image_fields)) {
        $form['field_wrapper']['logo_field'] = [
          '#type' => 'select',
          '#title' => $this->t('Select Logo Field'),
          '#description' => $this->t('Select the image field to use for organization logos.'),
          '#options' => $image_fields,
          '#default_value' => $config->get('logo_field'),
          '#empty_option' => $this->t('- None -'),
        ];
      }
    }

    $form['debug_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Debug Mode'),
      '#description' => $this->t('When enabled, the Organigram Data JSON will be displayed below the graph.'),
      '#default_value' => $config->get('debug_mode'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Get field options for a given vocabulary.
   */
  private function getFieldOptions($vid) {
    $fields = \Drupal::entityTypeManager()
      ->getStorage('field_config')
      ->loadByProperties(['entity_type' => 'taxonomy_term', 'bundle' => $vid]);

    $options = [];
    foreach ($fields as $field) {
      $options[$field->getName()] = $field->getLabel();
    }

    return $options;
  }

  /**
   * Get image field options for a given vocabulary.
   */
  private function getImageFieldOptions($vid) {
    $fields = \Drupal::entityTypeManager()
      ->getStorage('field_config')
      ->loadByProperties([
        'entity_type' => 'taxonomy_term',
        'bundle' => $vid,
        'field_type' => 'image',
      ]);

    $options = [];
    foreach ($fields as $field) {
      $options[$field->getName()] = $field->getLabel();
    }

    return $options;
  }

  /**
   * Ajax callback to update field options.
   */
  public function updateFieldOptions(array &$form, FormStateInterface $form_state) {
    return $form['field_wrapper'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('tpf_organization_chart.settings')
      ->set('page_title', $form_state->getValue('page_title'))
      ->set('chart_type', $form_state->getValue('chart_type'))
      ->set('vocabulary', $form_state->getValue('vocabulary'))
      ->set('hierarchy_field', $form_state->getValue('hierarchy_field'))
      ->set('logo_field', $form_state->getValue('logo_field'))
      ->set('debug_mode', $form_state->getValue('debug_mode'))
      ->save();

    // Invalidate cache tags
    Cache::invalidateTags(['tpf_organization_chart']);
    
    // If the vocabulary has changed, invalidate its cache tags as well
    $old_vocabulary = $this->config('tpf_organization_chart.settings')->get('vocabulary');
    if ($old_vocabulary != $form_state->getValue('vocabulary')) {
      Cache::invalidateTags(['taxonomy_term_list:' . $old_vocabulary]);
    }
    Cache::invalidateTags(['taxonomy_term_list:' . $form_state->getValue('vocabulary')]);

    // Rebuild routes and menu
    \Drupal::service('router.builder')->rebuild();
    \Drupal::service('plugin.manager.menu.link')->rebuild();
  } 
} 