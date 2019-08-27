<?php

namespace ScoutElastic\Console;

use Illuminate\Console\Command;
use ScoutElastic\Facades\ElasticClient;

class ReindexSearchCommand extends Command
{
    protected $name = 'scout:reindex';

    protected $description = 'Reindex Elasticsearch';

    public function handle()
    {
        foreach (config('scout_elastic.models', []) as $searchableModel) {

            if (!in_array(\ScoutElastic\Searchable::class, class_uses($searchableModel))) {
                continue;
            }

            $this->newElasticHandle($searchableModel);
        }
    }

    private function newElasticHandle($searchableModel)
    {
        $searchableModelObject = new $searchableModel;

        $this->info("\nIndexing " . $searchableModel);

        $indexConfiguratorClass = $searchableModelObject->indexConfigurator;

        $indexConfigurator = new $indexConfiguratorClass();

        if (ElasticClient::indices()->exists(['index' => $indexConfigurator->getName()])) {

            $this->call('elastic:drop-index', [
                'index-configurator' => $searchableModelObject->indexConfigurator
            ]);
        }

        $this->call('elastic:create-index', [
            'index-configurator' => $searchableModelObject->indexConfigurator
        ]);
        $this->call('elastic:update-mapping', [
            'model' => $searchableModel
        ]);

        $this->call('scout:import', [
            'model' => $searchableModel
        ]);
    }
}