<?php

abstract class AbstractCiscoEPC3925 {
    
    const CISCO_EPC3925_STATUS_URL = 'http://192.168.100.1/Docsis_system.asp';
    const DNS_SERVER_IP = '8.8.8.8';
    
    private $node;
    private $loop;
    protected $configuration;
    
    public function __construct($loop) {
        $this->loop = $loop;
        
        $dnsResolverFactory = new \React\Dns\Resolver\Factory();
        $this->dnsResolver = $dnsResolverFactory->createCached(self::DNS_SERVER_IP, $this->loop);
        $this->factory = new \React\HttpClient\Factory();
    }
    
    public function setNode(\PhuninNode\Node $node) {
        $this->node = $node;
    }
    
    protected function fetchModemStatusValue(\React\Promise\DeferredResolver $deferredResolver, $table, $column) {
        $deferred = new \React\Promise\Deferred();
        $deferred->promise()->then(function($html) use ($deferredResolver, $table, $column) {
            $channelValues = array();
            
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);
            
            $i = 0;
            $rows = $xpath->query('.//tr[position()>1]', $xpath->query('//table[contains(@class, \'std\')]')->item($table));
            foreach ($rows as $row) {
                $channelValues['channel' . ++$i] = (float) $xpath->query('.//td[' . $column . ']', $row)->item(0)->textContent;
            }
            
            $deferredResolver->resolve($channelValues);
        });
        $this->fetchModemStatusUrl($deferred->resolver());
    }
    
    protected function fetchModemStatusUrl(\React\Promise\DeferredResolver $deferredResolver) {
        $client = $this->factory->create($this->loop, $this->dnsResolver);
        
        $request = $client->request('GET', self::CISCO_EPC3925_STATUS_URL);
        $request->on('response', function ($response) use ($deferredResolver) {
            $dataBuffer = new stdClass();
            $response->on('data', function ($data) use ($dataBuffer) {
                $dataBuffer->buffer .= $data;
            });
            $response->on('end', function () use ($dataBuffer , $deferredResolver) {
                $deferredResolver->resolve($dataBuffer->buffer);
            });
        });
        $request->end();
    }
    
}