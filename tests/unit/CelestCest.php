<?php

namespace Celest;

class CelestCest {

    private $tpl;
    private $nestingTpl;

    public function _before() {
        $this->tpl = new Celest('test %key% %key2.subkey%');
        $this->nestingTpl = new Celest('test %key%<!--@zone@--> %key%<!--{@nested@}--> %key%');
    }

    public function inject_nothing() {
        expect($this->tpl->render())->equals('test  ');
    }
    
    public function inject_keys() {
        $this->tpl->inject(['key' => 'ok', 'key2' => ['subkey' => 'ok2']]);
        expect($this->tpl->render())->equals('test ok ok2');
    }

    public function inject_reinject_keys() {
        $this->tpl->inject(['key' => 'ok', 'key2' => ['subkey' => 'ok2']]);
        $this->tpl->inject(['key2' => ['subkey' => 'ok3']]);
        expect($this->tpl->render())->equals('test ok ok3');
    }

    public function inject_nested() {
        $this->nestingTpl->inject(['key' => 'ok']);
        expect($this->nestingTpl->render())->equals('test ok ok ok');
        
        $this->nestingTpl->getZone('zone')->inject(['key' => 'ok2']);
        expect($this->nestingTpl->render())->equals('test ok ok2 ok2');
        
        $this->nestingTpl->getZone('zone')->getZone('nested')->inject(['key' => 'ok3']);
        expect($this->nestingTpl->render())->equals('test ok ok2 ok3');
    }
    
    public function render() {
        $this->tpl->inject(['key' => 'ok']);
        expect($this->tpl->render(true))->equals('test ok %key2.subkey%');
    }
    
    public function injectArray() {
        $this->tpl->injectArray([
            ['key' => 'ok1', 'key2' => ['subkey' => 'ok11']],
            ['key' => 'ok2', 'key2' => ['subkey' => 'ok21']]
        ]);
        expect($this->tpl->render())->equals('test ok1 ok11test ok2 ok21');
    }
    
    public function push() {
        $this->tpl->push(['key' => 'ok2', 'key2' => ['subkey' => 'ok21']]);
        expect($this->tpl->render())->equals('test ok2 ok21');
    }
    
    public function to_string() {
        expect("$this->tpl")->equals('test  ');
    }

    public function options() {
        $tpl = new Celest('test #key# #key2/sub#', ['delimiter' => '#', 'sep' => '/']);
        $tpl->inject(['key' => 'ok']);
        $tpl->inject(['key2' => ['sub' => 'ok2']]);
        expect("$tpl")->equals('test ok ok2');
    }
}
