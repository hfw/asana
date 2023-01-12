#!/usr/bin/php
<?php

include_once 'init.php';

foreach ($api->getWorkspace()->getCustomFields() as $customField) {
    echo "{$customField->getName()}\n";
}