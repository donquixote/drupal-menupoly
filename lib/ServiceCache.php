<?php


class menupoly_ServiceCache {

  protected $factory;
  protected $cache = array();

  function __construct($factory) {
    $this->factory = $factory;
  }

  function __get($key) {
    return $this->get($key);
  }

  function __call($method, $args) {
    $key = serialize(array($method, $args));
    if (!isset($this->cache[$key])) {
      $method = 'call_' . count($args) . '_' . $method;
      if (method_exists($this->factory, $method)) {
        array_unshift($args, $this);
        $service = call_user_func_array(array($this->factory, $method), $args);
      }
      else {
        throw new Exception("Method $method not provided by service factory.");
      }
      $this->cache[$key] = isset($service) ? $service : FALSE;
    }
    return $this->cache[$key];
  }

  function get($key) {
    if (!isset($this->cache[$key])) {
      $method = 'get_' . $key;
      if (method_exists($this->factory, $method)) {
        $service = $this->factory->$method($this);
      }
      else {
        throw new Exception("Method $method not provided by service factory.");
      }
      $this->cache[$key] = isset($service) ? $service : FALSE;
    }
    return $this->cache[$key];
  }

  function reset($key) {
    unset($this->cache[$key]);
  }
}
