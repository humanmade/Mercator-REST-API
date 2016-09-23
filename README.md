# Mercator REST API

REST API for working with domain mappings.

Requires WordPress 4.5+ or the [WP REST API plugin](https://github.com/WP-API/WP-API)

## Usage

You need to include and instantiate the controller:

```
add_action( 'rest_api_init', function() {
    include 'class-rest-api.php';
    $api = new Mercator\REST_API;
    $api->register_routes();
} );
```

The controller adds two API routes:

```
/wp-json/mercator/v1/mappings       // accepts GET, POST
/wp-json/mercator/v1/mappings/:id   // accepts PUT, PATCH, DELETE
```

You can additionally specify a blog ID by passing in a `GET` parameter:

```
?blog_id=1
```

### Data structure

Mappings are very simple. The example JSON object for a mapping is as follows:

```json
{
    "id": 1                       // integer
    "domain": "example.com",      // string
    "active": true                // boolean
}
```

When creating a mapping using `POST` you only need to send the `domain` and `active` values.
