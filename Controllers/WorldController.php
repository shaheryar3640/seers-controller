<?php

namespace App\Http\Controllers;

use App\Models\Continent;
use App\Models\Country;
use App\Models\Division;
use App\Models\City;

/**
 * World
 */
class WorldController extends Controller
{
    public static function World(){

        //$data = WorldController::LoadCountriesByContinent('as');
        $data = WorldController::LoadDivisionsByCountry('hk');
        //$data = WorldController::LoadCitiesByDivision('pk-pb');

        dd($data);

        $continents = Continent::all();

        foreach($continents as $continent){
        /*$continent = WorldController::getContinentByCode('as');
        if($continent){*/
            echo $continent->name;
            echo "<br />";
            foreach($continent->children() as $country){
                echo "--".$country->name;
                echo "<br />";
                if($country->has_division) {
                    foreach ($country->children() as $division) {
                        echo "----".$division->name;
                        echo "<br />";
                        foreach ($division->children() as $city) {
                            echo "------".$city->name;
                            echo "<br />";
                        }
                    }
                }else{
                    foreach ($country->children() as $city) {
                        echo "------".$city->name;
                        echo "<br />";
                    }
                }
            }
            echo "<br />";
        }
    }



    public static function Continents(){
        return Continent::all();
    }

    public static function LoadCountriesByContinent($code){
        $continent = Continent::where('code',$code)->first();
        return $continent->children();
    }

    public static function LoadDivisionsByCountry($code){
        $country = Country::where('code',$code)->first();
        return $country->children();
    }

    public static function LoadCitiesByDivision($code){
        $division = Division::where('code',$code)->first();
        return $division->children();
    }

    /*
    public static function getContinentByCode($code)
    {
        return Continent::getByCode($code);
    }

    public static function getCountryByCode($code)
    {
        return Country::getByCode($code);
    }

    public static function getByCode($code)
    {
        $code = strtolower($code);
        if (strpos($code, '-')) {
            list($country_code, $code) = explode('-', $code);
            $country = self::getCountryByCode($country_code);
        } else {
            return self::getCountryByCode($code);
        }
        if ($country->has_division) {
            return Division::where([
                ['country_id', $country->id],
                ['code', $code ],
            ])->first();
        } else {
            return City::where([
                ['country_id', $country->id],
                ['code', $code ],
            ]);
        }
        throw new \App\Exceptions\InvalidCodeException("Code is invalid");
    } */
}
