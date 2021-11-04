<?php

namespace Helix\Asana\Api\Laravel\Command;

use Helix\Asana\Api\Laravel\Facade\Asana;
use Illuminate\Console\Command;

final class AsanaCall extends Command {

    protected $description = 'Arbitrarily call methods on (mostly) any entity, followed by update() if appropriate.';

    protected $signature = 'asana:call'
    . ' {class  : The entity class to load, relative to the "Helix\\Asana\\" namespace (e.g. "User")}'
    . ' {path   : The entity\'s resource path (e.g. "users/me")}'
    . ' {method : The method name to call (e.g. "getName", "reload")}'
    . ' {args?* : Any arguments for the method, separated by spaces. Empty strings ("") are converted to NULL.}';

    public function handle () {
        $api = Asana::getApi();
        $entity = $api->load($api, "Helix\\Asana\\" . $this->argument('class'), $this->argument('path'));
        if (!$entity) {
            $this->error('404');
            exit(1);
        }
        $args = array_map(function(string $each) {
            if (!strlen($each)) {
                return null;
            }
            return $each;
        }, $this->argument('args'));
        $return = $entity->{$this->argument('method')}(...$args);
        if ($entity->isDiff() and method_exists($entity, 'update')) {
            var_dump($entity->update());
        }
        else {
            var_dump($return);
        }
        echo "\n\n";
    }
}