<?php

class Cisco_EPC3925_Upstream_Power_Level implements \PhuninNode\Interfaces\Plugin {
    
    const CISCO_EPC3925_STATUS_URL = 'http://192.168.100.1/Docsis_system.asp';
    const DNS_SERVER_IP = '8.8.8.8';
    const STATUS_TABLE = 3;
    const STATUS_COLUMN = 2;
    
    private $node;
    private $loop;
    private $configuration;
    
    public function __construct($loop) {
        $this->loop = $loop;
        
        $dnsResolverFactory = new \React\Dns\Resolver\Factory();
        $this->dnsResolver = $dnsResolverFactory->createCached(self::DNS_SERVER_IP, $this->loop);
        $this->factory = new \React\HttpClient\Factory();
    }
    
    public function setNode(\PhuninNode\Node $node) {
        $this->node = $node;
    }
    
    public function getSlug() {
        return 'cisco_epc3925_upstream_power_level';
    }
    
    public function getConfiguration(\React\Promise\DeferredResolver $deferredResolver) {
        if ($this->configuration instanceof \PhuninNode\PluginConfiguration) {
            $deferredResolver->resolve($this->configuration);
            return;
        }
        
        $this->configuration = new \PhuninNode\PluginConfiguration();
        $this->configuration->setPair('graph_category', 'cisco_epc3925');
        $this->configuration->setPair('graph_title', 'Upstream Power Level');
        $this->configuration->setPair('graph_vlabel', 'dBmV');
        $this->configuration->setPair('graph_args', '--base 1000 -l 47 -u 54');
        $this->configuration->setPair('graph_info', 'This graph shows the Upstream Power Level in dBmV.');
        
        $configuration = $this->configuration;
        $deferred = new \React\Promise\Deferred();
        $deferred->promise()->then(function($channels) use ($deferredResolver, $configuration) {
            
            foreach ($channels as $channel => $value) {
                $this->configuration->setPair($channel . '.min', 6);
                $this->configuration->setPair($channel . '.max', 12);
                $this->configuration->setPair($channel . '.label', $channel);
                $this->configuration->setPair($channel . '.type', 'GAUGE');
            }
            
            $deferredResolver->resolve($configuration);
        });
        $this->fetchModemStatusValue($deferred->resolver(), self::STATUS_TABLE, self::STATUS_COLUMN);
    }
    
    public function getValues(\React\Promise\DeferredResolver $deferredResolver) {
        $deferred = new \React\Promise\Deferred();
        $deferred->promise()->then(function($channels) use ($deferredResolver) {
            
            $values = new \SplObjectStorage;
            
            foreach ($channels as $channel => $value) {
                $valueObject = new \PhuninNode\Value();
                $valueObject->setKey($channel);
                $valueObject->setValue($value);
                
                $values->attach($valueObject);
            }
            
            $deferredResolver->resolve($values);
        });
        $this->fetchModemStatusValue($deferred->resolver(), self::STATUS_TABLE, self::STATUS_COLUMN);
    }
    
    private function fetchModemStatusValue(\React\Promise\DeferredResolver $deferredResolver, $table, $column) {
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
    
    private function fetchModemStatusUrl(\React\Promise\DeferredResolver $deferredResolver) {
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