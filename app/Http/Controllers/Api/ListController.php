<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Client;
use App\Models\Currency;
use App\Models\Project;
use App\Models\Service;
use Illuminate\Http\Request;

class ListController extends Controller
{
    //
    public function getCurrencies(Request $request)
    {
        $data = Currency::active()->get();
        return ApiResponse::index('Currencies data', $data);
    }

    public function getClients(Request $request)
    {
        $data = Client::active()->get();
        return ApiResponse::index('Clients data', $data);
    }

    public function getProjects(Request $request)
    {
        $data = Project::active()->get();
        return ApiResponse::index('Projects data', $data);
    }

    public function getServices(Request $request)
    {
        $data = Service::active()->get();
        return ApiResponse::index('Services data', $data);
    }

}
