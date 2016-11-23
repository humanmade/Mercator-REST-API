# Mercator REST API

REST API for working with domain mappings.

Requires WordPress 4.5+ or the [WP REST API plugin](https://github.com/WP-API/WP-API)

## Usage

You need to include and instantiate the controllers:

```php
require_once 'api.php';
```

### Mappings

The mappings controller adds two API routes:

```
/wp-json/mercator/v1/mappings       // accepts GET, POST
/wp-json/mercator/v1/mappings/:id   // accepts PUT, PATCH, DELETE
```

You can additionally specify a blog ID by sending in a parameter:

```
GET
curl http://wordpress.dev/wp-json/mercator/v1/mappings?blog=1

POST, PUT, PATCH, DELETE
curl --data "blog=1" http://wordpress.dev/wp-json/mercator/v1/mappings
```

#### Data structure

Mappings are very simple. The example JSON object for a mapping is as follows:

```
{
    "id": 1                       // integer
    "domain": "example.com",      // string
    "active": true                // boolean
}
```

When creating a mapping using `POST` you should only send the `domain` and `active` values.

### Primary domain

The primary domain controller adds a single route for getting and setting the primary domain.

```
/wp-json/mercator/v1/primary     // accepts GET, POST
```

When `POST`ing to this route you must send a mapping ID:

```
curl --data "mapping=123" http://wordpress.dev/wp-json/mercator/v1/primary
```

The mapping for that ID will made into the site's primary domain provided the user has 
permission and the old primary domain will be converted into a new mapping.
