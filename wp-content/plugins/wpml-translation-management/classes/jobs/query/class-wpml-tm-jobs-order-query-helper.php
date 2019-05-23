<?php

class WPML_TM_Jobs_Order_Query_Helper {

	public function get_order( WPML_TM_Jobs_Search_Params $params ) {
		$orders = $this->map_sort_parameters( $params );

		if ( $orders ) {
			return 'ORDER BY ' . implode( ', ', $orders );
		} else {
			return '';
		}
	}

	/**
	 * @param WPML_TM_Jobs_Search_Params $params
	 *
	 * @return array
	 */
	private function map_sort_parameters( WPML_TM_Jobs_Search_Params $params ) {
		$orders = array();
		if ( $params->get_sorting() ) {
			foreach ( $params->get_sorting() as $order ) {
				if ( $order->get_column() === 'language' ) {
					$orders[] = 'source_language_name ' . $order->get_direction();
					$orders[] = 'target_language_name ' . $order->get_direction();
				} else {
					$orders[] = $order->get_column() . ' ' . $order->get_direction();
				}
			}
		}

		return $orders;
	}

}