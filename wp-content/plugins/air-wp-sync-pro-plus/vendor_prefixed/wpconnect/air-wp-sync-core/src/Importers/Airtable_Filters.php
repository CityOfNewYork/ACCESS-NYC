<?php
/**
 * Airtable Filters
 *
 * @package wpconnect/air-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Importers;

/**
 * Class Airtable_Filters
 */
class Airtable_Filters {

	/**
	 * Date mode supported.
	 *
	 * @var string[]
	 */
	protected static $date_mode_supported = array(
		'pastWeek',
		'pastMonth',
		'pastYear',
		'nextWeek',
		'nextMonth',
		'nextYear',
		'calendarWeek',
		'calendarMonth',
		'calendarYear',
		'nextNumberOfDays',
		'pastNumberOfDays',
		'exactDate',
		'today',
		'tomorrow',
		'yesterday',
		'oneWeekAgo',
		'oneWeekFromNow',
		'oneMonthAgo',
		'oneMonthFromNow',
		'daysAgo',
		'daysFromNow',
	);

	/**
	 * Return available filters from Airtable fields.
	 *
	 * @param array $fields List of Airtable fields.
	 *
	 * @return array
	 */
	public function get_filters_from_fields( $fields ) {
		$filters = array();

		foreach ( $fields as $field ) {
			// Exclude link to another related fields.
			if ( strpos( $field->id, '__rel__' ) === 0 ) {
				continue;
			}
			$filter = array(
				'name' => $field->name,
				'id'   => $field->id,
			);
			switch ( $field->type ) {
				case 'singleLineText':
				case 'barcode.text':
				case 'multilineText':
				case 'phoneNumber':
				case 'richText':
				case 'url':
				case 'rollup':
					$filter['type'] = 'string';
					if ( 'barcode.text' === $field->type ) {
						$filter['name'] = $this->remove_sub_prop_suffix_from_name( $filter['name'], __( 'Value', 'air-wp-sync-pro-plus' ) );
					}
					break;
				case 'autoNumber':
				case 'count':
				case 'currency':
				case 'duration':
				case 'number':
				case 'percent':
				case 'rating':
					$filter['type'] = 'number';
					break;
				case 'date':
				case 'dateTime':
				case 'createdTime':
				case 'lastModifiedTime':
					$filter['type'] = 'date';
					break;
				case 'singleCollaborator.name':
				case 'createdBy.name':
				case 'lastModifiedBy.name':
					$filter['type'] = 'user';
					$filter['name'] = $this->remove_sub_prop_suffix_from_name( $filter['name'], __( 'Name', 'air-wp-sync-pro-plus' ) );
					break;
				case 'singleSelect':
				case 'multipleSelects':
					$filter['type']    = 'multipleSelects' === $field->type ? 'multi_select' : 'select';
					$filter['options'] = array_map(
						function ( $choice ) {
							return array(
								'value' => $choice->name,
								'label' => $choice->name,
							);
						},
						$field->options->choices
					);
					break;
				case 'multipleRecordLinks':
					$filter['type'] = 'link_to_another_record';
					break;
				case 'multipleAttachments':
					$filter['type'] = 'attachment';
					break;
				case 'checkbox':
					$filter['type'] = 'checkbox';
					break;
			}
			if ( isset( $filter['type'] ) ) {
				$filters[] = $filter;
			}
		}

		return apply_filters( 'air-wp-sync-core-hook-prefix/airtable_filters', $filters, $fields );
	}

	/**
	 * Build formula from selected filters.
	 *
	 * @param array  $filters Selected filters.
	 * @param array  $available_filters Available filters (@see get_filters_from_fields).
	 * @param string $today String date, format: Y-m-d.
	 *
	 * @return string
	 * @throws \Exception "Unknown conjunction".
	 * @throws \Exception "Filter not available anymore".
	 */
	public function build_formula( $filters, $available_filters, $today = '' ) {
		if ( empty( $today ) ) {
			$today = wp_date( 'Y-m-d' );
		}
		if ( empty( $filters['filters'] ) ) {
			return '';
		}
		$available_filters_index = array();
		foreach ( $available_filters as $available_filter ) {
			$available_filters_index[ $available_filter['id'] ] = $available_filter;
		}
		$formula = '';
		switch ( $filters['conjunction'] ) {
			case 'and':
				$formula .= 'AND';
				break;
			case 'or':
				$formula .= 'OR';
				break;
			default:
				throw new \Exception( 'Unknown conjunction' );
		}
		$formula        .= '(';
		$formula_filters = array();
		foreach ( $filters['filters'] as $filter ) {
			// Is subgroup?
			if ( isset( $filter['conjunction'] ) ) {
				$subgroup = $this->build_formula( $filter, $available_filters, $today );
				if ( ! empty( $subgroup ) ) {
					$formula_filters[] = $subgroup;
				}
				continue;
			}

			if ( ! isset( $available_filters_index[ $filter['columnId'] ] ) ) {
				throw new \Exception( 'Filter not available anymore' );
			}
			$filter_type         = $available_filters_index[ $filter['columnId'] ]['type'];
			$escaped_column_name = $this->escape_formula_column_name( $filter['columnName'] );
			if ( 'date' === $filter_type ) {
				$escaped_column_value          = $this->escape_formula_column_value( $filter['value'] ?? '', 'date' );
				$unit                          = '\'days\'';
				list( $start_date, $end_date ) = $this->get_date_caps( $escaped_column_value, $today );
				switch ( $filter['operator'] ) {
					case '=':
						$formula_filters[] = 'IS_SAME(' . $escaped_column_name . ',\'' . $start_date . '\',' . $unit . ')';
						break;
					case '<':
						$formula_filters[] = 'IS_BEFORE(' . $escaped_column_name . ',\'' . $start_date . '\',' . $unit . ')';
						break;
					case '>':
						$formula_filters[] = 'IS_AFTER(' . $escaped_column_name . ',\'' . $start_date . '\',' . $unit . ')';
						break;
					case '<=':
						$start_date = new \DateTime( $start_date );
						$start_date->add( new \DateInterval( 'P1D' ) );
						$start_date        = $start_date->format( 'Y-m-d' );
						$formula_filters[] = 'IS_BEFORE(' . $escaped_column_name . ',\'' . $start_date . '\',' . $unit . ')';
						break;
					case '>=':
						$start_date = new \DateTime( $start_date );
						$start_date->sub( new \DateInterval( 'P1D' ) );
						$start_date        = $start_date->format( 'Y-m-d' );
						$formula_filters[] = 'IS_AFTER(' . $escaped_column_name . ',\'' . $start_date . '\',' . $unit . ')';
						break;
					case '!=':
						$formula_filters[] = 'NOT(IS_SAME(' . $escaped_column_name . ',\'' . $start_date . '\',' . $unit . '))';
						break;
					case 'isWithin':
						$formula_filters[] = 'AND(IS_AFTER(' . $escaped_column_name . ',\'' . $start_date . '\'),IS_BEFORE(' . $escaped_column_name . ',\'' . $end_date . '\'))';
						break;
				}
			} elseif ( 'checkbox' === $filter_type ) {
				switch ( $filter['operator'] ) {
					case '=':
						$formula_filters[] = $escaped_column_name . '=' . ( '1' === $filter['value'] ? 'TRUE()' : 'FALSE()' );
						break;
				}
			} else {
				$value_type = 'string';
				if ( 'number' === $filter_type ) {
					$value_type = 'number';
				} elseif ( in_array( $filter['operator'], array( 'isAnyOf', 'isNoneOf', '|', '&', 'hasNoneOf' ), true ) ) {
					$value_type = 'string[]';
				}

				$escaped_column_value = $this->escape_formula_column_value( $filter['value'] ?? '', $value_type );

				switch ( $filter['operator'] ) {
					case '>':
					case '=':
					case '!=':
					case '>=':
					case '<':
					case '<=':
						$formula_filters[] = $escaped_column_name . $filter['operator'] . $escaped_column_value;
						break;

					case 'contains':
					case 'filename':
						$formula_filters[] = 'FIND(' . $escaped_column_value . ',' . $escaped_column_name . ')';
						break;
					case 'doesNotContain':
						$formula_filters[] = 'FIND(' . $escaped_column_value . ',' . $escaped_column_name . ')=FALSE()';
						break;
					case 'isEmpty':
						$formula_filters[] = $escaped_column_name . '=BLANK()';
						break;
					case 'isNotEmpty':
						$formula_filters[] = 'NOT(' . $escaped_column_name . '=BLANK())';
						break;
					case 'isAnyOf':
						$formula_filters[] = $this->build_formula(
							array(
								'conjunction' => 'or',
								'filters'     => array_map(
									function ( $filter_column_value ) use ( $filter ) {
										return array(
											'operator'   => '=',
											'columnName' => $filter['columnName'],
											'columnId'   => $filter['columnId'],
											'value'      => $filter_column_value,
										);
									},
									$escaped_column_value
								),
							),
							$available_filters,
							$today
						);
						break;
					case 'isNoneOf':
						$formula_filters[] = $this->build_formula(
							array(
								'conjunction' => 'and',
								'filters'     => array_map(
									function ( $filter_column_value ) use ( $filter ) {
										return array(
											'operator'   => '!=',
											'columnName' => $filter['columnName'],
											'columnId'   => $filter['columnId'],
											'value'      => $filter_column_value,
										);
									},
									$escaped_column_value
								),
							),
							$available_filters,
							$today
						);
						break;
					case '|':
						$formula_filters[] = $this->build_formula(
							array(
								'conjunction' => 'or',
								'filters'     => array_map(
									function ( $filter_column_value ) use ( $filter ) {
										return array(
											'operator'   => 'contains',
											'columnName' => $filter['columnName'],
											'columnId'   => $filter['columnId'],
											'value'      => $filter_column_value,
										);
									},
									$escaped_column_value
								),
							),
							$available_filters,
							$today
						);
						break;
					case '&':
						$formula_filters[] = $this->build_formula(
							array(
								'conjunction' => 'and',
								'filters'     => array_map(
									function ( $filter_column_value ) use ( $filter ) {
										return array(
											'operator'   => 'contains',
											'columnName' => $filter['columnName'],
											'columnId'   => $filter['columnId'],
											'value'      => $filter_column_value,
										);
									},
									$escaped_column_value
								),
							),
							$available_filters,
							$today
						);
						break;
					case 'hasNoneOf':
						$formula_filters[] = $this->build_formula(
							array(
								'conjunction' => 'and',
								'filters'     => array_map(
									function ( $filter_column_value ) use ( $filter ) {
										return array(
											'operator'   => 'doesNotContain',
											'columnName' => $filter['columnName'],
											'columnId'   => $filter['columnId'],
											'value'      => $filter_column_value,
										);
									},
									$escaped_column_value
								),
							),
							$available_filters,
							$today
						);
						break;
				}
			}
		}
		$formula .= implode( ', ', $formula_filters );
		$formula .= ')';
		return $formula;
	}

	/**
	 * Escape field value based on "$type" parameter to be used in formula.
	 *
	 * @param mixed  $column_value Column’s value.
	 * @param string $type Value’s type.
	 *
	 * @return array|mixed|string|string[]
	 * @throws \Exception "Unexpected date numberOfDays value, format expected: number".
	 * @throws \Exception "Unexpected date input value, format expected: Y-m-d".
	 * @throws \Exception "Unexpected date mode".
	 */
	public function escape_formula_column_value( $column_value, $type = 'string' ) {
		switch ( $type ) {
			case 'string':
				$column_value = '"' . str_replace( '"', '\"', $column_value ) . '"';
				break;
			case 'date':
				if ( empty( $column_value ) || ! is_array( $column_value ) ) {
					throw new \Exception( 'Unexpected empty or not array value for date filter' );
				}
				$column_value = array_merge(
					array(
						'numberOfDays' => '',
						'input'        => '',
						'mode'         => '',
					),
					$column_value
				);
				if ( ! empty( $column_value['numberOfDays'] ) && ! is_numeric( $column_value['numberOfDays'] ) ) {
					throw new \Exception( 'Unexpected date numberOfDays value, format expected: number' );
				}
				if ( ! empty( $column_value['input'] ) && ! preg_match( '`^\d{4}-\d{2}-\d{2}$`', $column_value['input'] ) ) {
					throw new \Exception( 'Unexpected date input value, format expected: Y-m-d' );
				}
				if ( ! empty( $column_value['mode'] ) && ! in_array( $column_value['mode'], self::$date_mode_supported, true ) ) {
					throw new \Exception( 'Unexpected date mode' );
				}

				break;
			case 'string[]':
				if ( is_string( $column_value ) ) {
					$column_value = array( $column_value );
				} elseif ( ! is_array( $column_value ) ) {
					$column_value = array();
				}
				break;
		}
		return $column_value;
	}

	/**
	 * Escape field name to be used in formula.
	 *
	 * @param string $column_name Column’s name.
	 *
	 * @return string
	 */
	public function escape_formula_column_name( $column_name ) {
		return '{' . $column_name . '}';
	}

	/**
	 * Get start / end date from date filter value.
	 *
	 * @param array  $date_value The date value (['mode' => '...', 'numberOfDays' => '...', 'input' => 'Y-m-d']).
	 * @param string $today String date, format: Y-m-d.
	 *
	 * @return array [ 'Start date as Y-m-d', 'End date as Y-m-d' ]
	 */
	public function get_date_caps( $date_value, $today ) {
		$mode           = $date_value['mode'];
		$number_of_days = $date_value['numberOfDays'];
		$input          = $date_value['input'];
		$start_date     = new \DateTime( $today );
		$end_date       = new \DateTime( $today );
		switch ( $mode ) {
			case 'tomorrow':
				$start_date->add( new \DateInterval( 'P1D' ) );
				$end_date->add( new \DateInterval( 'P1D' ) );
				break;
			case 'yesterday':
				$start_date->sub( new \DateInterval( 'P1D' ) );
				$end_date->sub( new \DateInterval( 'P1D' ) );
				break;
			case 'oneWeekAgo':
				$start_date->sub( new \DateInterval( 'P7D' ) );
				$end_date->sub( new \DateInterval( 'P7D' ) );
				break;
			case 'oneWeekFromNow':
				$start_date->add( new \DateInterval( 'P7D' ) );
				$end_date->add( new \DateInterval( 'P7D' ) );
				break;
			case 'oneMonthAgo':
				$start_date->sub( new \DateInterval( 'P1M' ) );
				$end_date->sub( new \DateInterval( 'P1M' ) );
				break;
			case 'oneMonthFromNow':
				$start_date->add( new \DateInterval( 'P1M' ) );
				$end_date->add( new \DateInterval( 'P1M' ) );
				break;
			case 'daysAgo':
				$start_date->sub( new \DateInterval( 'P' . $number_of_days . 'D' ) );
				$end_date->sub( new \DateInterval( 'P' . $number_of_days . 'D' ) );
				break;
			case 'daysFromNow':
				$start_date->add( new \DateInterval( 'P' . $number_of_days . 'D' ) );
				$end_date->add( new \DateInterval( 'P' . $number_of_days . 'D' ) );
				break;
			case 'exactDate':
				$start_date = new \DateTime( $input );
				$end_date   = new \DateTime( $input );
				break;

			case 'pastWeek':
				$start_date->sub( new \DateInterval( 'P7D' ) );
				$end_date->add( new \DateInterval( 'P1D' ) );
				break;
			case 'pastMonth':
				$start_date->sub( new \DateInterval( 'P1M' ) );
				$end_date->add( new \DateInterval( 'P1D' ) );
				break;
			case 'pastYear':
				$start_date->sub( new \DateInterval( 'P1Y' ) );
				$end_date->add( new \DateInterval( 'P1D' ) );
				break;

			case 'nextWeek':
				$start_date->sub( new \DateInterval( 'P1D' ) );
				$end_date->add( new \DateInterval( 'P7D' ) );
				break;
			case 'nextMonth':
				$start_date->sub( new \DateInterval( 'P1D' ) );
				$end_date->add( new \DateInterval( 'P1M' ) );
				break;
			case 'nextYear':
				$start_date->sub( new \DateInterval( 'P1D' ) );
				$end_date->add( new \DateInterval( 'P1Y' ) );
				break;
			case 'calendarWeek':
				$week       = get_weekstartend( $today );
				$start_date = ( new \DateTime() )->setTimestamp( $week['start'] );
				$start_date->sub( new \DateInterval( 'P1D' ) );
				$end_date = ( new \DateTime() )->setTimestamp( $week['end'] );
				$end_date->add( new \DateInterval( 'P1D' ) );
				break;
			case 'calendarMonth':
				$start_date = new \DateTime( $start_date->format( 'Y-m-01' ) );
				$start_date->sub( new \DateInterval( 'P1D' ) );
				$end_date = new \DateTime( $end_date->format( 'Y-m-' ) . str_pad( $end_date->format( 't' ), 2, '0' ) );
				$end_date->add( new \DateInterval( 'P1D' ) );
				break;
			case 'calendarYear':
				$start_date = new \DateTime( ( (int) $start_date->format( 'Y' ) - 1 ) . '-12-31' );
				$end_date   = new \DateTime( ( (int) $end_date->format( 'Y' ) + 1 ) . '-01-01' );
				break;
			case 'nextNumberOfDays':
				$start_date->sub( new \DateInterval( 'P1D' ) );
				$end_date->add( new \DateInterval( 'P' . ( $number_of_days + 1 ) . 'D' ) );
				break;
			case 'pastNumberOfDays':
				$start_date->sub( new \DateInterval( 'P' . ( $number_of_days + 1 ) . 'D' ) );
				$end_date->add( new \DateInterval( 'P1D' ) );
				break;
		}
		return array(
			$start_date->format( 'Y-m-d' ),
			$end_date->format( 'Y-m-d' ),
		);
	}

	/**
	 * Some Airtable fields have sub props like name, value...
	 * We add a suffix to the field name in that case, this function allows to remove it.
	 *
	 * @param string $field_name Field's name with suffix.
	 * @param string $suffix_label Suffix label.
	 *
	 * @return string Field's name without suffix.
	 */
	public function remove_sub_prop_suffix_from_name( $field_name, $suffix_label ) {
		$suffix_label = ' (' . $suffix_label . ')';
		if ( mb_substr( $field_name, mb_strlen( $field_name ) - mb_strlen( $suffix_label ), mb_strlen( $suffix_label ) ) === $suffix_label ) {
			$field_name = mb_substr( $field_name, 0, mb_strlen( $field_name ) - mb_strlen( $suffix_label ) );
		}
		return $field_name;
	}
}
