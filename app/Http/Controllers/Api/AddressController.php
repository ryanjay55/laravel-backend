<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Address\BarangayModel;
use App\Models\Address\RegionsModel;
use App\Models\Address\MunicipalityModel;
use App\Models\Address\ProvinceModel;

class AddressController extends Controller
{
    public function getRegions()
    {
        $regionModel = new RegionsModel;

        $regions = $regionModel::all()->toJson();

        return $regions;
    }

    public function getProvinces(Request $request)
    {       
        $provinceModel = new ProvinceModel;

        $regCode = $request->input('regCode');

        $provinces = $provinceModel::where('regCode', $regCode)->get()->toJson();
        return $provinces;        
    }

    public function getMunicipalities(Request $request)
    {       

        $municipalityModel = new MunicipalityModel;

        $provCode = $request->input('provCode');

        $municipalities = $municipalityModel::where('provCode', $provCode)->get()->toJson();
        return $municipalities;

    }

    public function getBarangays(Request $request)
    {        

        $barangayModel = new BarangayModel;

        $citymunCode = $request->input('citymunCode');

        $barangays = $barangayModel::where('citymunCode', $citymunCode)->get()->toJson();
        return $barangays;

    }
}
