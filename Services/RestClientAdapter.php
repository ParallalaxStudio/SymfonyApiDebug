<?php

namespace Parallalax\ApiDebugBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\RequestStack;

use \GuzzleHttp\ClientInterface;
use \Psr\Http\Message\RequestInterface;

class RestClientAdapter implements ClientInterface{

    protected $env;//to catch the ws requests only in the dev environment
    protected $request;
    protected $client;//the restClient
    protected $uri;
    protected $sentData;
    protected $executionTime;

    protected $response;

    public function __construct(RequestStack $request, $env) {
        $this->request = ($request->getCurrentRequest() === null) ? new Request() : $request->getCurrentRequest();
        $this->client = new \GuzzleHttp\Client();
        $this->env = $env;
    }


    public function send(RequestInterface $request, array $options = []) {

        $this->uri = $request->getUri();
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
        $this->uri = $request->getUri();
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
                $xDebugdata = json_decode($this->request->headers->get('X-API-Debug-Data'));
            }

            if($this->request->headers->has('X-API-Response')) {
                $xDebugResponse = json_decode($this->request->headers->get('X-API-Response'));
            }

            if(array_key_exists('X-Debug-Token', $headers)) {
                $data = ['token' => $this->response->getHeaderLine('X-Debug-Token'), 'profiler' => $this->response->getHeaderLine('X-Debug-Token-Link')];
            }

            $data['method'] = $method;
            $data['status'] = $this->response->getStatusCode();
            $data['uri'] = $this->uri;
            $data['time'] = $this->executionTime;

            $xDebugdata[] = $data;

            $dataResponse['sentData'] = $this->sentData;

            //if json : decode the response, else get html length
            if(strpos($this->response->getHeaderLine('content-type'), 'application/json') !== false) {
                $resultData = json_decode($this->response->getBody());
                $resultToShow = array();
                foreach($resultData as $key => $r) {
                    $resultToShow[$key] = (is_string($r) && strlen($r) > 300) ? (strlen($r)*3/4) .'o' : $r;
                }
                $dataResponse['response'] = ['type' => 'json', 'content' => $resultToShow];
            }
            else {
                $dataResponse['response'] = ['type' => 'html', 'content' =>  strlen($this->response->getBody())*3/4 .'o'];
            }

            $xDebugResponse[] = $dataResponse;

            //$this->request->headers->set('X-API-Debug-Data', [$xDebugdata]);
            $this->request->headers->set('X-API-Debug-Data', json_encode($xDebugdata));
            $this->request->headers->set('X-API-Response', json_encode($xDebugResponse));
        }
    }
}
