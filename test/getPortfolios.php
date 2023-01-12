#!/usr/bin/php
<?php

include_once 'init.php';

foreach ($api->getMe()->getPortfolios() as $portfolio) {
    echo "{$portfolio} \"{$portfolio->getName()}\"\n";
    foreach ($portfolio->getItems() as $item) {
        echo "{$portfolio} > {$item} \"{$item->getName()}\"\n";
    }
    echo "\n";
}
