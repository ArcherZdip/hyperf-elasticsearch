<?php


namespace ArcherZdip\ScoutElastic;


class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [],
            'annotations'  => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'commands'     => [
                \ArcherZdip\ScoutElastic\Console\ElasticUpdateMappingCommand::class,
                \ArcherZdip\ScoutElastic\Console\ElasticIndexCreateCommand::class,
                \ArcherZdip\ScoutElastic\Console\ElasticIndexDropCommand::class,
                \ArcherZdip\ScoutElastic\Console\ElasticIndexUpdateCommand::class,
                \ArcherZdip\ScoutElastic\Console\IndexConfiguratorMakeCommand::class,
                \ArcherZdip\ScoutElastic\Console\SearchRuleMakeCommand::class,
                \ArcherZdip\ScoutElastic\Console\ElasticImportCommand::class,
                \ArcherZdip\ScoutElastic\Console\SearchableModelMakeCommand::class,
            ],
            'listeners'    => [],
            'publish'      => [
                [
                    'id'          => 'config',
                    'description' => 'elasticsearch connect config',
                    'source'      => __DIR__ . '/../config/scout_elastic.php',
                    'destination' => BASE_PATH . '/config/autoload/scout_elastic.php',
                ]
            ]

        ];
    }
}