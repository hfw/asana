<?php

namespace Helix\Asana\Api\Laravel\Command;

use Helix\Asana\Api\Laravel\Facade\Asana;
use Illuminate\Console\Command;

final class AsanaGet extends Command {

    protected $description = 'Calls HTTP GET on an Asana endpoint.';

    protected $signature = 'asana:get {path=users/me}';

    public function handle () {
        var_dump(Asana::getApi()->get(ltrim($this->argument('path'), '/')));
        echo "\n\n";
    }

}