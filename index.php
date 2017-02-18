<?php

/**
 * SLDB: SIMPLE DATABASE STORAGE FOR LSL 1.1
 * Copyright (C) 2009 aubreTEC Labs
 * http://aubretec.com/products/sldb
 *
 * This program is free software. You can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License.
 */

// Include the config file.
require_once('config.php');

// Extract the arguments from the path.
$path = explode('/', strtok($_SERVER['REQUEST_URI'], '?'));
$self = explode('/', $_SERVER['PHP_SELF']);
$hlpvar_01 = array_keys($self);
$args = array_slice($path, end($hlpvar_01));
$args_0 = isset($args[0]) ? $args[0] : "";
$args_1 = isset($args[1]) ? $args[1] : "";

// Break apart the path to find the endpoint.
$action = isset($_REQUEST['action']) ? strtolower($_REQUEST['action']) : strtolower($args_0);
$uuid   = isset($_REQUEST['uuid']) ? $_REQUEST['uuid'] : $args_1;
$fields = isset($_REQUEST['fields']) ? $_REQUEST['fields'] : "";


// Verbose and reverse can be 'true', 'yes', or 1.
$verbose = isset($_REQUEST['verbose']) ? in_array(strtolower($_REQUEST['verbose']), array('yes', 'true', 1)) : FALSE;

// Set defaults for the separators.
$separators = $_REQUEST['separators'];
$separators[0] = empty($separators[0]) ? '&' : $separators[0];
$separators[1] = empty($separators[1]) ? '=' : $separators[1];

// Check authentication.
$REQ_secret = isset($_REQUEST['secret']) ? $_REQUEST['secret'] : "";
if ($action != 'install' && $REQ_secret != $secret) {

	die("ERROR: NOT AUTHENTICATED");
}

// If no key is provided, or the request is a reverse lookup without fields, or
// the request is a put without values, this will fail.
if ($action != 'install' && (empty($uuid) || (empty($fields) && $action != 'read'))) {
	die("ERROR: INSUFFICIENT ARGUMENTS");
}

// Start a new request.
require_once('sldb.php');
$request = new sldbRequest($db_host, $db_user, $db_pass, $db_name, $db_table);

// Take an action; these are all based on the CRUD model.
switch ($action) {
	case 'create':
	case 'update':
		$request->updateData($uuid, $fields, $verbose);
		break;

	case 'read':
		$request->readData($uuid, $fields, $verbose, $separators[1]);
		break;

	case 'delete':
		$request->deleteData($uuid, $fields, $verbose);
		break;

	case 'install':
		$request->createTable();
}

print $request->getOutput($separators[0]);

?>
