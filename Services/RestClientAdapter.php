<?php

namespace Parallalax\ApiDebugBundle\Services;

use \Symfony\Component\HttpFoundation\RequestStack;

use \GuzzleHttp\ClientInterface;
//use \Psr\Http\Message\RequestInterface;
use \GuzzleHttp\Message\RequestInterface;
class RestClientAdapter implements ClientInterface{

    protected $env;//to catch the ws requests only in the dev environment
    protected $request;
    protected $client;//the restClient
    protected $uri;
    protected $sentData;
    protected $executionTime;

    protected $response;

    public function __construct(RequestStack $request, $env) {
        $this->request = $request->getCurrentRequest();
        $this->client = new \GuzzleHttp\Client();
        $this->env = $env;
    }


    public function send(RequestInterface $request, array $options = []) {

        $this->uri = $request->getUrl();

        $this->sentData = $options;
        $executionStartTime = microtime(true);
        $this->response = $this->client->send($request, $options);
        $executionEndTime = microtime(true);
        $this->executionTime = round(($executionEndTime - $executionStartTime)*1000);

        $this->modifyRequestHeaders($request->getMethod());

        return $this->response;
    }

    public function sendAsync(RequestInterface $request, array $options = [])
    {
        $this->uri = $request->getUrl();
        $this->sentData = $options;
        $executionStartTime = microtime(true);
        $this->response = $this->client->sendAsync($request, $options);
        $executionEndTime = microtime(true);
        $this->executionTime = round(($executionEndTime - $executionStartTime)*1000);

        $this->modifyRequestHeaders($request->getMethod());

        return $this->response;
    }

    public function request($method, $uri, array $options = []) {

        $this->uri = $uri;
        $this->sentData = $options;
        $executionStartTime = microtime(true);
        $this->response = $this->client->request($method, $uri, $options);
        $executionEndTime = microtime(true);
        $this->executionTime = round(($executionEndTime - $executionStartTime)*1000);

        $this->modifyRequestHeaders($method);

        return $this->response;
    }

    public function requestAsync($method, $uri, array $options = []) {

        $this->uri = $uri;
        $this->sentData = $options;
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

    /**
     * modify the request with the api calls params to get catched by the dataCollector
     * @param  string $method
     * @return void
     */
    private function modifyRequestHeaders($method) {

        if($this->env == 'dev') {//only useful in dev env
            $headers = $this->response->getHeaders();

            $data = array();
            $dataResponse = array();
            $xDebugdata = array();
            $xDebugResponse = array();

            if($this->request->headers->has('X-API-Debug-Data')) {
                $xDebugdata = $this->request->headers->get('X-API-Debug-Data');
            }

            if($this->request->headers->has('X-API-Response')) {
                $xDebugResponse = $this->request->headers->get('X-API-Response');
            }

            if(array_key_exists('X-Debug-Token', $headers)) {
                $data = ['token' => $headers['X-Debug-Token'][0], 'profiler' => $headers['X-Debug-Token-Link'][0]];
            }

            $data['method'] = $method;
            $data['status'] = $this->response->getStatusCode();
            $data['uri'] = $this->uri;
            $data['time'] = $this->executionTime;

            $xDebugdata[] = $data;

            $dataResponse['sentData'] = $this->sentData;

            //if json : decode the response, else get html length
            if(strpos($headers['Content-Type'][0], 'application/json') !== false) {
                $dataResponse['response'] = ['type' => 'json', 'content' => json_decode($this->response->getBody())];
            }
            else {
                $dataResponse['response'] = ['type' => 'html', 'content' =>  strlen($this->response->getBody()) .'o'];
            }

            $xDebugResponse[] = $dataResponse;

            $this->request->headers->set('X-API-Debug-Data', [$xDebugdata]);
            $this->request->headers->set('X-API-Response', [$xDebugResponse]);
        }
    }






    public function createRequest($method, $url = null, array $options = []) {
        return $this->client->createRequest($method, $url, $options);
    }



    public function get($url = null, $options = []) {
        return $this->client->get($url, $options);
    }

    public function head($url = null, array $options = []) {
        return $this->client->head($url, $options);
    }

    public function delete($url = null, array $options = []) {
        return $this->client->delete($url, $options);
    }

    public function put($url = null, array $options = []) {
        return $this->client->put($url, $options);
    }

    public function patch($url = null, array $options = []) {
        return$this->client->patch($url, $options);
    }

    public function post($url = null, array $options = []) {
        $response = $this->client->post($url, $options);
        print_r(json_decode($response->getBody()));
        return $response;
    }

    public function options($url = null, array $options = []) {
        return $this->client->options($url, $options);
    }

    public function getDefaultOption($keyOrPath = null) {
        return $this->client->getDefaultOption($keyOrPath);
    }

    public function setDefaultOption($keyOrPath, $value) {
        $this->client->options($keyOrPath, $value);
    }

    public function getBaseUrl() {
        return $this->client->getBaseUrl();
    }

    public function getEmitter() {
        return $this->client->getEmitter();
    }
}
