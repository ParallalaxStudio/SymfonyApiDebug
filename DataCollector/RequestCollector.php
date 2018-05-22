<?php

namespace Parallalax\ApiDebugBundle\DataCollector;

use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RequestCollector extends DataCollector
{

    public function collect(Request $request, Response $response, \Exception $exception = null)
    {

        $headers = $request->headers->all();

        if(array_key_exists('x-api-debug-data', $headers)) {
            $this->data = array(
                'api_debug_data' => $request->headers->get('x-api-debug-data')
            );

            $response->headers->set('x-api-debug-data', json_encode($request->headers->get('x-api-debug-data')));
        }
    }

    public function reset()
    {
        $this->data = array();
    }

    public function getApiDebugData()
    {
        return isset($this->data['api_debug_data']) ? $this->data['api_debug_data'] : null;
    }

    public function getName()
    {
        return 'parallalax.request_collector';
    }
}
