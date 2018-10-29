<?php
require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Console\Application;

$application = new Application();

$application->add(new GenerateSellerListCommand());
$application->add(new GenerateCarChartCommand());

$application->run();
