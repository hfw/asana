<?php

namespace Helix\Asana\Api\Laravel\Command;

use Helix\Asana\Api\Laravel\Facade\Asana;
use Illuminate\Console\Command;

final class AsanaTest extends Command {

    protected $description = "Tests Asana connectivity.";

    protected $signature = 'asana:test';

    public function handle () {
        var_dump(Asana::getMe());
        echo "\n\n";
    }
}