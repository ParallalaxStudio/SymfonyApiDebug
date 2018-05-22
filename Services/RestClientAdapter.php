<?php

namespace Parallalax\ApiDebugBundle\Services;

use \Symfony\Component\HttpFoundation\RequestStack;

use \GuzzleHttp\ClientInterface;
use \GuzzleHttp\Psr7;
use \Psr\Http\Message\RequestInterface;

class RestClientAdapter implements ClientInterface{

    protected $request;
    protected $client;
    protected $auth;
    protected $uri;
    protected $executionTime;

    protected $response;

    public function __construct(RequestStack $request) {
        $this->request = $request->getCurrentRequest();
        $this->client = new \GuzzleHttp\Client();
    }


    public function send(RequestInterface $request, array $options = []) {

        $this->uri = $request->getUri();
        $executionStartTime = microtime(true);
        $this->response = $this->client->send($request, $options);
        $executionEndTime = microtime(true);
        $this->executionTime = round(($executionEndTime - $executionStartTime)*1000);

        $this->modifyRequestHeaders($request->getMethod());

        return $this->response;
    }

    public function sendAsync(RequestInterface $request, array $options = [])
    {
        $this->uri = $request->getUri();
        $executionStartTime = microtime(true);
        $this->response = $this->client->sendAsync($request, $options);
        $executionEndTime = microtime(true);
        $this->executionTime = round(($executionEndTime - $executionStartTime)*1000);

        $this->modifyRequestHeaders($request->getMethod());

        return $this->response;
    }

    public function request($method, $uri, array $options = []) {

        $this->uri = $uri;
        $executionStartTime = microtime(true);
        $this->response = $this->client->request($method, $uri, $options);
        $executionEndTime = microtime(true);
        $this->executionTime = round(($executionEndTime - $executionStartTime)*1000);

        $this->modifyRequestHeaders($method);

        return $this->response;
    }

    public function requestAsync($method, $uri, array $options = []) {

        $this->uri = $uri;
        $executionStartTime = microtime(true);
        $this->response = $this->client->requestAsync($method, $uri, $options);
        $executionEndTime = microtime(true);
        $this->executionTime = round(($executionEndTime - $executionStartTime)*1000);

        $this->modifyRequestHeaders($method);

        return $this->response;
    }

    public function getConfig($option = null) {
        return $this->client->getConfig($option);
    }

















    private function modifyRequestHeaders($method) {

        $headers = $this->response->getHeaders();

        $data = array();
        $xDebugdata = array();

        if($this->request->headers->has('X-API-Debug-Data')) {
            $xDebugdata = $this->request->headers->get('X-API-Debug-Data');
        }

        if(array_key_exists('X-Debug-Token', $headers)) {

            $data = ['token' => $this->response->getHeaderLine('X-Debug-Token'), 'profiler' => $this->response->getHeaderLine('X-Debug-Token-Link')];
        }

        $data['method'] = $method;
        $data['status'] = $this->response->getStatusCode();
        $data['url'] = $this->uri;
        $data['time'] = $this->executionTime;

        $xDebugdata[] = $data;

        $this->request->headers->set('X-API-Debug-Data', [$xDebugdata]);
    }
}
