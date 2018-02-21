<?php
/**
 * FlipperCode_Database class file.
 * @author Flipper Code <hello@flippercode.com>
 * @package Core
 */

if ( ! class_exists( 'FlipperCode_Database' ) ) {

	/**
	 * Class : FlipperCode_Database
	 * @author Flipper Code <hello@flippercode.com>
	 * @package Core
	 */
	class FlipperCode_Database {
		/**
		 * Connection reference.
		 * @var [type]
		 */
		public $connection;
		/**
		 * Intialize connection string.
		 */
		function __construct() {

			global $wpdb;
			$this->connection = $wpdb;
		}
		/**
		 * Connect to database.
		 * @return string Connection reference.
		 */
		public static function connect() {

			global $wpdb;
			return $wpdb;
		}
		/**
		 * Read query over connection.
		 * @param  string $query      SQL Query.
		 * @param  string $connection Connection String.
		 * @return object             Records cursor.
		 */
		public static function reader($query, $connection) {

			$cursor = $connection->get_results( $query );
			return $cursor;
		}
		/**
		 * Execute delete query.
		 * @param  string $query      SQL Query.
		 * @param  string $connection Connection String.
		 * @return boolean             True or False.
		 */
		public static function non_query($query, $connection) {

			$result = $connection->query( $query );

			if ( 0 == $result or 'FALSE' == $result ) {
				return false;
			}

			return $result;
		}
		/**
		 * Insert or Update records
		 * @param  string $table Table Name.
		 * @param  array  $data   Data array.
		 * @param  string $where Condition.
		 * @return int        Insert ID or Update Status.
		 */
		public static function insert_or_update($table, $data, $where = '') {

			global $wpdb;
			$wpdb->show_errors();
			if ( ! is_array( $where ) ) {
				 $wpdb->insert( $table, $data );
				 $result = $wpdb->insert_id;
				return $result;
			} else { $result = $wpdb->update( $table, $data, $where );
				return $result;
			}

		}
	}
}
