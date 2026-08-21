<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;

class PipelineController extends Controller
{
    public function __invoke()
    {
        return view('crm.pipeline');
    }
}
