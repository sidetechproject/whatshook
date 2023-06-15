<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Webhook;
use App\Models\User;
use GuzzleHttp\Client as HttpClient;
use App\Notifications\WebhookReceived;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\BadResponseException;
use App\Traits\WebhookTrait;

class WebhookController extends Controller
{
    use WebhookTrait;

    public function index()
    {
        //return view('contact.index');
    }

    public function send(Request $request, $alias)
    {
        $httpClient = new HttpClient();
        $redirect_return_url = null;

        //$payload = json_decode($request->getContent());
        $payload = $request->getContent();
        if(!$payload){
            $payload = 'Empty Payload';
        }

        $webhook = Webhook::where('alias', '=', trim($alias))->first();

        if(!$webhook || !$webhook->status){
            return response()->json([
                'status' => 400,
                'message' => 'Not Found or Not Verified'
            ], 400);
        }

        $httpBaseUrl = env('APP_URL_WHATSAPP');

        try {
            if(!$this->isJson($payload)){
                parse_str(urldecode($payload), $payload_clean);
                $payload = json_encode($payload_clean, JSON_PRETTY_PRINT);

                if(isset($payload_clean['_redirect'])){
                    $redirect_return_url = $payload_clean['_redirect'];
                }
            }

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

        if($redirect_return_url){
            return redirect($redirect_return_url);
        }

        return response()->json([
            'status' => 200
        ], 200);
    }

    public function channelVerify($uuid)
    {   
        $channelIsVerified = $this->verify($uuid);
        
        return view('webhook-verification', ['channelIsVerified' => $channelIsVerified]);
    }
}
