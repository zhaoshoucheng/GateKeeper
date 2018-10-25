<?php

namespace Test;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Client;

class BaseTestCase extends TestCase
{
    protected $client;

    public function __construct(string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->client = new Client([
            'base_uri' => 'http://localhost:8000',
        ]);
    }

    protected function move(string $uri, array $params = [], $errno = 0, callable $callable = null)
    {
        $res = $this->request($uri, $params);

        if ($callable != null) {
            call_user_func($callable, $res);
        }

        return $this->assert($res, $errno);
    }

    protected function request(string $uri, array $params = [])
    {
        return $this->client->post($uri, [
            'form_params' => $params,
        ]);
    }

    protected function assert(ResponseInterface $res, $errno = 0)
    {
        $this->assertEquals(200, $res->getStatusCode());

        $data = json_decode($res->getBody(), true);

        $this->assertArrayHasKey('errno', $data);
        $this->assertArrayHasKey('errmsg', $data);
        $this->assertArrayHasKey('data', $data);

        $this->assertEquals($data['errno'], $errno, 'Error Code: ' . $data['errno'] . "\n" . 'Error Message: ' . $data['errmsg']);

        return $data['data'];
    }
}