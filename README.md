# common-api

Helpful abstractions and API utilities for API projects based on the [Symfony](https://www.symfony.com/) framework.

### Configuration
The "hello world" example for configuring this package should be to link the default route to the _RootController_ in _config/routes.yaml_:
```yaml
index:
    path: /
    controller: MisfitPixel\Common\Api\Controller\RootController::root
```

Next, configure the URI and message that will be presented at this root route in _config/services.yaml_:
```yaml
parameters:
  misfitpixel.common.documentation.url: "https://developers.misfitpixel.io"
  misfitpixel.common.documentation.message: "Interested in working with our APIs? Check out our developer docs!"
  ...

services:
...

```

When checking the root route, you should be greeted with the following response:
```json
{
	"version": "1.0.0",
	"documentation_url": "https://developers.misfitpixel.io",
	"message": "Interested in working with our APIs? Check out our developer docs!"
}
```
The version will automatically increment to the highest-available deployed tag on your current branch.

### Middleware

The following optional classes can be added to Symfony's kernel event stack to handle common requirements before the request is handled within the controller.

These can be registered through _config/services.yaml_:
```yaml
parameters:
...
services:
...
  # MIDDLEWARE
  Spoonity\Event\SchemaValidator:
    tags:
      - { name: kernel.event_listener, event: kernel.request, method: execute }
    
  Spoonity\Event\Middleware\CorsHandler:
     tags:
      - { name: kernel.event_listener, event: kernel.request, method: execute, priority: 255 }
      - { name: kernel.event_listener, event: kernel.response, method: response }
...
```

#### CorsHandler
This middleware automatically adds the required CORS headers.

#### SchemaValidator
This utility event allows us to migrate API request schema validation outside the controller body.

Once registered, the _SchemaValidator_ can be triggered by registering a schema file in the _config/schema_validator_ directory.

This file will be used to compare the structure and data types of a request body with an expected schema.
```yaml
root:
  name: 
    type: string
    required: true
    nullable: false

  homepage_url:
    type: string
    required: false
    pattern: /^https?:\/\/(?:www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b(?:[-a-zA-Z0-9()@:%_\+.~#?&\/=]*)$/
```

Based on the above example, we would expect the API request to have two parameters:

* name: a required string, that can't be set to null
* homepage_url, an optional string, that must match a URL

If any of the above requirements are violated, the API will return an error response indicating which field rules were triggered.

The SchemaValidator supports the following field rules:

* type // value type matches a given PHP type
* required // the key _must_ be present in the request
* nullable // the value can be set to a hard NULL
* min // minimum numerical value, or minimum string/array/object size
* max // maximum numerical value, or maximum string/array/object
* pattern // the string value must match the regular expression
* in // the value must match one of a given list of possible options
* schema // the value structure must match a subschema described in this file

and types:

* string
* boolean
* int
* float
* array
* object

If using the `object` type, an optional subschema can be declared within the file and passed as a `schema` rule.  Subschemas can be declared in any order, and are recursively evaluated (subschemas can have their own subschemas).

For example, you could have the following:
```yaml
root:
  name: 
    type: string
    required: true
    nullable: false

  homepage_url:
    type: string
    required: false
    pattern: /^https?:\/\/(?:www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b(?:[-a-zA-Z0-9()@:%_\+.~#?&\/=]*)$/

  home_address:
    type: object
    required: false
    schema: address
    
  shipping_address:
    type: object
    required: false
    schema: address

address:
  street:
    type: string
    required: true
    nullable: false
    
  city:
    type: string
    required: false
    
  region:
    type: string
    required: true
    in: ['AB', 'BC', 'MB', 'NB', 'NL', 'NS', 'ON', 'PE', 'QC', 'SK', 'NT', 'NU', 'YT']
    
  country:
    type: string
    required: true
    in: ['CA']
```

which enforces the requirments for an `address` key, and declares a subschema that the JSON object should follow.

### Handling Requests

This package provides an implementation of Symfony's _AbstractController_, and adds some useful shortcuts for handling common API-response cases like paging and searching.

Controllers should extend _MisfitPixel\Common\Api\Controller\Abstraction\BaseController_.  From here, you will have access to:

* ManagerRegistry
* EventDispatcherInterface
* JwtService
* RequestStack

The BaseController also exposes a `getContent()` method to more quickly consume a raw JSON request body.

Route actions should be handled normally.

### Handling Responses

This packages provides a _JsonResponse_ class which extends from Symfony's _Symfony\Component\HttpFoundation\JsonResponse_ class.

It handles response data much the same way, but adds our automated helpers for handling paging.

Controllers should return a fresh instance of the _JsonResponse_, passing the expected JSON data as an associative array, an HTTP status code, and the Symfony Request object (for exceptions handling).

```php
...
public function myAction(Request $request): Response
{
    ...
    
    return new JsonResponse(['status' => 'success'], Response::HTTP_OK, $request);
}
...
```

If the passed JSON data includes an `items` key, then the response will automatically append the paging data to the response.

```php
...
public function myPagedAction(Request $request): Response
{
    ...
    
    return new JsonResponse(['items' => [['name' => 'John'], ['name' => 'Smith']]], Response::HTTP_OK, $request);
}
...
```

The response returned to the client would then look something like this:
```json
{
	"items": [{
		"name": "John"
	}, {
		"name": "Smith"
	}],
	"paging": {
		"prev": "http://localhost:8888/?limit=x&page=y-1",
		"next": "http://localhost:8888/?limit=x&page=y+1"
	}
}
```

Where the `prev` key represents the current request with (page-1), and the `next` key represents the current request with (page+1). The limit and page numbers are automatically retrieved from the current request context.
