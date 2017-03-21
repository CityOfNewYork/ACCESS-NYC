<?php
/*
	Plugin Name: StatCollector
	Description: Collects information from Drools and saved results
	Author:      Blue State Digital
*/

namespace StatCollector;

if (!defined('WPINC')) {
	die; //no direct access
}
require plugin_dir_path( __FILE__ ) . 'settings.php';

add_action( 'drools_request', '\StatCollector\drools_request', 10, 2 );
add_action( 'drools_response', '\StatCollector\drools_response', 10, 2 );
add_action( 'results_sent', '\StatCollector\results_sent', 10, 3 );


function drools_request( $data, $uid ) {
	$db = _get_db();
	$db->insert("requests", [
		"uid" => $uid,
		"data"=>json_encode($data),
	]);
}

function drools_response( $response, $uid ) {
	$db = _get_db();
	$db->insert("responses", [
		"uid" => $uid,
		"data"=>json_encode($response),
	]);
}

function results_sent( $type, $to, $uid ) {
	$db = _get_db();
	$db->insert("messages", [
		"uid" => $uid,
		"msg_type"=>strtolower($type),
		"address"=>$to
	]);
}




function _get_db(){
	if ( empty(get_option('statc_host')) ||
		 empty(get_option('statc_database')) ||
		 empty(get_option('statc_user')) ||
		 empty(get_option('statc_password')) ) {
		error_log("StatCollector is missing database connection information. Cannot log");
		return new MockDb();
	}

	$db = new \wpdb(get_option('statc_user'),
					get_option('statc_password'),
					get_option('statc_database'),
					get_option('statc_host'));
	$db->show_errors();

	if ( ! get_option('statc_bootstrapped') ) {
		__bootstrap( $db );
	}
	return $db;
}

function __bootstrap( $db ){
	$db->query(
		"CREATE TABLE IF NOT EXISTS messages (
			id INT(11) NOT NULL AUTO_INCREMENT,
			uid VARCHAR(13) DEFAULT NULL,
			msg_type VARCHAR(10) DEFAULT NULL,
			address VARCHAR(255) NOT NULL,
			date DATETIME DEFAULT NOW(),
			PRIMARY KEY(id)
		) ENGINE=InnoDB"
	);
	$db->query(
		"CREATE TABLE IF NOT EXISTS requests (
			id INT(11) NOT NULL AUTO_INCREMENT,
			uid VARCHAR(13) DEFAULT NULL,
			data MEDIUMBLOB NOT NULL,
			date DATETIME DEFAULT NOW(),
			PRIMARY KEY(id)
		) ENGINE=InnoDB"
	);
	$db->query(
		"CREATE TABLE IF NOT EXISTS responses (
			id INT(11) NOT NULL AUTO_INCREMENT,
			uid VARCHAR(13) DEFAULT NULL,
			data MEDIUMBLOB NOT NULL,
			date DATETIME DEFAULT NOW(),
			PRIMARY KEY(id)
		) ENGINE=InnoDB"
	);

	update_option('statc_bootstrapped',1);
}

class MockDb {
	public function insert( $table, $args ) {
		return;
	}
	public function query( $q ) {
		return;
	}
}