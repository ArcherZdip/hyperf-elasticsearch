# Hyperf Elasticsearch Driver
根据 [babenkoivan/scout-elasticsearch-driver](https://github.com/babenkoivan/scout-elasticsearch-driver) 改造的基于Hyperf框架的ElasticSearch组件。

## Requirements
- PHP version >=7.2.0
- Hyperf Framework version >=1.0 <2.0
- Elasticsearch version >=7

## Installation
```shell
composer require archerzdip/hyperf-elasticsearch
```

## Configuration
> 发布配置文件
```shell
php bin/hyperf vendor:publish archerzdip/hyperf-elasticsearch
```
* 配置文件在config/autoload/scout_elastic.php， 配置参数如下： *

| Option | Description |
| :---   | :---        |
| driver | 默认defalut |
| soft_delete | 是否软删除 |
| prefix | 前缀 |
| client.host | ElasticSearch client, default localhost:9200|
| max_connections | 最大连接数，默认500 |
| indexer | 索引方式,目前支持`single`|
| chunk.searchable | 批量处理的搜索块数量 |
| chuck.unsearchable | 批量处理的搜索块数量 |
| update_mapping | 是否自动更新，默认 `true` |
| document_refresh | This option controls when updated documents appear in the search results. Can be set to 'true', 'false', 'wait_for' or null. More details about this option you can find [here](https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-refresh.html). By default set to null. |

## Index Configurator
所以配置器类用于设置ElasticSearch的索引，可使用以下方式创建新的索引配置器：
```shell script
php bin/hyperf.php make:index-configurator MyIndexConfigurator
```
默认目录为`App\ElasticIndexConfigurator\MyIndexConfigurator`

```php
<?php

namespace App\ElasticIndexConfigurator;

use ArcherZdip\ScoutElastic\IndexConfigurator;
use ArcherZdip\ScoutElastic\Traits\Migratable;

class MyIndexConfigurator extends IndexConfigurator
{
    use Migratable;

    protected $name = 'my_index';

    /**
     * @var array
     */
    protected $settings = [
        'analysis' => [
            'analyzer' => [
                'es_std' => [
                    'type' => 'standard',
                    'stopwords' => '_spanish_'
                ]
            ]    
        ]
    ];
}
```

More about index settings you can find [in the index management](https://www.elastic.co/guide/en/elasticsearch/guide/current/index-management.html) section of Elasticsearch documentation.

## Searchable Model
```shell script
php bin/hyperf.php make:searchable-model MyModel
```

## Usage
Basic search usage example:

```php
// set query string
App\MyModel::search('phone')
    // specify columns to select
    ->select(['title', 'price'])
    // filter 
    ->where('color', 'red')
    // sort
    ->orderBy('price', 'asc')
    // collapse by field
    ->collapse('brand')
    // set offset
    ->from(0)
    // set limit
    ->take(10)
    // get results
    ->get();
```

If you only need the number of matches for a query, use the `count` method:

```php
App\MyModel::search('phone') 
    ->count();
```

If you need to load relations, use the `with` method:

```php
App\MyModel::search('phone') 
    ->with('makers')
    ->get();
```

In addition to standard functionality the package offers you the possibility to filter data in Elasticsearch without specifying a query string:
  
```php
App\MyModel::search('*')
    ->where('id', 1)
    ->get();
```

Also you can override model [search rules](#search-rules):

```php
App\MyModel::search('Brazil')
    ->rule(App\MySearchRule::class)
    ->get();
```

And use [variety](#available-filters) of `where` conditions: 

```php
App\MyModel::search('*')
    ->whereRegexp('name.raw', 'A.+')
    ->where('age', '>=', 30)
    ->whereExists('unemployed')
    ->get();
```

And filter out results with a score less than [min_score](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-body.html#request-body-search-min-score): 

```php
App\MyModel::search('sales')
    ->minScore(1.0)
    ->get();
```

And add more complex sorting (geo_distance eg.)

```php
$model = App\MyModel::search('sales')
    ->orderRaw([
       '_geo_distance' =>  [
           'coordinates' => [
               'lat'   =>  51.507351,
               'lon'   =>  -0.127758
           ],
           'order'     =>  'asc',
           'unit'      =>  'm'
       ]
    ])
    ->get();

// To retrieve sort result, use model `sortPayload` attribute:
$model->sortPayload;
```



At last, if you want to send a custom request, you can use the `searchRaw` method:

```php
App\MyModel::searchRaw([
    'query' => [
        'bool' => [
            'must' => [
                'match' => [
                    '_all' => 'Brazil'
                ]
            ]
        ]
    ]
]);
```
```php
## 直接返回ES数据
App\MyModel::search("test")->raw();

```

This query will return raw response.

## Console Commands

Command | Arguments | Description
--- | --- | ---
make:index-configurator | `name` - The name of the class | Creates a new Elasticsearch index configurator.
make:searchable-model | `name` - The name of the class | Creates a new searchable model.
make:search-rule | `name` - The name of the class | Creates a new search rule.
elastic:create-index | `index-configurator` - The index configurator class | Creates an Elasticsearch index.
elastic:update-index | `index-configurator` - The index configurator class | Updates settings and mappings of an Elasticsearch index.
elastic:drop-index | `index-configurator` - The index configurator class | Drops an Elasticsearch index.
elastic:update-mapping | `model` - The model class | Updates a model mapping.
elastic:migrate-model | `model` - The model class, `target-index` - The index name to migrate | Migrates model to another index.

For detailed description and all available options run `php bin/hyperf.php help [command]` in the command line.

## Search rules
```
php bin/hyperf.php make:search-rule MySearchRule
```
默认目录为`App\ElasticSearchRule\MySearchRule`

## License
MIT