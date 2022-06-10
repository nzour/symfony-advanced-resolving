# Symfony advanced resolving</h1>

### Requirements
- php >= 8.0
- symfony >= 5.3

### Installation

```
composer require nzour/symfony-advanced-resolving
```

**Important**: Make sure bundle `\AdvancedResolving\AdvancedResolvingBundle` become enabled, otherwise enable it in `bundles.php`

### Examples

#1 Using **#[FromQuery]** attribute:
```php
#[AsController, Route(path: '/foo')]
final class FooController
{
    #[Route(methods: ['GET'])]
    public static function index(#[FromQuery] string $value): JsonResponse
    {
        return new JsonResponse([
            'value' => $value,
        ]);
    }
}
```

`GET {host}/foo?value={value}`

---

#2 Using **#[FromQuery]** attribute with different param name:
```php
#[AsController, Route(path: '/foo')]
final class FooController
{
    #[Route(methods: ['GET'])]
    public static function index(
        #[FromQuery(paramName: 'foobar')] string $value,
        #[FromQuery] int $score,
    ): JsonResponse {
        return new JsonResponse([
            'value' => $value,
            'score' => $score,
        ]);
    }
}
```

`GET {host}/foo?foobar={value}&score={score}`

---

#3 Using **#[FromQuery]** with class typehint

```php
final class FooQuery
{
    public function __construct(
        public string $value,
        public int $score,
    ) {
    }
}

#[AsController, Route(path: '/foo')]
final class FooController
{
    #[Route(methods: ['GET'])]
    public static function index(#[FromQuery] FooQuery $fooQuery): JsonResponse {
        return new JsonResponse($fooQuery);
    }
}
```

`GET {host}/foo?value={value}&score={score}`

_Note_: it's not possible to rename properties of **FooQuery** via additional attributes

---

#4 Using **#[FromBody]**
```php
final class FooCommand
{
    public function __construct(
        public string $foobar,
        public int $barfoo,
    ) {
    }
}

#[AsController, Route(path: '/foo')]
final class FooController
{
    #[Route(methods: ['POST'])]
    public static function command(#[FromBody] FooCommand $command): JsonResponse
    {
        return new JsonResponse([
            'command' => $command,
        ]);
    }
}
```

### Docs

Feature based on **Symfony's ArgumentValueResolver** flow, therefore make sure your controllers tagged with `controller.service_arguments` or marked via `#[AsController]` attribute. 

##### Built in
- `bin/console debug:meta-resolvers` - View list of defined meta resolvers
- **FromBody**

  Implementation class [FromBodyMetaResolver](https://github.com/nzour/symfony-advanced-resolving/blob/master/src/Core/Resolver/FromBodyMetaResolver.php)
  
  Uses symfony serializer to instantiate objects from plain data, default format of data is **json**.
  There is a way to specify another format: `#[FromBody(format: XmlEncoder::FORMAT)]`.
  It is not possible to change format globally.

- **FromQuery**

  Implementation class [FromQueryMetaResolver](https://github.com/nzour/symfony-advanced-resolving/blob/master/src/Core/Resolver/FromQueryMetaResolver.php)
  
  You can specify different param name `#[FromQuery(paramName: 'foobar')]`
  
  Parameter **FromQuery::$disableTypeEnforcement** is responsible for flag **AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT** when resolve object.
  It's value **true** by default.

##### Not implemented yet
- FromHeader
- FromForm

##### Errors
- [Symfony Serializer's Exceptions](https://symfony.com/doc/current/components/serializer.html)
- [NonNullableArgumentWithNoDefaultValueFromQueryParamsException](https://github.com/nzour/symfony-advanced-resolving/blob/master/src/Core/Exception/NonNullableArgumentWithNoDefaultValueFromQueryParamsException.php) - Only for **#[FromQuery]**. Argument is not nullable, has no default value and there is also no value specified from request
- [CouldNotCreateInstanceFromQueryParamsException](https://github.com/nzour/symfony-advanced-resolving/blob/master/src/Core/Exception/CouldNotCreateInstanceFromQueryParamsException.php) - Just like above, but if argument's typehint is class

##### Extend by user land
If you want to create your own attribute and algoritm that resolves it, follow steps:
- Define your attribute or use existing attribute
- Define class-service, that implements **MetaResolverInterface**
- Method **supportedAttribute** should return **class-string** of which attribute it supports
- Mark your service with tag `meta-resolver`
 
### Limitations

- Built in resolvers work with [Symfony Serializer](https://symfony.com/doc/current/components/serializer.html)
- No interop with [Symfony Validator](https://symfony.com/doc/current/components/validator.html) (yet, or maybe not yet)
- Built in attributes work only with endpoint's parameters, it's not possible to combine attributes:
  ```php
  final class FooDto
  {
      public function __construct(
         #[FromQuery]
         public string $foobar,
         public int $barfoo,
     ) {
     }
  }

  #[AsController]
  final class FooController
  {
      #[Route(path: '/foo', methods: ['POST'])
      public function index(#[FromBody] FooDto $dto): void
      {
       // ...
      }
  }
  ```
  The entire class **FooDto** would be compiled from Body parameters.

  However property **FooDto::$foobar** is marked with attribute **#[FromQuery]**, resolver would not try to find parameter **foobar** inside query-parameters and try set it as property's value.


### Inspired by

- [Model Binding in ASP.NET Core](https://docs.microsoft.com/en-us/aspnet/core/mvc/models/model-binding?view=aspnetcore-6.0)
- [NestJs param decorators](https://docs.nestjs.com/custom-decorators#param-decorators)
