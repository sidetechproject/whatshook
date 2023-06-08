<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Webhook;
use GuzzleHttp\Client as HttpClient;

class WebhookController extends Controller
{
    public function index()
    {
        //return view('contact.index');
    }

    public function send(Request $request, $alias)
    {
        $httpClient = new HttpClient();

        $payload = json_decode($request->getContent());

        $webhook = Webhook::where('alias', '=', trim($alias))->first();
        
        $httpBaseUrl = env('APP_URL_WHATSAPP');

        try {
            $authRequest = $httpClient->request('POST', $httpBaseUrl, [
                    'form_params' => [
                        'number' => $webhook->route_value,
                        'message' => $payload,
                    ],
                    'verify' => false,
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => false
                    ],
                ]
            );

            //$content = json_decode($authRequest->getBody()->getContents());
        } catch (BadResponseException $e) {
            Log::info($e->getResponse()->getBody()->getContents());

            return response()->json([
                'status' => 400
            ], 400);
        }

        return response()->json([
            'status' => 200
        ], 200);
    }
}
