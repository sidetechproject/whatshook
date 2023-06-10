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
}
