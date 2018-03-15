<?php
namespace Celest;

class Celest {

    protected $template = '';
    protected $keys = [];
    protected $delimiter = '%';
    protected $sep = '.';

    public function __construct($template, $options = []) {
        $this->template = $template;
        $this->initOptions($options);
        $this->nodes = explode($this->delimiter, $this->template);
        $this->prepareKeys();
    }

    public function initOptions($options) {
        foreach(['delimiter', 'sep'] as $key) {
            if (!empty($options[$key])) {
                $this->$key = $options[$key];
            }
        }
    }
    
    private function prepareKeys() {
        $count = count($this->nodes);
        for ($rnk = 1; $rnk < $count; $rnk += 2) {
            $key = $this->nodes[$rnk];
            if (!isset($this->keys[$key])) {
                $this->keys[$key] = ['value' => '', 'nodes' => []];
            }
            $this->keys[$key]['nodes'][] = $rnk;
        }
    }

    public function render() {
        $nodes = $this->nodes;
        foreach ($this->keys as $props) {
            foreach ($props['nodes'] as $rnk) {
                $nodes[$rnk] = $props['value'];
            }
        }
        return implode('', $nodes);
    }

    public function __toString() {
        return $this->render();
    }
    
    public function inject($data) {
        $flattern = $this->flatternData($data);
        foreach ($flattern as $key => $value) {
            if (isset($this->keys[$key])) {
                $this->keys[$key]['value'] = $value;
            }
        }
    }

    private function flatternData($data, $prefix = '') {
        $flattern = [];
        foreach ($data as $key => $value) {
            if (is_object($value) or is_array($value)) {
                $flattern += $this->flatternData($value, "$prefix$key$this->sep");
            } else {
                $flattern["$prefix$key"] = $value;
            }
        }
        return $flattern;
    }

}
