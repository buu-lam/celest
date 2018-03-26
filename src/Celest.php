<?php
namespace Celest;

class Celest {

    /** @var string */
    protected $template = '';
    
    /** @var hash|of|hash */
    protected $keys = [];
    
    /** @var string */
    protected $delimiter = '%';
    
    /** @var string */
    protected $sep = '.';

    /**
     * 
     * @param string $template
     * @param hash $options
     */
    public function __construct($template, $options = []) {
        $this->template = $template;
        $this->initOptions($options);
        $this->nodes = explode($this->delimiter, $this->template);
        $this->prepareKeys();
    }

    /**
     * 
     * @param hash $options
     * @return $this
     */
    public function initOptions($options) {
        foreach(['delimiter', 'sep'] as $key) {
            if (!empty($options[$key])) {
                $this->$key = $options[$key];
            }
        }
        return $this;
    }
    
    private function prepareKeys() {
        $count = count($this->nodes);
        for ($rnk = 1; $rnk < $count; $rnk += 2) {
            $key = $this->nodes[$rnk];
            if (!isset($this->keys[$key])) {
                $this->keys[$key] = ['value' => null, 'nodes' => []];
            }
            $this->keys[$key]['nodes'][] = $rnk;
        }
    }
    
    /**
     * 
     * @param hash|object $data
     * @return $this
     */
    public function inject($data) {
        $flattern = $this->flatternData($data);
        foreach ($flattern as $key => $value) {
            if (isset($this->keys[$key])) {
                $this->keys[$key]['value'] = $value;
            }
        }
        return $this;
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

    /**
     * 
     * @return string
     */
    public function render($keepKeys = false) {
        $nodes = $this->nodes;
        foreach ($this->keys as $key => $props) {
            foreach ($props['nodes'] as $rnk) {
                $nodes[$rnk] = isset($props['value']) ?
                    $props['value'] :
                    ($keepKeys ? "$this->delimiter$key$this->delimiter": '')
                ;
            }
        }
        return implode('', $nodes);
    }

    public function __toString() {
        return $this->render();
    }
}
