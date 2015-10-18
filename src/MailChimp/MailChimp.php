<?php

namespace Oneup\Contao\Mailchimp;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class MailChimp
{
    /** @var Client $client */
    protected $client;
    protected $apiKey;
    protected $apiEndpoint = 'https://%dc%.api.mailchimp.com/3.0/';
    protected $headers = [];

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;

        list(, $dc) = explode('-', $this->apiKey);
        $this->apiEndpoint = preg_replace('/%dc%/', $dc, $this->apiEndpoint);

        $this->headers = [
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
            'Authorization' => 'apikey '.$this->apiKey,
            'User-Agent' => 'Oneup/Contao/MailChimp 1.0 (github.com/1up-lab/contao-mailchimp)',
        ];

        $this->client = new Client([
            'base_uri' => $this->apiEndpoint,
            'headers' => $this->headers,
        ]);
    }

    public function call($type = 'get', $uri = '', $args = [], $timeout = 10)
    {
        $args['apikey'] = $this->apiKey;
        $request = null;

        try {
            switch ($type) {
                case 'post':
                    return $response = $this->client->request('POST', $uri, [
                        'json' => $args,
                        'timeout' => $timeout,
                    ]);
                    break;

                case 'patch':
                    return $response = $this->client->request('PATCH', $uri, [
                        'body' => json_encode($args),
                        'timeout' => $timeout,
                    ]);
                    break;

                case 'put':
                    return $response = $this->client->request('PUT', $uri, [
                        'query' => $args,
                        'timeout' => $timeout,
                    ]);
                    break;

                case 'delete':
                    return $response = $this->client->request('DELETE', $uri, [
                        'query' => $args,
                        'timeout' => $timeout,
                    ]);
                    break;

                case 'get':
                default:
                    return $response = $this->client->request('GET', $uri, [
                        'query' => $args,
                        'timeout' => $timeout,
                    ]);
                    break;
            }
        } catch (RequestException $e) {
            return $e->getResponse();
        }
    }

    public function get($uri = '', $args = [], $timeout = 10)
    {
        return $this->call('get', $uri, $args, $timeout);
    }

    public function post($uri = '', $args = [], $timeout = 10)
    {
        return $this->call('post', $uri, $args, $timeout);
    }

    public function patch($uri = '', $args = [], $timeout = 10)
    {
        return $this->call('patch', $uri, $args, $timeout);
    }

    public function put($uri = '', $args = [], $timeout = 10)
    {
        return $this->call('put', $uri, $args, $timeout);
    }

    public function delete($uri = '', $args = [], $timeout = 10)
    {
        return $this->call('delete', $uri, $args, $timeout);
    }

    public function validateApiKey()
    {
        $response = $this->get();
        return $response && 200 == $response->getStatusCode() ? true : false;
    }

    public function getAccountDetails()
    {
        $response = $this->get('');
        return $response ? json_decode($response->getBody()) : null;
    }

    public function isSubscribed($listId, $email)
    {
        $email = strtolower($email);
        $hash = md5($email);
        $endpoint = sprintf('lists/%s/members/%s', $listId, $hash);

        $response = $this->get($endpoint);

        if (200 === $response->getStatusCode()) {
            return true;
        }

        return false;
    }

    public function subscribeToList($listId, $email, $mergeVars = [], $doubleOptin = true)
    {
        $endpoint = sprintf('lists/%s/members', $listId);

        if (!$this->isSubscribed($listId, $email)) {
            $response = $this->post($endpoint, [
                'id' => $listId,
                'email_address' => $email,
                'merge_fields' => $mergeVars,
                'status' => $doubleOptin ? 'pending' : 'subscribed',
            ]);

            return $response && 200 == $response->getStatusCode() ? true : false;
        }

        return false;
    }

    public function unsubscribeFromList($listId, $email)
    {
        $email = strtolower($email);
        $hash = md5($email);
        $endpoint = sprintf('lists/%s/members/%s', $listId, $hash);

        $response = $this->patch($endpoint, [
            'status' => 'unsubscribed'
        ]);

        if (200 == $response->getStatusCode()) {
            return true;
        }

        return false;
    }

    public function removeFromList($listId, $email)
    {
        $email = strtolower($email);
        $hash = md5($email);
        $endpoint = sprintf('lists/%s/members/%s', $listId, $hash);

        $response = $this->delete($endpoint);

        if (204 === $response->getStatusCode()) {
            return true;
        }

        return false;
    }
}
