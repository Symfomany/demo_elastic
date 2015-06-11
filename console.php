#!/usr/bin/env php
<?php

require_once __DIR__.'/vendor/autoload.php';

set_time_limit(0);


$application = new \Symfony\Component\Console\Application();
$application->add(new \Command\PostCommand());
$application->run();

return $app;