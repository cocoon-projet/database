[![PHP Composer](https://github.com/cocoon-projet/database/actions/workflows/ci.yml/badge.svg)](https://github.com/cocoon-projet/database/actions/workflows/ci.yml)  [![codecov](https://codecov.io/gh/cocoon-projet/database/graph/badge.svg?token=KNV48Z8CAF)](https://codecov.io/gh/cocoon-projet/database) ![License](https://img.shields.io/badge/Licence-MIT-green)

# cocoon-projet/database

## Pré-requis

![PHP Version](https://img.shields.io/badge/php:version-8.0-blue)

## Installation

via composer
```
composer require cocoon-projet/database
```
## Connexion & initialisation

```php
$config =  [
    'db_driver' => 'sqlite',
    'db' => [
        'mysql' => [
            'db_name' => 'cocoon',
            'db_user' => 'root',
            'db_password' => 'root',
            'db_host' => 'localhost',
            'mode' => 'development',
            'db_cache_path' => dirname(__DIR__) . '/database/cache/',
            'pagination_renderer' => 'bootstrap5' // ou tailwind
        ],
        'sqlite' => [
            'path' => dirname(__DIR__) . '/database/database.sqlite',
            'mode' => 'development',
            'db_cache_path' => dirname(__DIR__) . '/database/cache/',
            'pagination_renderer' => 'bootstrap5' // ou tailwind
        ]
    ]
];
// la connexion est active pour utiliser l'orm
Orm::manager($config['db_driver'], $config['db']['sqlite']);


```

## Query Builder
```php
// la connexion est active pour utiliser l'orm
Orm::manager($config['db_driver'], $config['db']['sqlite']);

// Utilisation via la classe Orm::manager
$users = Orm::table('users')->where('id', 1)->get();
echo $users[0]->name;

// Utilisation via la classe DB
$products = DB::table('products')->where('price', '>', 100)->get
foreach($products as $product) {
    echo $product->name ' . ' $product->price . '\n';
}
```


## Model

## Crud

## Pagination

## Scopes

## Mutators & Accessors

## Méthodes magiques

## Transactions

 ## Observers

 ## Repository




