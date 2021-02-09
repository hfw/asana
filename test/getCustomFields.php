#!/usr/bin/php
<?php

include_once 'init.php';

foreach ($api->getDefaultWorkspace()->getCustomFields() as $customField) {
    echo "{$customField->getName()}\n";
}