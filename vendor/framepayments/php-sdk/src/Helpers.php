<?php

namespace Frame;

use League\ISO3166;

class Helpers
{
    public static function convertAlpha2ToAlpha3($iso3166_2)
    {
        $iso3166 = new ISO3166\ISO3166();
        $countryData = $iso3166->alpha2($iso3166_2);

        return $countryData['alpha3'];
    }
}
