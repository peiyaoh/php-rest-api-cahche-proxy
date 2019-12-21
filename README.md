# php-rest-api-cache-proxy

This is a one-file PHP script for caching RESTful API that serves responses in JSON format. The script will query the original API for unseen queries and or return cached responses for queries that have been seen before. All the URLs pointing to the original API endpoint will be replaced with URLs pointing to the proxy API to maintain consistency.

Note: I created this script originally to cache results returned from an API for students to practice using Python requests module without overloading the original API server.

## Installation

## Specify the API end point

Step 1: specify the endpoint of the API to cache in the variable, `$base_url`. For instnace,

```php
$base_url = "https://swapi.co/";
```

Step 2: sepcify the proxy API endpoint in two variables, `$sub_folder` and `$cache_url`.

```php
$sub_folder = "\/swproxy\/";
$cache_url = "https://xyz.com".$sub_folder;
```

### Upload the script

### Create two folders

Create tmp and json folders under the same folder as the index.php.

The tmp folder will be used to store log files.

The json folder will be used to store all the responses in JSON files.

## Testing

Make an API query to the proxy API.

For instnace, making a query to the original Starwars API (swapi.co)

```sh

https://swapi.co/api/people/?search=Leia Organa

```

can be replaced with making a query to the proxy api

```sh

https://xyz.com/swproxy/api/people/?search=Leia Organa


```
