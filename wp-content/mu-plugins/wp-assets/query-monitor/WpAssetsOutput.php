<?php

namespace NYCO\QueryMonitor;

class WpAssetsOutput extends \QM_Output_Html {
  /**
   * Constructor. Adds filters to Query Monitor menu for displaying in the panel
   *
   * @param   Object  $collector  Instance of WpAssetsCollector
   *
   * @return  Object              Instance of self (WpAssetsOutput)
   */
  public function __construct($collector) {
    parent::__construct($collector);

    add_filter('qm/output/menus', array($this, 'menus'), 101);
    add_filter('qm/output/menu_class', array($this, 'menuClass'));

    return $this;
  }

  /**
   * Build the output for the Query Monitor table
   *
   * @return  String  The output
   */
  public function output() {
    $data = $this->collector->get_data();

    $integrations = $data['integrations'];

    $headers = array(
      __('Handle'),
      __('Configuration'),
      __('Registered'),
      __('Position'),
      __('Queue')
    );

    $echo = array();

    // phpcs:disable
    $echo[] = '<div class="qm" id="' . esc_attr($this->collector->id()) . '"><table>';

    // Table column header
    $echo[] = '<thead><tr>';
    foreach ($headers as $header) $echo[] = '<th scope="col">' . $header . '</th>';
    $echo[] = '</tr></thead>';

    // Table body
    $echo[] = '<tbody>';
    foreach ($integrations as $i) {
      $echo[] = '<tr class="qm-odd">';

      // Handle column
      $echo[] = '<td class="qm-nowrap qm-ltr">' . $i['handle'] . '</td>';

      // Configuration column
      $echo[] = '<td class="qm-row-caller qm-ltr qm-has-toggle qm-nowrap">';
      $echo[] = '  <button class="qm-toggle" data-on="+" data-off="-" aria-expanded="false" aria-label="Toggle more information">+</button>';
      $echo[] = '  <ol>';
      $echo[] = '    <li>Path: <code>' . ((isset($i['path'])) ? $i['path'] : 'none') . '</code></li>';
      $echo[] = '    <div class="qm-toggled">';
      $echo[] = ((isset($i['dep'])) ? '<li class="qm-info qm-supplemental">Constant Dependency: ' . $i['dep'] . '</li>' : '');
      $echo[] = ((isset($i['localize'])) ? '<li class="qm-info qm-supplemental">Localized constants: <br> - ' . implode('<br> - ', $i['localize']) . '</li>' : '');
      $echo[] = ((isset($i['inline'])) ? '<li class="qm-info qm-supplemental">Inline script: ' . $i['inline']['path'] . '<br> Inline position: ' . $i['inline']['position'] . '</li>' : '');
      $echo[] = ((isset($i['style'])) ? '<li class="qm-info qm-supplemental">Stylesheet: ' . $i['style']['path'] . '</li>' : '');
      $echo[] = ((isset($i['body_open'])) ? '<li class="qm-info qm-supplemental">Body tag: ' . $i['body_open']['path'] . '</li>' : '');
      $echo[] = '    </div>';
      $echo[] = '  </ol>';
      $echo[] = '</td>';

      // Registered Column
      $echo[] = '<td class="qm-row-caller qm-ltr qm-has-toggle qm-nowrap">';
      if ($i['registered']) {
        if ($i['registered']->extra)
          $echo[] = '<button class="qm-toggle" data-on="+" data-off="-" aria-expanded="false" aria-label="Toggle more information">+</button>';

        $echo[] = '<ol>';
        $echo[] = '<li><code>' . $i['registered']->src . '</code></li>';

        // Registered Output
        $echo[] = '<div class="qm-toggled">';

        $extra = $i['registered']->extra;
        if ($extra && isset($extra['after'])) {
          $echo[] = '<pre class="qm-info qm-supplemental">' .
            $extra['after'][1] . '</pre>';
        }

        if ($extra && isset($extra['before'])) {
          $echo[] = '<pre class="qm-info qm-supplemental">' .
            $extra['before'][1] . '</pre>';
        }

        $echo[] = '</div>';
        $echo[] = '</ol>';
      } else {
        $echo[] = 'false';
      }
      $echo[] = '</td>';

      // Position and queue columns
      $echo[] = '<td class="qm-nowrap qm-ltr">' .
        (($i['in_footer']) ? 'footer' : 'head') . '</td>';
      $echo[] = '<td class="qm-nowrap qm-ltr">' .
        (($i['queue'] !== false) ? $i['queue'] : 'false') . '</td>';
      $echo[] = '</tr>';
    }
    $echo[] = '</tbody>';

    // Table footer with the total integration count
    $echo[] = '<tfoot><tr>';
    $echo[] = '<td colspan="' . count($headers) . '">Total: ' .
      count($integrations) . '</td>';
    $echo[] = '</tr></tfoot>';

    $echo[] = '</table></div>';
    // phpcs:enable

    echo implode('', $echo);

    return $echo;
  }

  /**
   * Constructs the menu class
   *
   * @param   Array  $class  Query Monitor menu classes
   *
   * @return  Array          Query Monitor menu classes
   */
  public function menuClass($class) {
    $class[] = 'qm-' . $this->collector->id;

    return $class;
  }

  /**
   * Add menu to panel
   *
   * @param   Array  $menu  Query Monitor menus
   *
   * @return  Array         Query Monitor menus
   */
  public function menus($menus) {
    $menus[] = $this->menu(array(
      'id' => 'qm-' . $this->collector->id,
      'href' => '#qm-' . $this->collector->id,
      'title' => $this->collector->name()
    ));

    return $menus;
  }
}
