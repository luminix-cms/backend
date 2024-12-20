# Filtering Data

Luminix API provides a powerful filtering system that allows clients to retrieve only the data they need. This helps creating a more generic and flexible API, reducing the amount of data transferred and improving performance.

## Enabling/Disabling Filtering

By default, filtering is enabled for all models. You can disable this feature by setting the `api.filter.enable` configuration key to `false`.

```php
// config/luminix/backend.php
'api' => [
    'filter' => [
        'enable' => false,
    ],
],
```

## Filter Syntax

The filtering system will use the query parameter `where` to apply filters to the data. The value of this parameter should be an array of key-value pairs, where the key could be the column name or the column name followed by a colon and an operator. The value should be the desired value to filter by.

Here are some examples of how you can use the `where` parameter:

```http
// Get users with the exact name "John Doe"
GET /luminix-api/users?where[name]=John%20Doe

// Get users with the name containing "John"
GET /luminix-api/users?where[name:contains]=John

// Get users with the age greater than 18
GET /luminix-api/users?where[age:greaterThan]=18

// Get users created in the year 2024
GET /luminix-api/users?where[created_at:between][]=2024-01-01&where[created_at:between][]=2024-12-31
```

The available operators are:

- `equals`: Filters records where the column value is equal to the provided value.
- `notEquals`: Filters records where the column value is not equal to the provided value.
- `greaterThan`: Filters records where the column value is greater than the provided value.
- `greaterThanOrEquals`: Filters records where the column value is greater than or equal to the provided value.
- `lessThan`: Filters records where the column value is less than the provided value.
- `lessThanOrEquals`: Filters records where the column value is less than or equal to the provided value.
- `like`: Filters records where the column value is like the provided value. This operator supports the `%` wildcard.
- `contains`: Filters records where the column value contains the provided value. It is the like operator with the `%` wildcard automatically added to the beginning and end of the value.
- `startsWith`: Filters records where the column value starts with the provided value. It is the like operator with the `%` wildcard automatically added to the end of the value.
- `endsWith`: Filters records where the column value ends with the provided value. It is the like operator with the `%` wildcard automatically added to the beginning of the value.
- `null`: Filters records where the column value is `null`.
- `notNull`: Filters records where the column value is not `null`.
- `relation`: Filters records by a related model. The value should be the related model's ID.

If the operator is either `equals` or `relation`, you can omit it from the key. For example, `where[age]=18` is equivalent to `where[age:equals]=18`.

Multiple conditions can be combined by adding more key-value pairs to the `where` parameter. For example, to filter users by age and email verification status, you can use the following query:

```http
GET /luminix-api/users?where[age:greaterThan]=18&where[email_verified_at:notNull]=1
```

It is not possible, however, to apply an "OR" condition in the same query. A custom endpoint or a custom filter would be necessary to achieve this.

## Excluding Sensitive Columns

You can exclude specific columns from being filtered by setting the `api.filter.exclude` configuration key. This is useful for preventing clients from filtering on sensitive or internal columns.

```php
// config/luminix/backend.php
'api' => [
    'filter' => [
        'exclude' => [
            'App\Models\User:password,email_verified_at',
        ],
    ],
],
```

This will prevent clients from filtering on the `password` and `email_verified_at` columns of the `User` model.

 > **Note:** By default, the model's `$hidden` columns are excluded from filtering, so this example would almost always be unnecessary. However, if adding columns to a model's `$hidden` array is not an option, this configuration can be used as a fallback.

## Registering Custom Operators

It is possible to add custom operators to the filtering system by registering macros to the `Luminix\Backend\Services\ModelFilter` class. This allows you to define custom filtering methods that can be used in the `where` parameter.

Here's an example of how you might register a custom operator:

```php

use Luminix\Backend\Services\ModelFilter;

ModelFilter::macro('gmailOrHotmail', function (Builder $query, string $column, mixed $value) {
    return $query->where(function ($query) use ($column, $value) {
        $query->where($column, 'like', '%@gmail.com')
            ->orWhere($column, 'like', '%@hotmail.com');
    });
});
```

With this macro registered, you can now use the `gmailOrHotmail` operator in the `where` parameter:

```http
GET /luminix-api/users?where[email:gmailOrHotmail]=1
```

This will filter users whose email address ends with either `@gmail.com` or `@hotmail.com`.

## Next Steps

[Middleware Configuration](3a-middleware.md)
[Back to Documentation Index](0-index.md)

