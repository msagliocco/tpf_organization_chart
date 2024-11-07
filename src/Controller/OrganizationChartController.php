<?php

namespace Drupal\tpf_organization_chart\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Cache\CacheableMetadata;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;

/**
 * Controller for the TPF Organization Chart.
 */
class OrganizationChartController extends ControllerBase {

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   *  OrganizationChartController constructor.
   *
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   */
  public function __construct(FileUrlGeneratorInterface $file_url_generator) {
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_url_generator')
    );
  }

  /**
   * Builds the custom chart.
   */
  public function content() {
    if (!$this->currentUser()->hasPermission('access tpf organization chart')) {
      throw new AccessDeniedHttpException();
    }

    $config = $this->config('tpf_organization_chart.settings');
    $vid = $config->get('vocabulary');
    $hierarchy_field = $config->get('hierarchy_field');
    $debug_mode = $config->get('debug_mode');
    $chart_type = $config->get('chart_type') ?: 'treegraph';

    $organigram_data = $this->getOrganigramData($vid, $hierarchy_field);
    $root_options = $this->getRootOptions($organigram_data);

    $build = [
      '#theme' => 'tpf_organization_chart',
      '#root_options' => $root_options,
      '#organigram_data' => $organigram_data,
      '#debug_mode' => $debug_mode,
      '#chart_type' => $chart_type,
      '#attached' => [
        'library' => [
          'tpf_organization_chart/tpf_organization_chart',
        ],
        'drupalSettings' => [
          'tpfOrganizationChart' => [
            'organigramData' => $organigram_data,
            'chartType' => $chart_type,
            'labels' => [
              'selectRoot' => $this->t('Select Root'),
              'organigramData' => $this->t('Organigram Data'),
            ],
          ],
        ],
      ],
    ];

    if ($chart_type === 'table') {
      $table_data = $this->buildTableData($organigram_data);
      $build['#theme'] = 'tpf_organization_chart_table';
      $build['#organigram_data'] = $table_data;
    }

    $cache_metadata = new CacheableMetadata();
    $cache_metadata->addCacheTags(['tpf_organization_chart', 'taxonomy_term_list:' . $vid]);
    $cache_metadata->applyTo($build);

    return $build;
  }

  /**
   * Fetches and prepares the Organigram taxonomy data.
   */
  private function getOrganigramData($vid, $hierarchy_field) {
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
    $config = $this->config('tpf_organization_chart.settings');
    $logo_field = $config->get('logo_field');

    $data = [];
    foreach ($terms as $term) {
      $term_entity = Term::load($term->tid);
      $parent_field = $term_entity->get($hierarchy_field)->getValue();
      $parent_id = !empty($parent_field) ? $parent_field[0]['target_id'] : '0';

      // Get logo if the field is configured and has a value
      $logo_url = null;
      if ($logo_field && $term_entity->hasField($logo_field) && !$term_entity->get($logo_field)->isEmpty()) {
        $file = $term_entity->get($logo_field)->entity;
        $logo_url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
      }

      $data[$term->tid] = [
        'id' => (string) $term->tid,
        'name' => htmlspecialchars($term->name),
        'parent' => (string) $parent_id,
        'depth' => null,
        'logo' => $logo_url ? htmlspecialchars($logo_url) : null,
        'description' => $term_entity->getDescription() ? htmlspecialchars(substr($term_entity->getDescription(), 0, 100)) . '...' : null,
      ];
    }

    foreach ($data as &$item) {
      $item['depth'] = $this->calculateDepth($item['id'], $data);
    }

    return array_values($data);
  }

  /**
   * Calculates the depth of a term based on its parents.
   */
  private function calculateDepth($tid, $data, $depth = 0) {
    if ($tid == '0' || !isset($data[$tid])) {
      return $depth;
    }
    return $this->calculateDepth($data[$tid]['parent'], $data, $depth + 1);
  }

  /**
   * Gets the root options for the dropdown.
   */
  private function getRootOptions($organigram_data) {
    $options = [];
    foreach ($organigram_data as $item) {
      if ($item['depth'] <= 3) {
        $prefix = str_repeat('-', $item['depth']);
        $options[$item['id']] = $prefix . ' ' . $item['name'];
      }
    }
    return $options;
  }

  /**
   * Builds the table data for the custom chart.
   */
  private function buildTableData($organigram_data) {
    $table_data = [];
    foreach ($organigram_data as $item) {
      if ($item['parent'] == '0') {
        $table_data[$item['id']] = $item;
        $table_data[$item['id']]['children'] = $this->getChildren($item['id'], $organigram_data);
      }
    }
    return array_values($table_data);
  }

  private function getChildren($parent_id, $organigram_data) {
    $children = [];
    foreach ($organigram_data as $item) {
      if ($item['parent'] == $parent_id) {
        $child = $item;
        $child['children'] = $this->getChildren($item['id'], $organigram_data);
        $children[] = $child;
      }
    }
    return $children;
  }

  /**
   * Gets the page title from configuration.
   *
   * @return string
   *   The page title.
   */
  public function getTitle() {
    $config = $this->config('tpf_organization_chart.settings');
    return $config->get('page_title') ?: $this->t('Organization Chart');
  }

  /**
   * Prepares the term data for display.
   */
  protected function prepareTermData($term) {
    // Get the description value directly from the field
    $description = '';
    if (!$term->get('description')->isEmpty()) {
      $description = $term->get('description')->value;
    }

    return [
      'id' => $term->id(),
      'name' => $term->label(),
      'description' => $description,
      'url' => $term->toUrl()->toString(),
      'image' => $this->getTermImage($term),
      'weight' => $term->getWeight(),
    ];
  }

  /**
   * Displays the organization page.
   *
   * @return array
   *   A render array for the organization page.
   */
  public function organizationPage() {
    return [
      '#theme' => 'tpf_organization_page',
      '#attached' => [
        'library' => [
          'tpf_organization_chart/tpf_organization_chart',
        ],
      ],
    ];
  }

  /**
   * Displays the table view page.
   *
   * @return array
   *   A render array for the table view page.
   */
  public function tableView() {
    if (!$this->currentUser()->hasPermission('access tpf organization chart')) {
      throw new AccessDeniedHttpException();
    }

    $config = $this->config('tpf_organization_chart.settings');
    $vid = $config->get('vocabulary');
    $hierarchy_field = $config->get('hierarchy_field');
    
    // Reuse existing data preparation methods
    $organigram_data = $this->getOrganigramData($vid, $hierarchy_field);
    $table_data = $this->buildTableData($organigram_data);

    $build = [
      '#theme' => 'tpf_organization_chart_table',
      '#organigram_data' => $table_data,
      '#attached' => [
        'library' => [
          'tpf_organization_chart/tpf_organization_chart',
        ],
      ],
    ];

    // Add cache metadata
    $cache_metadata = new CacheableMetadata();
    $cache_metadata->addCacheTags(['tpf_organization_chart', 'taxonomy_term_list:' . $vid]);
    $cache_metadata->applyTo($build);

    return $build;
  }

  /**
   * Displays the tree view page.
   *
   * @return array
   *   A render array for the tree view page.
   */
  public function treeView() {
    return [
      '#theme' => 'tpf_organization_tree_view',
      '#attached' => [
        'library' => [
          'tpf_organization_chart/tpf_organization_chart',
        ],
      ],
    ];
  }

} 