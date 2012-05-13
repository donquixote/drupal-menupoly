<?php


class menupoly_MenuTreeLoader {

  protected $_source;
  protected $_min_depth;
  protected $_max_depth;
  protected $_expand;

  protected $_root_key;
  protected $_items = array();
  protected $_submenus = array();

  function __construct(menupoly_MenuItemSource_Interface $source, $min_depth, $max_depth, $expand = TRUE) {
    $this->_source = $source;
    $this->_min_depth = $min_depth;
    $this->_max_depth = $max_depth;
    $this->_expand = $expand;
  }

  function addSeedPath($seed_path) {
    foreach ($this->_source->fetchPathItems($seed_path) as $item) {
      $this->_items[$item['mlid']] = $item;
      if ($item['depth'] + 1 >= $this->_min_depth) {
        $this->_addSeed($item);
      }
    }
    if (!empty($item)) {
      return TRUE;
    }
  }

  function addSeed($seed_key) {
    if (!$seed_key) {
      // The "virtual" root item.
      // TODO: What is this about?
      // $item = (object)array('depth' => 0, 'mlid' => $seed_key, 
    }
    else if ($item = $this->_getItem($seed_key)) {
      if ($item['depth'] + 1 >= $this->_min_depth) {
        $this->_addSeed($item);
      }
      return TRUE;
    }
  }

  protected function _addSeed($item) {
    if ($this->_expand === 'all') {
      $this->_addSeed__expand_all($item);
    }
    else {
      $this->_addSeed__expand_some($item);
    }
    for ($depth = $this->_min_depth; $depth <= $item['depth']; ++$depth) {
      $plid = $item['p'. $depth];
      if ($plid && isset($this->_items[$plid])) {
        $this->_items[$plid]['active_trail'] = TRUE;
      }
    }
    $this->_items[$item['mlid']]['active'] = TRUE;
  }

  protected function _addSeed__expand_all($item) {
    if (!isset($this->_root_key)) {
      if ($this->_min_depth <= 1) {
        $this->_root_key = 0;
        $this->_fillUp();
      }
      else {
        $root_key = $item['p'. ($this->_min_depth - 1)];
        if ($root_key) {
          $this->_root_key = $root_key;
          $this->_source->setRootItem($root_key);
          $this->_fillUp();
        }
      }
    }
  }

  protected function _fillUp() {
    $items = $this->_source->fetchDepthItems($this->_min_depth, $this->_max_depth);
    foreach ($items as $item) {
      $this->_submenus[$item['plid']][$item['mlid']] = $item['mlid'];
      $this->_items[$item['mlid']] = $item;
    }
  }

  protected function _addSeed__expand_some($item) {
    $item['p0'] = 0;
    if ($this->_min_depth <= 1) {
      for ($depth = 0; $depth < $this->_max_depth; ++$depth) {
        $plid = $item['p'. $depth];
        if (!$plid && $depth > 1) break;
        $this->_addSubmenus(array($plid));
      }
    }
    else {
      $root_key = $item['p'. ($this->_min_depth - 1)];
      if ($root_key && !isset($this->_root_key)) {
        $this->_root_key = $root_key;
        $this->_source->setParentItem($root_key);
      }
      if ($root_key && $this->_root_key === $root_key) {
        for ($depth = $this->_min_depth - 1; $depth < $this->_max_depth; ++$depth) {
          $plid = $item['p'. $depth];
          if (!$plid && $depth > 1) break;
          $this->_addSubmenus(array($plid));
        }
      }
    }
  }

  protected function _addSubmenus(array $plids) {
    $plids_missing = array();
    foreach ($plids as $plid) {
      if (!isset($this->_submenus[$plid])) {
        $plids_missing[] = $plid;
      }
    }
    if (empty($plids_missing)) {
      return;
    }
    $expanded_mlids = array();
    foreach ($this->_source->fetchChildItems($plids_missing) as $item) {
      $this->_items[$item['mlid']] = $item;
      $this->_submenus[$item['plid']][$item['mlid']] = $item['mlid'];
      if ($this->_expand && $item['expanded']) {
        if ($item['depth'] < $this->_max_depth) {
          $expanded_mlids[] = $item['mlid'];
        }
      }
    }
    if (!empty($expanded_mlids)) {
      $this->_addSubmenus($expanded_mlids);
    }
  }

  protected function _getItem($mlid) {
    if (!isset($this->_items[$mlid])) {
      if ($item = $this->_source->fetchItem($mlid)) {
        $this->_items[$mlid] = $item;
      }
      else {
        // we assume this item does not exist.
        $this->_items[$mlid] = FALSE;
      }
    }
    return $this->_items[$mlid];
  }

  function getItems() {
    return $this->_items;
  }

  function getSubmenus() {
    return $this->_submenus;
  }

  function getRootKey() {
    return $this->_root_key;
  }
}
