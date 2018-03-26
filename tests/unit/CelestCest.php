<?php

namespace Celest;

class CelestCest {

    private $tpl;

    public function _before() {
        $this->tpl = new Celest('test %key% %key2.subkey%');
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
    
    public function render() {
        $this->tpl->inject(['key' => 'ok']);
        expect($this->tpl->render(true))->equals('test ok %key2.subkey%');
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
