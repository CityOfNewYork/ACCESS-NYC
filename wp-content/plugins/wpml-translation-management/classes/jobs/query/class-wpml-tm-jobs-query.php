<?php

interface WPML_TM_Jobs_Query {
	/**
	 * @param WPML_TM_Jobs_Search_Params $params
	 *
	 * @return string
	 */
	public function get_data_query( WPML_TM_Jobs_Search_Params $params );

	/**
	 * @param WPML_TM_Jobs_Search_Params $params
	 *
	 * @return int
	 */
	public function get_count_query( WPML_TM_Jobs_Search_Params $params );
}
