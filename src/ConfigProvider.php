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
                \ArcherZdip\ScoutElastic\Command\ElasticUpdateMappingCommand::class,
                \ArcherZdip\ScoutElastic\Command\ElasticIndexCreateCommand::class,
                \ArcherZdip\ScoutElastic\Command\ElasticIndexDropCommand::class,
                \ArcherZdip\ScoutElastic\Command\ElasticIndexUpdateCommand::class,
                \ArcherZdip\ScoutElastic\Command\IndexConfiguratorMakeCommand::class,
                \ArcherZdip\ScoutElastic\Command\SearchRuleMakeCommand::class,
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