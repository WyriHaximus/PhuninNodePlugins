<?php

require_once 'AbstractCiscoEPC3925.php';

class Cisco_EPC3925_Downstream_Signal_To_Noise_Ratio extends AbstractCiscoEPC3925 implements \PhuninNode\Interfaces\Plugin {
    
    const STATUS_TABLE = 3;
    const STATUS_COLUMN = 3;
    
    public function getSlug() {
        return 'cisco_epc3925_downstream_signal_to_noise_ratio_channels';
    }
    
    public function getConfiguration(\React\Promise\DeferredResolver $deferredResolver) {
        if ($this->configuration instanceof \PhuninNode\PluginConfiguration) {
            $deferredResolver->resolve($this->configuration);
            return;
        }
        
        $this->configuration = new \PhuninNode\PluginConfiguration();
        $this->configuration->setPair('graph_category', 'cisco_epc3925');
        $this->configuration->setPair('graph_title', 'Downstream Signal to Noise Ratio');
        $this->configuration->setPair('graph_vlabel', 'dB');
        $this->configuration->setPair('graph_args', '--base 1000 -l 37 -u 42');
        $this->configuration->setPair('graph_info', 'This graph shows the Downstream Signal to Noise Ratio in dB.');
        
        $configuration = $this->configuration;
        $deferred = new \React\Promise\Deferred();
        $deferred->promise()->then(function($channels) use ($deferredResolver, $configuration) {
            
            foreach ($channels as $channel => $value) {
                $this->configuration->setPair($channel . '.min', 37);
                $this->configuration->setPair($channel . '.max', 42);
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