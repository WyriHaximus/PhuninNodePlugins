<?php

require_once 'AbstractCiscoEPC3925.php';

class Cisco_EPC3925_Upstream_Power_Level extends AbstractCiscoEPC3925 implements \PhuninNode\Interfaces\Plugin {
    
    const STATUS_TABLE = 4;
    const STATUS_COLUMN = 2;
    
    public function getSlug() {
        return 'cisco_epc3925_upstream_power_level_channels';
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
                $this->configuration->setPair($channel . '.min', 47);
                $this->configuration->setPair($channel . '.max', 54);
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
    
}