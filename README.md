# MockAPI
Simple, RESTful, database-less backend API for developing front-end applications.

This is a simple RESTful API intended for providing mock data during
front-end application development. No database is required - all data
is saved in a text file in JSON format.

MockAPI uses the DataStore class (https://github.com/seelang2/DataStore)
to access and store the data.
 
To install, create a directory on your web server and place the .htaccess,
index.php, class_datastore.php, and schema.php files into the directory.
Ensure that the web server has write privileges to the directory.

A web server compatible with .htaccess files and mod_rewrite enabled is
recommended. However, MockAPI will still work without it, but the URI will
longer be RESTful. Simply add a 'url' parameter to the query string and 
pass the request parameters without a leading slash (/).
 