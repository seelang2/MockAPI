<?php
/**
 * Data schema file
 *
 * This file defines the data schema and any initial data
 * to be used by the API.
 *
 */


if (file_exists($datafile)):

$dataset = new DataStore(['dataFile' => $datafile]);

else:

// Data file doesn't exist, so define schema and add mock data

$dataset = new DataStore([
	'dataFile' => $datafile,
	'schema' => [
		'customers' => [
			'schema' => [
				'fields' => ['firstname', 'lastname', 'email'],
				'has' => ['orders']
			],
			'resources' => []
		],
		'orders' => [
			'schema' => [
				'fields' => ['customers.id', 'orderdate', 'ordertotal'],
				'belongsTo' => ['customers']
			],
			'resources' => []
		]
	]
]);

// mock data

$customerID = $dataset->saveResource(
	'customers',
	['firstname'=>'John','lastname'=>'Doe','email'=>'jdoe@email.com']
);

$dataset->saveResource(
	'orders',
	['customers.id'=>$customerID,'orderdate'=>'2015/10/13','ordertotal'=>'325.00']
);

$dataset->saveResource(
	'orders',
	['customers.id'=>$customerID,'orderdate'=>'2016/07/21','ordertotal'=>'1287.00']
);

$customerID = $dataset->saveResource(
	'customers',
	['firstname'=>'Alex','lastname'=>'Martin','email'=>'alex.martin@somewhere.com']
);

$dataset->saveResource(
	'orders',
	['customers.id'=>$customerID,'orderdate'=>'2017/01/13','ordertotal'=>'25.00']
);

$dataset->saveResource(
	'orders',
	['customers.id'=>$customerID,'orderdate'=>'2017/04/11','ordertotal'=>'342.00']
);

$dataset->saveResource(
	'orders',
	['customers.id'=>$customerID,'orderdate'=>'2017/06/05','ordertotal'=>'854.00']
);

$dataset->saveResource(
	'orders',
	['customers.id'=>$customerID,'orderdate'=>'2017/09/23','ordertotal'=>'1123.00']
);

$customerID = $dataset->saveResource(
	'customers',
	['firstname'=>'Leroy','lastname'=>'Jenkins','email'=>'gotchicken@email.com']
);

$dataset->saveResource(
	'orders',
	['customers.id'=>$customerID,'orderdate'=>'2013/10/13','ordertotal'=>'56.13']
);

$customerID = $dataset->saveResource(
	'customers',
	['firstname'=>'Miranda','lastname'=>'Gonzales','email'=>'mg23@email.com']
);

$dataset->saveResource(
	'orders',
	['customers.id'=>$customerID,'orderdate'=>'2017/06/12','ordertotal'=>'2344.00']
);

$dataset->saveResource(
	'orders',
	['customers.id'=>$customerID,'orderdate'=>'2017/07/28','ordertotal'=>'1985.00']
);

$customerID = $dataset->saveResource(
	'customers',
	['firstname'=>'Beth','lastname'=>'Allen','email'=>'bethallen@acompany.com']
);

$dataset->saveResource(
	'orders',
	['customers.id'=>$customerID,'orderdate'=>'2017/01/13','ordertotal'=>'345.00']
);

$dataset->saveResource(
	'orders',
	['customers.id'=>$customerID,'orderdate'=>'2017/10/01','ordertotal'=>'614.00']
);

$dataset->saveResource(
	'orders',
	['customers.id'=>$customerID,'orderdate'=>'2017/11/19','ordertotal'=>'456.00']
);

$customerID = $dataset->saveResource(
	'customers',
	['firstname'=>'Chris','lastname'=>'Collins','email'=>'cc123@email.com']
);

$dataset->saveResource(
	'orders',
	['customers.id'=>$customerID,'orderdate'=>'2017/10/03','ordertotal'=>'123.00']
);

$dataset->saveResource(
	'orders',
	['customers.id'=>$customerID,'orderdate'=>'2017/11/07','ordertotal'=>'473.00']
);

$customerID = $dataset->saveResource(
	'customers',
	['firstname'=>'Glenn','lastname'=>'Quagmire','email'=>'giggity@email.com']
);

$dataset->saveResource(
	'orders',
	['customers.id'=>$customerID,'orderdate'=>'2017/01/05','ordertotal'=>'5674.00']
);

$dataset->saveResource(
	'orders',
	['customers.id'=>$customerID,'orderdate'=>'2017/12/31','ordertotal'=>'4545.00']
);

$dataset->saveResource(
	'orders',
	['customers.id'=>$customerID,'orderdate'=>'2018/01/02','ordertotal'=>'2734.00']
);

$customerID = $dataset->saveResource(
	'customers',
	['firstname'=>'Sarah','lastname'=>'Connor','email'=>'terminatrix@email.com']
);

$dataset->saveResource(
	'orders',
	['customers.id'=>$customerID,'orderdate'=>'2016/10/13','ordertotal'=>'2342.00']
);

$dataset->saveResource(
	'orders',
	['customers.id'=>$customerID,'orderdate'=>'2017/7/21','ordertotal'=>'3412.00']
);


endif;