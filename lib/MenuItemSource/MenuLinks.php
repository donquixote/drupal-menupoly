<?php


/**
 * Using the {menu_links} db table as a source for menu items.
 */
class menupoly_MenuItemSource_MenuLinks implements menupoly_MenuItemSource_Interface {

  protected $menuName;
  protected $rootMlid;
  protected $rootDepth = -1;
  protected $frontPath;

  function __construct($menu_name) {
    $this->menuName = $menu_name;
    $this->frontPath = drupal_get_normal_path(variable_get('site_frontpage', 'node'));
  }

  function setRootItem($new_root_mlid) {
    $new_root_item = $this->fetchItem($new_root_mlid);
    if ($new_root_item && $new_root_item['depth'] > $this->rootDepth) {
      $this->rootMlid = $new_root_mlid;
      $this->rootDepth = $new_root_item['depth'];
    }
  }

  function setRootPath($new_root_path) {
    $new_root_items = fetchPathItems($new_root_path);
    $new_root_depth = 10;
    // choose the matching item with the smallest depth.
    foreach ($new_root_items as $candidate) {
      if ($candidate['depth'] < $new_root_depth) {
        $new_root_mlid = $candidate['mlid'];
        $new_root_depth = $candidate['depth'];
      }
    }
    if ($new_root_mlid) {
      $this->setRootItem($new_root_mlid);
    }
  }

  function hasItems($min_depth = NULL, $max_depth = NULL) {
    $q = $this->_selectMenuLinks()->fields('ml', array('mlid'));
    if (isset($min_depth)) {
      $q->condition('depth', $min_depth, '>=');
    }
    if (isset($max_depth)) {
      $q->condition('depth', $max_depth, '<=');
    }
    $q->limit(1);
    return TRUE && $q->execute()->fetchAssoc();
  }

  function fetchItem($mlid) {
    return $this->_selectItems()->condition('mlid', $mlid)->fetchAssoc();
  }

  function fetchChildItems($plids) {
    if (is_numeric($plids)) {
      $plids = array($plids);
    }
    if (is_array($plids) && !empty($plids)) {
      $q = $this->_selectItems()->condition('plid', $plids);
      return $q->execute()->fetchAllAssoc('mlid', PDO::FETCH_ASSOC);
    }
    else {
      return array();
    }
  }

  function fetchPathItems($link_path) {
    $q = $this->_selectItems();
    if ($this->frontPath === $link_path) {
      // menu_links stores '<front>'
      $q->condition('link_path', array('<front>', $link_path));
    }
    else {
      $q->condition('link_path', $link_path);
    }
    return $q->execute()->fetchAllAssoc('mlid', PDO::FETCH_ASSOC);
  }

  function fetchDepthItems($min_depth = NULL, $max_depth = NULL) {
    $q = $this->_selectItems();
    if (isset($min_depth)) {
      $q->condition('depth', $min_depth, '>=');
    }
    if (isset($max_depth)) {
      $q->condition('depth', $max_depth, '<=');
    }
    return $q->execute()->fetchAllAssoc('mlid', PDO::FETCH_ASSOC);
  }

  protected function _findItemDepth($mlid) {
    $q = db_select('menu_links')->fields('depth')->condition('mlid', $mlid);
    if ($row = $q->fetchObject()) {
      return $row->depth;
    }
  }

  protected function _selectItems() {
    $q = $this->_selectMenuLinks()->fields('ml');
    $q->leftJoin('menu_router', 'm', 'm.path = ml.router_path');
    $q->fields('m', array(
      'load_functions', 'to_arg_functions', 'access_callback',
      'access_arguments', 'page_callback', 'page_arguments', 'title',
      'title_callback', 'title_arguments', 'type', 'description',
    ));
    for ($i = 1; $i <= 9; ++$i) {
      $q->orderBy("p$i", 'ASC');
    }
    return $q;
  }

  protected function _selectMenuLinks() {
    $q = db_select('menu_links', 'ml');
    $q->condition('hidden', 1, '!=');
    if (!empty($this->rootMlid)) {
      $q->condition('p'. $this->rootDepth, $this->rootMlid);
    }
    else {
      $q->condition('menu_name', $this->menuName);
    }
    return $q;
    
  }
}
