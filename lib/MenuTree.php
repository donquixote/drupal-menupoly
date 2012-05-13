<?php


/**
 * A menu tree ready to render.
 */
class menupoly_MenuTree {

  protected $_submenus;
  protected $_items;
  protected $_root_key;

  function __construct(array $submenus, array $items, $root_key = 0) {
    $this->_submenus = $submenus;
    $this->_items = $items;
    $this->_root_key = $root_key;
  }

  function getRootItems() {
    return $this->getSubmenuItems($this->_root_key);
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
    return $this->_renderSubmenu($theme, $this->_root_key, 1);
  }

  protected function _renderSubmenu($theme, $parent_mlid, $depth) {
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
        $subtree_html = $this->_renderSubmenu($theme, $mlid, $depth + 1);
        $pieces[$k] = $theme->renderMenuItem($item, $options, $subtree_html);
      }
      if (!empty($pieces)) {
        return $theme->renderMenuTree($pieces);
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
