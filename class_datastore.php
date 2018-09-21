<?php
/**

 The DataStore class is a container to access and store simple 
 collections without the need for a database. The data is saved 
 in JSON format in a text file.

 A collection is a group of resources. Collections are similar to
 database tables. A resource is a group of related fields, similar 
 to the rows in a table.

 DataStore uses an associative array structure to hold the data.

 The schema is defined in PHP using an associative array structure
 and defines each collection's data fields, relationships between
 collections, and resources.

 The DataStore class is a container for the schema and contains 
 methods for accessing the data. The data schema is passed into the 
 class constructor.

 Each collection has two properties:
 schema - contains a list of the collection's data fieldnames, and 
 optional keys for relationships to other collections. The names of 
 related collections are stored as a list in each key.

 resources - associative array of each resource item, keyed by a 
 unique generated index. 

 The collection data will be serialized as JSON and stored in a 
 text file

 Basic data structure (described using JSON):

	{
		"collectionName": {
			"schema": {
				"fields": ["fieldName1","fieldName2","fieldName3"...],
				"has": ["collectionName",...], // optional
				"belongsTo": ["collectionName",...], // optional
				"HABTM": ["collectionName",...] // optional
			},
			"resources": {
				"primaryKey": {
					"fieldName1": "value",
					"fieldName2": "value",
					"fieldName4": "value",
					...
				}
			}
		},
		...
	}

 Collection relationships

 The terms 'has' and 'belongsTo' represent one-to-many and many-to-one
 relationships respectively. These optional keys in the schema contain 
 arrays of collections related to the current collection.

 If the 'belongsTo' key is set, there must be an entry in the fields
 array matching the collection name ending in '.id' listed in the 
 'belongsTo' which  represents the foreign key that contains the primary 
 key of the related resource in the other collection. This value can be
 overridden in settings.

 HABTM (has-and-belongs-to-many) relationships use link tables in 
 databases to connect two tables together. 

 HABTM relationships are not yet implemented.


 */
class DataStore {
	protected $data = [];
	protected $settings = [];
	protected $defaultSettings = [
		'dataFile' => 'datastore.dat',
		'schema' => null,
		'FK_suffix' => '.id'
	];

	public function __construct($settings = []) {
		// get settings and merge with defaults
		$this->settings = array_merge($this->defaultSettings, $settings);
		
		// if the data file doesn't exist, check if schema is passed
		if (!file_exists($this->settings['dataFile'])) {
			// if schema is passed, use it to initialize data
			// and save data file
			if (!empty($this->settings['schema'])) {
				$this->data = $this->settings['schema'];
				$this->writeFile();
			} else {
				// if the file doesn't exist and there was no schema passed
				// we bail with nothing more to do
				throw new Exception('Missing schema data');
			}
		} else {
			// load the data from file
			$this->readFile();
		}
	} // __construct()


	// serializes data and saves to text file
	protected function writeFile() {
		// write to serialized file
		$OUTPUT = serialize($this->data); 
		$fp = fopen($this->settings['dataFile'],"w"); 
		fputs($fp, $OUTPUT); 
		fclose($fp); 
	} // savedata()

	// reads files and deserializes into array
	protected function readFile() {
		// read from serialized file
		$this->data = @unserialize(@file_get_contents($this->settings['dataFile']));
	} // loaddata()





	public function getData() {
		return $this->data;
	} // getData()

	public function collectionExists($collectionName) {
		if (!array_key_exists($collectionName, $this->data)) {
			return false;
		}
		return true;
	} // collectionExists()

	public function getCollection($collectionName) {
		if (!$this->collectionExists($collectionName)) {
			return false;
		} else {
			return $this->data[$collectionName];
		}
	} // getCollection()

	public function getCollectionSchema($collectionName) {
		if (!$this->collectionExists($collectionName)) {
			return false;
		} else {
			return $this->data[$collectionName]['schema'];
		}
	} // getCollection()

	/**
	 * Params:
	 * getRelated - Optional. Array of names of collection(s) to include
	 */
	public function getCollectionResources($collectionName, $params = []) {
		if (!$this->collectionExists($collectionName)) {
			return false;
		}
		// store collection resources in temp array
		$tmpResult = $this->data[$collectionName]['resources'];

		// 'getRelated' is an array of collections to include
		if (!empty($params['getRelated'])) {
			// get related resources and add to temp result set
			$this->getRelatedResources($collectionName, $params['getRelated'], $tmpResult);
		} // if 'getRelated'

		// add PK as 'id' field to final result set and reorder numerically
		$result = [];
		foreach($tmpResult as $key => $row) {
			$row['id'] = $key;
			$result[] = $row;
		}

		return $result;
	} // getCollectionResources()

	protected function getRelatedResources($collectionName, $relatedCollectionArray, &$tmpResult) {
		// 'local' refers to primary collection
		$localSchema = $this->data[$collectionName]['schema'];

		foreach($relatedCollectionArray as $relatedCollection) {
			switch(true) {
				case isset($localSchema['has']) && in_array($relatedCollection, $localSchema['has']):
					// get related collection resources
					// loop through related collection
					foreach($this->data[$relatedCollection]['resources'] as $rPK => $relatedResource) {
						// get local PK value from related FK
						$lPK = $relatedResource[$collectionName.$this->settings['FK_suffix']];
						// create key for related collection in local resource (if not already made)
						if (!isset($tmpResult[$lPK][$relatedCollection])) {
							$tmpResult[$lPK][$relatedCollection] = [];
						}
						// add related resource to local resource
						$tmpResult[$lPK][$relatedCollection][] = $relatedResource;
					}
				break; // 'has'

				case isset($localSchema['belongsTo']) && in_array($relatedCollection, $localSchema['belongsTo']):
					foreach($tmpResult as $key => $localResource) {
						$rPK = $localResource[$relatedCollection.$this->settings['FK_suffix']];

						if (!isset($tmpResult[$key][$relatedCollection])) {
							$tmpResult[$key][$relatedCollection] = [];
						}
						// add related resource to local resource
						$tmpResult[$key][$relatedCollection][] = $this->data[$relatedCollection]['resources'][$rPK];
					}
				break; // 'belongsTo'
			} // switch
		} // foreach relatedCollection

	} // getRelatedResources()

	/**
	 * Params:
	 * getRelated - Optional. Name of collection to include
	 */
	public function getCollectionResourceById($collectionName, $id = null, $params = []) {
		if (!$this->collectionExists($collectionName)) {
			return false;
		}

		if (!array_key_exists($id, $this->data[$collectionName]['resources'])) {
			return false;
		}

		$tmpResult = [];
		$tmpResult[$id] = $this->data[$collectionName]['resources'][$id];

		// check whether to include related collection resources
		if (!empty($params['getRelated'])) {
			// get related resources and add to temp result set
			$this->getRelatedResources($collectionName, $params['getRelated'], $tmpResult);
		}

		$result = $tmpResult[$id];
		$result['id'] = $id;

		return $result;
	} // getCollectionResourceById()

 /**
  * Params:
  * fieldName - Name of field to search by
  * fieldValue - Value to search for
	* getRelated - Optional. Array of names of collection(s) to include
  */
	public function getCollectionResourcesByField($collectionName, $params) {
		if (!$this->collectionExists($collectionName)) {
			return false;
		}

		// store collection resource in temp array using PK as key
		// needs to be an array for getRelatedResources
		$tmpResult = [];

		foreach($this->data[$collectionName]['resources'] as $PK => $resource) {
			if ($resource[$params['fieldName']] == $params['fieldValue']) {
				// found it - assign to result set
				$tmpResult[$PK] = $resource;
			}
		}

		// 'getRelated' is an array of collections to include
		if (!empty($params['getRelated'])) {
			// get related resources and add to temp result set
			$this->getRelatedResources($collectionName, $params['getRelated'], $tmpResult);
		} // if 'getRelated'

		// add PK as 'id' field to final result set and reorder numerically
		$result = [];
		foreach($tmpResult as $key => $row) {
			$row['id'] = $key;
			$result[] = $row;
		}

		return $result;
	} // getCollectionResourcesByField()

	/**
	 * Params:
	 * id - Optional. If id is present, update existing resource instead
	 * of creating a new resource.
	 */
	public function saveResource($collectionName, $data, $id = null) {
		// generate id if not specified
		if (empty($id)) $id = uniqid();

		$tmpRow = [];
		// loop through 'table' fields and map POST data
		foreach($this->data[$collectionName]['schema']['fields'] as $fieldName) {
			$tmpRow[$fieldName] = $data[$fieldName];
		}
		$this->data[$collectionName]['resources'][$id] = $tmpRow;

		// save data
		$this->writeFile();

		return $id; // return generated (or existing) id
	} // saveResource()

	/**
	 * Params:
	 * $collectionName - Name of collection
	 * $id - Resource to delete
	 * $removeRelated - If true, removes resources that belongTo the resource
	 * from other collections
	 */
	public function deleteResource($collectionName, $id, $removeRelated = true) {
		if (!array_key_exists($id, $this->data[$collectionName]['resources'])) {
			return false;
		}
		// remove key
		unset($this->data[$collectionName]['resources'][$id]);

		// does this collection 'has' any collections that belongTo it?
		if ($removeRelated && isset($this->data[$collectionName]['schema']['has'])) {
			// get collection schema and find all 'belongTo' related collections
			foreach($this->data[$collectionName]['schema']['has'] as $relatedCollection) {
				// loop through belongTo collection and remove resources with matching FK
				foreach($this->data[$relatedCollection]['resources'] as $rPK => $resource) {
					if ($resource[$collectionName.$this->settings['FK_suffix']] == $id) {
						unset($this->data[$relatedCollection]['resources'][$rPK]);
						$resource = null;
					}
				}
			}
		}

		// save data
		$this->writeFile();

		return true;
	} // deleteResource()

} // DataStore



