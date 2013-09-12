<?php


/**
 * A menu tree ready to render.
 */
class menupoly_MenuTree {

  protected $_rootMlid;
  protected $_submenus = array();
  protected $_items = array();

  function __construct($root_mlid = 0) {
    $this->_rootMlid = $root_mlid;
  }

  /**
   * Add items and their parent/child relationships.
   */
  function addItems($items) {
    foreach ($items as $mlid => $item) {
      $plid = !empty($item['plid']) ? $item['plid'] : 0;
      $this->_submenus[$plid][$mlid] = $mlid;
      $this->_items[$mlid] = $item;
    }
  }

  function getSubmenuItems($parent_mlid) {
    $submenu = $this->_submenus[$parent_mlid];
    if (is_object($submenu)) {
      return $submenu;
    }
    else if (is_array($submenu)) {
      $submenu_sorted = array();
      foreach ($submenu as $k => $mlid) {
        if ($item = $this->_items[$mlid]) {
          if ($this->_checkItemAccess($item)) {
            $submenu_sorted[$mlid] = $item;
            $sort[$mlid] = (50000 + $item['weight']) .' '. $item['title'];
          }
        }
      }
      array_multisort($sort, $submenu_sorted);
      return $submenu_sorted;
    }
  }

  function render($theme = NULL) {
    return $this->renderSubmenu($theme, $this->_rootMlid, 1);
  }

  protected function renderSubmenu($theme, $parent_mlid, $depth) {
    $html = '';
    $submenu = @$this->_submenus[$parent_mlid];
    if (is_object($submenu)) {
      return $submenu->render($this);
    }
    else if (is_array($submenu)) {
      $submenu_sorted = array();
      $sort = array();
      foreach ($submenu as $k => $mlid) {
        if ($item = @$this->_items[$mlid]) {
          if ($this->_checkItemAccess($item)) {
            $submenu_sorted[$k] = $mlid;
            $sort[$k] = (50000 + $item['weight']) .' '. $item['title'];
          }
        }
      }
      array_multisort($sort, $submenu_sorted);

      $items = array();
      foreach ($submenu_sorted as $k => $mlid) {
        $items[$k] = $this->_items[$mlid];
      }
      $theme->processSubmenuItems($items);

      $pieces = array();
      foreach ($items as $k => $item) {
        $mlid = $submenu_sorted[$k];
        $options = $item['localized_options'];
        $options = is_array($options) ? $options : array();
        $subtree_html = $this->renderSubmenu($theme, $mlid, $depth + 1);
        $pieces[$k] = $theme->renderMenuItem($item, $options, $subtree_html, $depth);
      }
      if (!empty($pieces)) {
        return $theme->renderMenuTree($pieces, $depth);
      }
    }
    return '';
  }

  protected function _checkItemAccess(&$item) {
    if (!isset($item['access'])) {
      // late access check
      $router_item = menu_get_item($item['link_path']);
      return $router_item['access'];
    }
    return $item['access'];
  }
}
