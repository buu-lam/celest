<?php
namespace Celest;

class Celest {

    /** @var string */
    protected $template = '';
    
    /** @var string */
    protected $string = '';
    
    /** @var hash|of|hash */
    protected $keys = [];

    /** @var string */
    protected $modify = ':';
    
    /** @var string[] */
    protected $modifiers = [];
    
    /** @var string[] */
    protected $keysToModify = [];
    
    /** @var string */
    protected $delimiter = '%';
    
    /** @var string */
    protected $sep = '.';
    
    /** @var null|array|of|Celest */
    protected $collection;
    
    /** @var string */
    protected $join = '';
    
    /** @var string */
    protected $zones = [];
    
    /** @var string */
    protected $zoneStart = '<!--@';
    
    /** @var string */
    protected $zoneStop = '@-->';
    
    /** @var string */
    protected $zoneSubStart = '<!--{';
    
    /** @var string */
    protected $zoneSubStop = '}-->';

    /** @var string */
    protected $zoneSupStart = '<!--';
    
    /** @var string */
    protected $zoneSupStop = '-->';
    
    /**
     * 
     * @param string $template
     * @param hash $options
     */
    public function __construct($template, $options = []) {
        $this->initOptions($options);
        $this->genZones ($template);
        $this->prepareKeys();
        $this->prepareKeysToModify();
    }

    /**
     * 
     * @param hash $options
     * @return $this
     */
    public function initOptions($options) {
        $this->options = $options;
        foreach(['delimiter', 'sep', 'join',
            'zoneStart', 'zoneStop', 
            'zoneSubStart', 'zoneSubStop', 
            'zoneSupStart', 'zoneSupStop', 
            'modify', 'modifiers'] as $key) {
            if (!empty($options[$key])) {
                $this->$key = $options[$key];
            }
        }
        return $this;
    }
    
    private function genZones($template) {
        $this->template = $template;
        $parts = explode($this->zoneStart, $this->template);
        $this->string = array_shift($parts);
        foreach($parts as $part) {
            list($zoneKey, $subtemplate) = explode($this->zoneStop, $part);
            $template = str_replace(
                [$this->zoneSubStart, $this->zoneSubStop], 
                [$this->zoneSupStart, $this->zoneSupStop],
                $subtemplate
            );
            $this->zones[$zoneKey] = new static($template, $this->options);
        }
    }
    
    private function prepareKeys() {
        $this->nodes = explode($this->delimiter, $this->string);
        $count = count($this->nodes);
        for ($rnk = 1; $rnk < $count; $rnk += 2) {
            $key = $this->nodes[$rnk];
            if (!isset($this->keys[$key])) {
                $this->keys[$key] = ['value' => null, 'nodes' => []];
            }
            $this->keys[$key]['nodes'][] = $rnk;
        }
    }
    
    private function prepareKeysToModify() {
        foreach($this->keys as $key => $props) {
            if (strpos($key, $this->modify) === false) {
                continue;
            }
            list($keyToModify, $modifier) = explode($this->modify, $key);
            if (!isset($this->keysToModify[$keyToModify])) {
                $this->keysToModify[$keyToModify] = [];
            }
            if (!in_array($modifier, $this->keysToModify[$keyToModify])) {
                $this->keysToModify[$keyToModify][] = $modifier;
            }
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
            if (isset($this->keysToModify[$key])) {
                $this->injectModifier($key, $value);
            }
        }
        foreach($this->zones as $zone) {
            $zone->inject($data);
        }
        return $this;
    }
    
    public function injectModifier($key, $value) {
        foreach($this->keysToModify[$key] as $modifier) {
            $callable = $this->modifiers[$modifier];
            $this->keys["$key:$modifier"]['value'] = $callable($value);
        }
    }
    
    /**
     * 
     * @param string $join
     * @return $this
     */
    public function join($join) {
        $this->join = $join;
        return $this;
    }
    
    /**
     * 
     * @param array|of|hash $collection
     * @return $this
     */
    public function injectArray($collection) {
        $this->collection = [];
        foreach($collection as $data) {
            $this->push($data);
        }
        return $this;
    }

    /**
     * 
     * @param hash $data
     * @return $this
     */
    public function push($data) {
        $item = new static($this->template, $this->options);
        $item->inject($data);
        $this->collection[] = $item;
        return $this;
    }
    
    /**
     * 
     * @param string $zoneKey
     * @return Celest
     */
    public function getZone($zoneKey) {
        return isset($this->zones[$zoneKey]) ?
            $this->zones[$zoneKey] : null
        ;
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
        if (is_array($this->collection)) {
            return implode($this->join, array_map(function($item) use($keepKeys) {
                return $item->render($keepKeys);
            }, $this->collection));
        }
        
        $nodes = $this->nodes;
        foreach ($this->keys as $key => $props) {
            foreach ($props['nodes'] as $rnk) {
                $nodes[$rnk] = isset($props['value']) ?
                    $props['value'] :
                    ($keepKeys ? "$this->delimiter$key$this->delimiter": '')
                ;
            }
        }
        return implode('', $nodes) . implode('', array_map(function($item) use($keepKeys) {
                return $item->render($keepKeys);
        }, $this->zones));
    }

    public function __toString() {
        return $this->render();
    }
}
