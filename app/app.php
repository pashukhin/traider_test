<?php

use \Slim\Slim;
use \RedBeanPHP\R as RedBean;

require_once 'error.php';

$config = require 'config.php';

RedBean::setup($config['db']['dsn']);

/**
 * @param $data
 */
function out($data)
{
    echo json_encode($data, JSON_PRETTY_PRINT);
}

$app = new Slim();

$app->get(
    '/currencies',
    function ()
    {
        out(TraiderApi::getCurrencies());
    }
);

$app->get(
    '/pairs',
    function ()
    {
        out(TraiderApi::getPairs());
    }
);

$app->get(
    '/pairs/:currencyFromCode-:currencyToCode',
    function ($currencyFromCode, $currencyToCode)
    {
        out(TraiderApi::getPair($currencyFromCode, $currencyToCode));
    }
);

$app->delete(
    '/pairs/:currencyFromCode-:currencyToCode',
    function ($currencyFromCode, $currencyToCode)
    {
        out(TraiderApi::deletePair($currencyFromCode, $currencyToCode));
    }
);

return $app;