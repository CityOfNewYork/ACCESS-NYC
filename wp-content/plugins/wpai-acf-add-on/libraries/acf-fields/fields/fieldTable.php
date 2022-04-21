<?php

namespace wpai_acf_add_on\acf\fields;

use wpai_acf_add_on\acf\ACFService;

/**
 * Class FieldTable
 * @package wpai_acf_add_on\acf\fields
 */
class FieldTable extends Field {

    /**
     *  Field type key
     */
    public $type = 'table';

	/**
	 * @var string
	 */
	public $mode = 'csv';

	/**
	 * @var string
	 */
	public $row_delimiter = '|';

	/**
	 * @var string
	 */
	public $cell_delimiter = ',';

	/**
	 * @var bool
	 */
	public $ignoreEmpties = false;

	/**
	 * @var int
	 */
	public $rowIndex = 0;

	/**
	 * @var []
	 */
	public $headers = [];

	/**
	 * @return string
	 */
	public function getMode() {
		return $this->mode;
	}

	/**
	 * @param string $mode
	 */
	public function setMode( $mode ) {
		$this->mode = $mode;
	}

	/**
	 * @return string
	 */
	public function getRowDelimiter() {
		return $this->row_delimiter;
	}

	/**
	 * @return string
	 */
	public function getCellDelimiter() {
		return $this->cell_delimiter;
	}

	/**
	 * @param string $delimiter
	 */
	public function setRowDelimiter( $delimiter ) {
		$this->row_delimiter = $delimiter;
	}

	/**
	 * @param string $delimiter
	 */
	public function setCellDelimiter( $delimiter ) {
		$this->cell_delimiter = $delimiter;
	}

	/**
	 * @return string
	 */
	public function getRowIndex() {
		return $this->rowIndex;
	}

	/**
	 * @param string $index
	 */
	public function setRowIndex( $index ) {
		$this->rowIndex = $index;
	}

	/**
	 * @return boolean
	 */
	public function isIgnoreEmpties() {
		return $this->ignoreEmpties;
	}

	/**
	 * @param boolean $ignoreEmpties
	 */
	public function setIgnoreEmpties( $ignoreEmpties ) {
		$this->ignoreEmpties = $ignoreEmpties;
	}

	/**
	 * @return mixed
	 */
	public function get_headers() {
		return $this->headers;
	}

	/**
	 * @param mixed $headers
	 */
	public function set_headers( $headers ) {
		$this->headers = $headers;
	}

	/**
     *
     * Parse field data
     *
     * @param $xpath
     * @param $parsingData
     * @param array $args
     */
    public function parse( $xpath, $parsingData, $args = array() ) {
        parent::parse( $xpath, $parsingData, $args );
	    // Remove table row template.
	    if ( isset($xpath['rows']['ROWNUMBER']) ) {
		    unset($xpath['rows']['ROWNUMBER']);
	    }

	    if ( ! empty( $xpath['rows'] ) ) {

		    $values = array();

		    $is_ignore_empties = empty($xpath['is_ignore_empties']) ? false : true;

		    $this->setIgnoreEmpties( $is_ignore_empties );
		    $this->setRowDelimiter( $xpath['row_separator'] );
		    $this->setCellDelimiter( $xpath['cell_separator'] );

		    if (!empty($xpath['use_headers']) && !empty($xpath['headers'])) {
			    $headers = $this->getByXPath( $xpath['headers'], $args['xpath_suffix'] . $this->getOption('field_path') );
			    $this->set_headers($headers);
		    }

		    switch ( $xpath['is_variable'] ) {
			    case 'yes':
				    $rowFields = array_shift($xpath['rows']);
				    $this->setMode('xml');
				    for ($k = 0; $k < $this->getOption('count'); $k++) {

					    $repeaterXpath = '[' . ($k + 1) . ']/' . ltrim(trim($xpath['foreach'], '{}!'), '/');
					    $file = false;
					    $repeaterRows = \XmlImportParser::factory($this->parsingData['xml'], $this->getOption('base_xpath') . $repeaterXpath, "{.}", $file)->parse();
					    @unlink($file);

					    $xpath_suffix = '';
					    if ( ( ! isset($rowFields[ $this->getFieldKey() ]) || ( is_array( $rowFields[ $this->getFieldKey() ] ) || strpos( $rowFields[ $this->getFieldKey() ], "!" ) !== 0 ) ) && strpos( $xpath['foreach'], "!" ) !== 0 ) {
						    $xpath_suffix = $this->getOption( 'base_xpath' ) . $repeaterXpath;
						    $xpath_suffix = str_replace( $parsingData['xpath_prefix'] . $parsingData['import']->xpath, '', $xpath_suffix );
					    }

					    $rowData = [];
					    $cells = explode($this->getCellDelimiter(), $rowFields);
					    $cells = array_map('trim', $cells);
					    if (!empty($cells)) {
					    	foreach ($cells as $cell) {
							    $rowData[] = $this->getByXPath( $cell, $xpath_suffix );
						    }
					    }

					    $values[] = array(
						    'countRows' => count($repeaterRows),
						    'fields' => $rowData
					    );
				    }
				    break;
			    default:
				    switch ( $xpath['is_variable'] ) {
					    case 'csv':
					    	$rowFields = array_shift($xpath['rows']);
						    if (!empty($rowFields)) {
							    $data = $this->getByXPath( $rowFields, $args['xpath_suffix'] . $this->getOption('field_path') );
								foreach ( $data as $index => $item ) {
									$indexValues = [];
									$rows = explode($this->getRowDelimiter(), $item);
									$rows = array_map('trim', $rows);
									if ( ! empty($rows) ) {
										foreach ($rows as $row) {
											$rowData = [];
											$cells = explode($this->getCellDelimiter(), $row);
											$cells = array_map('trim', $cells);
											if ( ! empty($cells) ) {
												foreach ( $cells as $cell ) {
													$rowData[] = $cell;
												}
											}
											$indexValues[] = $rowData;
										}
									}
									$values[] = $indexValues;
								}
						    }
						    break;
					    default:
						    $this->setMode( 'fixed' );
						    foreach ( $xpath['rows'] as $key => $rowFields ) {
							    $data = $this->getByXPath( $rowFields, $args['xpath_suffix'] . $this->getOption('field_path') );
							    foreach ( $data as $index => $item ) {
								    $rowData = [];
								    $cells = explode($this->getCellDelimiter(), $item);
								    $cells = array_map('trim', $cells);
								    if ( ! empty($cells) ) {
									    foreach ( $cells as $cell ) {
										    $rowData[] = $cell;
									    }
								    }
								    $values[$index][] = $rowData;
							    }
						    }
						    break;
				    }
				    break;
		    }
		    $this->setOption( 'values', $values );
	    }

    }

    /**
     * @param $importData
     * @param array $args
     * @return mixed
     */
    public function import( $importData, $args = array() ) {
        $isUpdated = parent::import( $importData, $args );
        if ( ! $isUpdated ) {
            return FALSE;
        }

	    $values = $this->getOption('values');

	    if (!empty($values)) {

	    	$headers = FALSE;
		    if (!empty($this->get_headers())) {
				$headers = $this->get_headers()[$this->getPostIndex()];
				if (!empty($headers)) {
					$headers = explode(",", $headers);
					$headers = array_map('trim', $headers);
				}
		    }
	    	$table = [
	    		'p' => [
	    			'o' => [
	    				'uh' => empty($headers) ? 0 : 1,
				    ],
				    'ca' => ''
			    ],
			    'c' => [],
			    'h' => [],
			    'b' => []
		    ];

		    switch ($this->getMode()) {
			    case 'xml':
				    $columns_count = count($values[$this->getPostIndex()]['fields']);
				    $table['c'] = array_fill(0, $columns_count, [ 'p' => '' ]);
				    $table['h'] = array_fill(0, $columns_count, [ 'c' => '' ]);
				    if (!empty($headers)) {
				    	foreach ($headers as $i => $header) {
						    $table['h'][$i]['c'] = $header;
					    }
				    }
				    for ($k = 0; $k < $values[$this->getPostIndex()]['countRows']; $k++) {
					    $row_data = [];
				    	foreach ($values[$this->getPostIndex()]['fields'] as $column) {
						    $row_data[] = [
							    'c' => $column[$k]
						    ];
					    }
					    $table['b'][] = $row_data;
				    }
				    ACFService::update_post_meta($this, $this->getPostID(), $this->getFieldName(), $this->filterTable($table));
				    break;
			    case 'csv':
			    case 'fixed':
			        $values = $values[$this->getPostIndex()];
			    	// Calculate maximum count of columns.
					$columns_count = 0;
				    foreach ($values as $row) {
				    	if ($columns_count < count($row)) {
				    		$columns_count = count($row);
					    }
				    }
				    $table['c'] = array_fill(0, $columns_count, [ 'p' => '' ]);
				    $table['h'] = array_fill(0, $columns_count, [ 'c' => '' ]);
				    if (!empty($headers)) {
					    foreach ($headers as $i => $header) {
						    $table['h'][$i]['c'] = $header;
					    }
				    }
				    foreach ($values as $row) {
					    $row_data = [];
					    foreach ($row as $cell) {
						    $row_data[] = [
							    'c' => $cell //[$this->getPostIndex()]
						    ];
					    }
					    // Prefill missing cells with empty values.
					    if (count($row_data) < $columns_count) {
					    	do {
							    $row_data[] = [
								    'c' => ''
							    ];
						    } while (count($row_data) < $columns_count);
					    }
					    $table['b'][] = $row_data;
				    }
				    ACFService::update_post_meta($this, $this->getPostID(), $this->getFieldName(), $this->filterTable($table));
				    break;
			    default:
				    break;
		    }
	    }
    }

	/**
	 * @param $table
	 * @return bool
	 */
	protected function filterTable($table){
		$isImportRow = $this->isIgnoreEmpties() ? false : true;
		if (!$isImportRow) {
			$filtered_rows = [];
			foreach ($table['b'] as $i => $row) {
				$empty_cells = 0;
				foreach ($row as $cell) {
					if ($cell['c'] == '') {
						$empty_cells++;
					}
				}
				// Import only rows with data.
				if ($empty_cells != count($row)) {
					$filtered_rows[] = $row;
				}
			}
			$table['b'] = $filtered_rows;
		}
		return $table;
	}
}