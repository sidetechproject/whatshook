<?php
namespace App\Traits;

use App\Models\Webhook;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

trait WebhookTrait
{
    /**
     * Generate a random unique alias.
     *
     * @return string|null
     */
    private function generateAlias()
    {
        $alias = null;
        $unique = false;
        $fails = 0;

        while (!$unique) {
            $alias = $this->generateString(5 + $fails);

            // Check if the alias exists
            if(!Webhook::where('alias', '=', $alias)->exists()) {
                $unique = true;
            }

            $fails++;
        }

        return $alias;
    }

    /**
     * Generate a random string.
     *
     * @param int $length
     * @return string
     */
    private function generateString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * Verify is json
     *
     * @param int $length
     * @return string
     */
    private function isJson($string) {
       json_decode($string);
       return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Sent verification link.
     *
     * @return string|null
     */
    private function sendWhatsAppLinkVerification($webhook)
    {
        $httpClient = new HttpClient();
        $httpBaseUrl = env('APP_URL_WHATSAPP');

        $data = [
            'url' => $webhook->alias,
            'name' => $webhook->name,
            'number' => $webhook->route_value,
            'payload' => route('webhook.whatsapp.verification', ['uuid' => $webhook->uuid]),
        ];

        $authRequest = $httpClient->request('POST', $httpBaseUrl, [
                'form_params' => $data,
                'verify' => false,
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => false
                ],
            ]
        );
    }

    /**
     * Verify link sent
     *
     * @return string|null
     */
    private function verify($uuid)
    {
        if(!Webhook::where('uuid', '=', $uuid)->exists()) {
            return false;
        }

        Webhook::where('uuid', '=', $uuid)->update(['status' => 1]);

        return true;
    }
    
}
