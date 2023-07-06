<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Webhook\Webhook;
use Illuminate\Support\Facades\Storage;

class ExternalController extends Controller
{
    public function webhook(Request $request, string $driver)
    {
        return Webhook::processWebhook($driver, $request);
    }

    public function qoreid(Request $request)
    {
        Storage::disk('local')->put('qoreid-req.txt', $request->__toString());
        Storage::disk('local')->put('qoreid-data.txt', $request->getContent());
        Storage::disk('local')->put('qoreid-raw.txt', $request->toArray());
        Storage::disk('local')->put('qoreid-res.txt', Webhook::processWebhook('qoreid', $request)->__toString());
        return Webhook::processWebhook('qoreid', $request);
    }

    public function verifyMe(Request $request)
    {
        return Webhook::processWebhook('verify.me', $request);
    }
}
