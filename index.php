<?php
/**
 * MockAPI - Database-less API for developing front-end applications
 *
 * This is a simple RESTful API intended for providing mock data during
 * front-end application development. No database is required - all data
 * is saved in a text file in JSON format.
 *
 * MockAPI uses the DataStore class (https://github.com/seelang2/DataStore)
 * to access and store the data.
 * 
 * To install, create a directory on your web server and place the .htaccess,
 * index.php, class_datastore.php, and schema.php files into the directory.
 * Ensure that the web server has write privileges to the directory.
 *
 * A web server compatible with .htaccess files and mod_rewrite enabled is
 * recommended. However, MockAPI will still work without it, but the URI will
 * longer be RESTful. Simply add a 'url' parameter to the query string and 
 * pass the request parameters without a leading slash (/).
 *
 * The following URL request patterns are supported:
 * <baseurl>/collection
 * <baseurl>/collection/id
 * <baseurl>/collection/id/collection
 * <baseurl>/collection/collection
 * 
 * Where <baseurl> is the URL path to you have placed the MockAPI files.
 * For example, if MockAPI is placed into http://dev.domain.com/myproject/api
 * the collections are accessed using:
 *
 * http://dev.domain.com/myproject/api/collection/id and so on.
 *
 * If mod_rewrite is not available, you can pass the request patterns with the
 * 'url' query string parameter as follows:
 *
 * http://dev.domain.com/myproject/api/?url=collection/id
 *
 * Additional parameters may be appended to the query string as usual.
 *
 * @author Chris Langtiw
 * @version 0.3.0
 *
 */

// https://docs.phpdoc.org/guides/docblocks.html

/**
 * Configuration settings
 */

// A factor used for latency simulation (use 0 for no latency)
@define('LATENCY_FACTOR', 0);

// Data file name used by the DataStore class
$datafile = 'mockapi.dat';

/********* No configuration options past this point *********/

require_once('class_datastore.php');

// load mock data
require_once('schema.php');

/**
 * Function declarations
 */

// utility function for debugging
function pr($data) {
	return '<pre>' . print_r($data, true) . '</pre>';
} // pr()

// sets HTTP header
function setResponseCode($code) {
	$message = array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		102 => 'Processing',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-Status',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		306 => 'Switch Proxy',
		307 => 'Temporary Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		//418 => 'I\'m a teapot',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Failed Dependency',
		425 => 'Unordered Collection',
		426 => 'Upgrade Required',
		449 => 'Retry With',
		450 => 'Blocked by Windows Parental Controls',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		506 => 'Variant Also Negotiates',
		507 => 'Insufficient Storage',
		509 => 'Bandwidth Limit Exceeded',
		510 => 'Not Extended'
	);
	
	header("HTTP/1.1 {$code} {$message[$code]}");
} // setResponseCode()

// outputs code
function output($data, $code = 200, $callback = '') {
	$data = json_encode($data);
	
	if (empty($callback)) {
		// set content type header to json
		header('Content-Type: application/json');
	} else {
		// set content type header to script
		header('Content-Type: text/javascript');
		// wrap data in callback function
		$data = $callback.'('.$data.');';
	}

	setResponseCode($code);
	exit($data);
} // output()

function generateID() {
	return uniqid();
} // generateID()

function parseRequestString($requestString) {
	
	$request = str_replace('?'.$_SERVER['QUERY_STRING'], '', str_replace(dirname($_SERVER['PHP_SELF']), '', $requestString));

	// remove leading and trailing slash if any
	$request = ltrim(rtrim($request, '/'), '/');

	// split request into array
	$requestArray = explode('/', $request);

	return $requestArray;
} // parseRequestString()



/**
 * Main program start
 */

// set CORS header to allow all access
header('Access-Control-Allow-Origin: *');

// to simulate latency
if (LATENCY_FACTOR) {
	mt_srand(crc32(microtime()));
	usleep(mt_rand(25000, 300000) * LATENCY_FACTOR);
}

/*
  Retrieve query string parameters
*/

$range = !isset($_GET['range']) ? 50 : $_GET['range'];
$offset = !isset($_GET['offset']) ? 1 : $_GET['offset'];
$callback = empty($_GET['callback']) ? '' : $_GET['callback'];

/*
	If .htaccess or mod_rewrite is not available the request parameters can
	be passed as part of the query string. if $_GET['url'] is present it will
	be used instead of the $_SERVER['REQUEST_URI'] value.
*/

if (!empty($_GET['url'])) {
	// remove any trailing slashes from 
	$requestParams = explode('/', rtrim($_GET['url'], '/'));
} else {
	// if REDIRECT_URL isn't present then this is a bad request
	if (!isset($_SERVER['REDIRECT_URL'])) {
		output(['message'=>'URI not supported'], 400);
	}

	// parse request string from REQUEST_URI
	$requestParams = parseRequestString($_SERVER['REQUEST_URI']);
}

// read request data from standard input into a variable
// ref: http://www.lornajane.net/posts/2008/accessing-incoming-put-data-from-php
parse_str(file_get_contents("php://input"),$REQUEST);

/*
  use pattern-matching approach
  In this approach we will determine the pattern the request implements
  and then apply the appropriate query to the request pattern.
  Note that there are two types of queries possible in patterns CIC and CC,
  depending on whether the tables are related via a link table or not.
  (Link table relationships are currently not implemented.)
*/
// determine pattern - C, CI, CIC, CC
$pattern = '';
// loop through request array
$count = count($requestParams);
for($c = 0; $c < $count; $c++) {
	// is it a collection or an identifier?
	if ($dataset->collectionExists($requestParams[$c])) {
		$pattern .= 'C';
	} else {
		$pattern .= 'I';
	}
}

//echo $pattern;

// take action depending on request method
switch($_SERVER['REQUEST_METHOD']) {
	// invalid request method
	default:
		output(['message'=>'Method not supported'], 405);
	break;
	// return data set as nested array
	case 'GET':
		switch($pattern) {
			default:
				output(['message'=>'URI pattern not supported'], 400);
			break;
			case 'C':
				$result = $dataset->getCollectionResources($requestParams[0]);
				if (!$result) {
					// no key, bail with not found
					output(['message'=>'Invalid collection'], 404);
				}
			break;
			case 'CI':
				$result = $dataset->getCollectionResourceById($requestParams[0], $requestParams[1]);

				if (!$result) {
					// no key, bail with not found
					output(['message'=>'Invalid resource'], 404);
				}
			break;
			case 'CIC':

				$result = $dataset->getCollectionResourceById($requestParams[0], $requestParams[1], ['getRelated' => [$requestParams[2]]]);

				if (!$result) {
					// no key, bail with not found
					output(['message'=>'Invalid resource'], 404);
				}

			break;
			case 'CC':
				$result = $dataset->getCollectionResources($requestParams[0], ['getRelated' => [$requestParams[1]]]);
				if (!$result) {
					// no key, bail with not found
					output(['message'=>'Invalid collection'], 404);
				}
				
			break;
		} // switch
		
		output($result, 200, $callback);
	break; // GET

	case 'POST':
		switch($pattern) {
			default:
				output(['message'=>'URI not supported'], 400);
			break;
			case 'C':
				$id = $dataset->saveResource($requestParams[0], $_POST);
			break;
			case 'CI':
				output(['message'=>'URI not supported'], 400);
			break;
			case 'CIC':
				$id = $dataset->saveResource($requestParams[0], $_POST);
			break;
			case 'CC':
				output(['message'=>'URI not supported'], 400);
			break;
		} // switch
		
		if (!$id) {
			output(['message'=>'Save operation failed'], 417);
		}

		output(array('id' => $id), 201);
	break; // POST

	case 'PUT':
		switch($pattern) {
			default:
				output(['message'=>'URI not supported'], 400);
			break;
			case 'C':
				output(['message'=>'URI not supported'], 400);
			break;
			case 'CI':
				$id = $dataset->saveResource($requestParams[0], $_POST, $requestParams[1]);
			break;
			case 'CIC':
				output(['message'=>'URI not supported'], 400);
			break;
			case 'CC':
				output(['message'=>'URI not supported'], 400);
			break;
		} // switch
		
		if (!$id) {
			output(['message'=>'Save operation failed'], 417);
		}

		output(['message' => 'Ok'], 200);
	break; // PUT

	case 'DELETE':
		switch($pattern) {
			default:
				output(['message'=>'URI not supported'], 400);
			break;
			case 'C':
				output(['message'=>'URI not supported'], 400);
			break;
			case 'CI':

				if ($dataset->deleteResource($requestParams[0], $requestParams[1])) {
					output(['message' => 'Resource not found'], 404);
				}
				
				output(['message' => 'Ok'], 204);
			break;
			case 'CIC':
				output(['message'=>'URI not supported'], 400);
			break;
			case 'CC':
				output(['message'=>'URI not supported'], 400);
			break;
		} // switch
	break; // DELETE

} // switch REQUEST_METHOD




