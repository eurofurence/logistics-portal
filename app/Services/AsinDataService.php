<?php

namespace App\Services;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AsinDataService
{
    /**
     *  URL to documentation: https://docs.trajectdata.com/asindataapi/product-data-api/overview
     */

    /**
     * This PHP function retrieves product data based on an ASIN using an API call and returns the product information if
     * successful.
     *
     * @param string asin The `asin` parameter in the `getProductData` function is a string variable that represents the
     * Amazon Standard Identification Number (ASIN) of a product. This ASIN is used to uniquely identify products on the
     * Amazon marketplace. When you call this function and pass an ASIN as an argument,
     *
     * @return null|array If the HTTP response is successful and the 'product' key exists in the JSON response, the
     * function will return the product data as an array. If either of these conditions is not met, the function will
     * return null.
     */
    public function getProductData(string $asin): null|array
    {
        $response = Http::timeout(config('amazon-asin.timeout', 60))->get(config('amazon-asin.api_url') . '/request?', [
            'api_key' => config('amazon-asin.api_key'),
            'type' => 'product',
            'asin' => $asin,
            'amazon_domain' => config('amazon-asin.amazon_domain'),
            'include_fields' => config('amazon-asin.include_fields'),
            'language' => config('amazon-asin.language'),
        ]);

        if ($response->successful()) {
            $result = $response->json();

            if (array_key_exists('product', $result)) {
                if ($result['product']) {
                    return $result;
                }
            }

            return null;
        }

        return null;
    }

    /**
     * This PHP function retrieves account data from an API endpoint and returns it if successful, otherwise it returns
     * null.
     *
     * @return null|array Either an array containing account information if the API call is successful and the
     * 'account_info' key exists in the response, or null if the API call is not successful or the 'account_info' key does
     * not exist in the response.
     */
    public function getAccountData(): null|array
    {
        $response = Http::timeout(config('amazon-asin.timeout', 60))->get(config('amazon-asin.api_url') . '/account?', [
            'api_key' => config('amazon-asin.api_key'),
        ]);

        if ($response->successful()) {
            $result = $response->json();

            if (array_key_exists('account_info', $result)) {
                if ($result['account_info']) {
                    return $result['account_info'];
                }
            }

            return null;
        }

        return null;
    }

    /**
     * The function extracts the ASIN (Amazon Standard Identification Number) from a given URL if it is a valid Amazon URL.
     *
     * @param string url Thank you for providing the code snippet. To extract the ASIN (Amazon Standard Identification
     * Number) from the given URL, you can use the `extractASIN` function.
     *
     * @return string|null An array with the ASIN value if it was successfully extracted from the URL, or null if the ASIN
     * could not be extracted.
     */
    public function extractASIN(string $url): string
    {
        if (!$this->isAmazonUrl($url)) {
            return '';
        }

        // Extract the ASIN from the URL
        preg_match('/\/([A-Z0-9]{10})(?:[\/?]|$)/', $url, $matches);
        $asin = $matches[1] ?? '';

        return $asin;
    }

    /**
     * The function isAmazonUrl checks if a given URL is from Amazon by using a regular expression pattern.
     *
     * @param url The `isAmazonUrl` function is designed to check if a given URL is from Amazon. The regular expression
     * used in the function checks if the URL starts with `http://` or `https://`, followed by an optional `www.`, then
     * `amazon.`, followed by a top-level domain
     *
     * @return int|false the result of the `preg_match` function, which will be either an integer (the number of times the
     * pattern was found in the string) or `false` if an error occurred/not found.
     */
    public function isAmazonUrl($url): int|false
    {
        return preg_match('/https?:\/\/(www\.)?amazon\.[a-z]{2,3}(\/.*)?$/', $url);
    }

    /**
     * This PHP function retrieves the remaining top-up credits from account data.
     *
     * @return int The function `getCredits()` is returning an integer value representing the remaining top-up credits from
     * the account information. If the account data is not available or if the 'account_info' key is not present in the
     * data, the function will return 0.
     */
    public function getCredits(): int
    {
        $account_data = $this->getAccountData();
        if ($account_data != null) {
            return intval($account_data['topup_credits_remaining']);
        }

        return 0;
    }

    /**
     * The function `getRateLimitPerMinute` retrieves the rate limit per minute from account data.
     *
     * @return int The function `getRateLimitPerMinute` returns the rate limit per minute as an integer value. If the
     * account data is not available or if the rate limit per minute is not set in the account data, it will return 0.
     */
    public function getRateLimitPerMinute(): int
    {
        $account_data = $this->getAccountData();
        if ($account_data != null) {
            return intval($account_data['rate_limit_per_minute']);
        }

        return 0;
    }

    /**
     * The function `getAutoTopUpStatus` retrieves the auto top-up status from account data and returns it as a boolean
     * value.
     *
     * @return bool A boolean value indicating the status of auto top-up for the account.
     */
    public function getAutoTopUpStatus(): bool
    {
        $account_data = $this->getAccountData();
        if ($account_data != null) {
            return boolval($account_data['auto_top_up_enabled']);
        }

        return 0;
    }

    /**
     * The function `resolveAmazonUrl` uses cURL to follow redirects and retrieve the final URL from a given short URL.
     *
     * @param string shortUrl The `resolveAmazonUrl` function you provided is designed to resolve a shortened Amazon URL to
     * its actual URL by following any redirections. To use this function, you need to pass the shortened Amazon URL as the
     * `shortUrl` parameter.
     *
     * @return string The function `resolveAmazonUrl` returns the resolved URL after following any redirects from the
     * provided short URL.
     */
    public function resolveAmazonUrl(string $shortUrl): string
    {
        $headers = get_headers($shortUrl, 1);
        return $headers['Location'] ?? $shortUrl; // Returns the target URL or the original URL
    }

    /**
     * The function `cleanAmazonUrl` in PHP checks if a given URL contains the DP format for an Amazon link and returns the
     * adjusted base link if found, otherwise returns the original URL.
     *
     * @param string url Please provide the Amazon URL that you would like to clean up using the `cleanAmazonUrl` function.
     *
     * @return string If the URL contains the DP format for an Amazon link, the adjusted base link will be returned.
     * Otherwise, the original URL will be returned.
     */
    public function cleanAmazonUrl(string $url) :string
    {
        // Check whether the URL contains the DP format
        if (preg_match('#https://www\.amazon\.de/dp/([A-Z0-9]+)#', $url, $matches)) {
            // Return the adjusted base link
            return "https://www.amazon.de/dp/" . $matches[1];
        }

        // If no DP link is found, return the original URL
        return $url;
    }
}
