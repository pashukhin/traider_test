<?php

use \RedBeanPHP\R as RedBean;

class TraiderApi
{
    /**
     * @param string $currencyFromCode
     * @param string $currencyToCode
     *
     * @return array
     */
    public static function getPair($currencyFromCode, $currencyToCode)
    {
        $currencyFrom = self::checkCurrency($currencyFromCode);
        $currencyTo   = self::checkCurrency($currencyToCode);

        $pair         = self::updatePair($currencyFrom, $currencyTo);
        return self::pairToArray($pair);
    }

    /**
     * @param string $currencyFromCode
     * @param string $currencyToCode
     *
     * @return array
     */
    public static function deletePair($currencyFromCode, $currencyToCode)
    {
        $pair = self::getPairFromDb(
            self::checkCurrency($currencyFromCode),
            self::checkCurrency($currencyToCode)
        );
        RedBean::trash($pair);
        return [
            'success' => true
        ];
    }

    /**
     * @return array
     */
    public static function getPairs()
    {
        $pairs = RedBean::findAll('pair');
        self::updatePairs($pairs);
        $result = [];
        foreach ($pairs as $pair) {
            $result[] = self::pairToArray($pair);
        }
        return $result;
    }

    /**
     * @return array
     */
    public static function getCurrencies()
    {
        $result = self::getCurrenciesFromDb();
        if (!count($result)) {
            self::getCurrenciesFromJsonrates();
            $result = self::getCurrenciesFromDb();
        }
        return $result;
    }
    
    ///


// @todo move to class JsonrateConnector
    /**
     * @throws \RedBeanPHP\RedException
     */
    protected static function getCurrenciesFromJsonrates()
    {
        $data = json_decode(file_get_contents('http://jsonrates.com/currencies.json', true));
        foreach ($data as $code => $name) {
            $currency = RedBean::dispense('currency');
            $currency->code = $code;
            $currency->name = $name;
            RedBean::store($currency);
        }
    }

    /**
     * @param \RedBeanPHP\OODBBean $currencyFrom
     * @param \RedBeanPHP\OODBBean $currencyTo
     *
     * @return float
     */
    protected static function getRateFromJsonrates($currencyFrom, $currencyTo)
    {
        $config = require 'config.php';
        $uri = sprintf(
            'http://apilayer.net/api/live?access_key=%s&currencies=%s,%s',
            $config['jsonrates']['api_key'],
            $currencyFrom->code,
            $currencyTo->code
        );

        $data = json_decode(file_get_contents($uri), true);
        if (array_key_exists('success', $data) && $data['success']) {

        } else {
            throw new \Exception('API Exception: Jsonrates api error');
        }

        $sourceCode = $data['source'];
        $currencyFromRateUSD = floatval($data['quotes'][$sourceCode . $currencyFrom->code]);
        $currencyToRateUSD   = floatval($data['quotes'][$sourceCode . $currencyTo->code]);

        return $currencyToRateUSD / $currencyFromRateUSD;
    }

    /**
     * @param \RedBeanPHP\OODBBean        $pair
     *
     * @return array
     */
    protected static function pairToArray($pair)
    {
        list($currencyFrom, $currencyTo) = self::getPairCurrencies($pair);
        return [
            'id'           => $pair->id,
            'currencyFrom' => $currencyFrom->code,
            'currencyTo'   => $currencyTo->code,
            'firstRate'    => $pair->firstRate,
            'rate'         => $pair->rate,
            'rateDiff'     => $pair->rate - $pair->firstRate,
            'createdAt'    => date('Y-m-d H:i:s', $pair->createdAt),
            'updatedAt'    => date('Y-m-d H:i:s', $pair->updatedAt),
        ];
    }

    /**
     * @param \RedBeanPHP\OODBBean $pair
     *
     * @return array
     */
    protected static function getPairCurrencies($pair)
    {
        $currencyFrom = RedBean::load('currency', $pair->currencyFrom_id);
        $currencyTo = RedBean::load('currency', $pair->currencyTo_id);
        return array($currencyFrom, $currencyTo);
    }

    /**
     * @return array
     */
    protected static function getCurrenciesFromDb()
    {
        $data = RedBean::findAll('currency');
        $result = [];
        foreach($data as $currency) {
            $result[$currency->code] = $currency->name;
        }
        return $result;
    }

    /**
     * @param \RedBeanPHP\OODBBean[] $pairs
     */
    protected static function updatePairs($pairs)
    {
        foreach($pairs as $pair) {
            self::updateExistingPair($pair);
        }
    }

    /**
     * @param string $currencyCode
     *
     * @return \RedBeanPHP\OODBBean
     */
    protected static function getCurrencyFromDb($currencyCode)
    {
        return RedBean::findOne('currency', ' code = ? ', [$currencyCode]);
    }

    /**
     * @param \RedBeanPHP\OODBBean $currencyFrom
     * @param \RedBeanPHP\OODBBean $currencyTo
     *
     * @return \RedBeanPHP\OODBBean updated pair
     */
    protected static function updatePair($currencyFrom, $currencyTo)
    {
        $rate = self::getRateFromJsonrates($currencyFrom, $currencyTo);

        $pair = self::getPairFromDb($currencyFrom, $currencyTo);
        if (null === $pair->createdAt) {
            $pair->createdAt    = microtime(true);
            $pair->currencyFrom = $currencyFrom;
            $pair->currencyTo   = $currencyTo;
            $pair->firstRate    = $rate;
        }

        $pair->rate      = $rate;
        $pair->updatedAt = microtime(true);

        RedBean::store($pair);

        return $pair;
    }

    /**
     * @param \RedBeanPHP\OODBBean $pair
     *
     * @return \RedBeanPHP\OODBBean updated pair
     */
    protected static function updateExistingPair($pair)
    {
        list($currencyFrom, $currencyTo) = self::getPairCurrencies($pair);
        $rate = self::getRateFromJsonrates($currencyFrom, $currencyTo);

        $pair->rate      = $rate;
        $pair->updatedAt = microtime(true);

        RedBean::store($pair);

        return $pair;
    }

    /**
     * find or create new pair
     *
     * @param \RedBeanPHP\OODBBean $currencyFrom
     * @param \RedBeanPHP\OODBBean $currencyTo
     *
     * @return \RedBeanPHP\OODBBean
     */
    protected static function getPairFromDb($currencyFrom, $currencyTo)
    {
        return RedBean::findOrCreate(
            'pair',
            [
                'currency_from_id' => $currencyFrom->id,
                'currency_to_id' => $currencyTo->id
            ]
        );
    }

    /**
     * @param string $currencyCode
     *
     * @return \RedBeanPHP\OODBBean
     *
     * @throws Exception
     */
    protected static function checkCurrency($currencyCode)
    {
        $currency = self::getCurrencyFromDb($currencyCode);
        if (null === $currency) {
            throw new \Exception("API Exception: currency {$currencyCode} not found");
        }
        return $currency;
    }

}