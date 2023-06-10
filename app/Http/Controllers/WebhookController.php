<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Webhook;
use App\Models\User;
use GuzzleHttp\Client as HttpClient;
use App\Notifications\WebhookReceived;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\BadResponseException;

class WebhookController extends Controller
{
    public function index()
    {
        //return view('contact.index');
    }

    public function send(Request $request, $alias)
    {
        $httpClient = new HttpClient();

        //$payload = json_decode($request->getContent());
        $payload = $request->getContent();
        if(!$payload){
            $payload = 'Empty Payload';
        }

        $webhook = Webhook::where('alias', '=', trim($alias))->first();

        $httpBaseUrl = env('APP_URL_WHATSAPP');

        try {
            $data = [
                'url' => $webhook->alias,
                'name' => $webhook->name,
                'number' => $webhook->route_value,
                'payload' => $payload,
            ];

            $authRequest = $httpClient->request('POST', $httpBaseUrl, [
                    'form_params' => $data,
                    'verify' => false,
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => false
                    ],
                ]
            );

            $user = User::find($webhook->user_id)->first();
            $user->notify(new WebhookReceived($data));

            $content = json_decode($authRequest->getBody()->getContents());
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
