<?php


/**
 * A menu tree ready to render.
 *
 * How to use:
 * - Construct with a root mlid.
 * - Use $tree->addItems() to grow the menu tree.
 * - Use $tree->render() to produce the HTML.
 */
class menupoly_MenuTree {

  /**
   * @var int|string
   *   Root mlid to start with.
   */
  protected $_rootMlid;

  /**
   * @var array
   *   Array representing the menu hierarchy.
   *   The array itself is only two levels deep, but it can represent a
   *   hierarchy of any depth.
   *
   *   Possible value:
   *
   *     array(
   *       1 => array(3, 4),
   *       2 => array(5, 6),
   *       3 => array(7, 8),
   *     );
   *
   *   The hierarchy would be:
   *
   *      1:
   *        3:
   *          7:
   *          8:
   *        4:
   *      2:
   *        5:
   *        6:
   */
  protected $_submenus = array();

  /**
   * @var array
   *   Items to be rendered. Array keys are the mlids.
   *   All items are at the top level of this array, the array does NOT reflect
   *   the menu hierarchy.
   */
  protected $_items = array();

  /**
   * @param int|string $root_mlid
   *   The root mlid to start with.
   */
  function __construct($root_mlid = 0) {
    $this->_rootMlid = $root_mlid;
  }

  /**
   * Add items and their parent/child relationships.
   *
   * @param array $items
   *   Items to be added.
   */
  function addItems($items) {
    foreach ($items as $mlid => $item) {
      $plid = !empty($item['plid']) ? $item['plid'] : 0;
      $this->_submenus[$plid][$mlid] = $mlid;
      $this->_items[$mlid] = $item;
    }
  }

  /**
   * Get items for a specific submenu.
   * This is mostly just used internally..
   *
   * @param int|string $parent_mlid
   *   Parent mlid identifying the submenu.
   * @return array
   *   Sorted menu items for this submenu.
   */
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

  /**
   * Render the menu.
   *
   * @param menupoly_MenuTheme_Interface $theme
   *   Object with methods to render various parts of the menu.
   *
   * @return string
   *   Rendered HTML code.
   */
  function render($theme = NULL) {
    return $this->renderSubmenu($theme, $this->_rootMlid, 1);
  }

  /**
   * Render a specific submenu.
   *
   * @param menupoly_MenuTheme_Interface $theme
   *   Object with methods to render various parts of the menu.
   *   @todo: This should not be optional!
   * @param int|string $parent_mlid
   *   Parent mlid identifying the submenu.
   * @param int $depth
   *   Depth of the submenu relative to the root item.
   *
   * @return string
   *   Rendered HTML code for this submenu.
   */
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
      $theme->processSubmenuItems($items, $depth);

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

  /**
   * Check access for a single menu item.
   *
   * @param array $item
   *   The item to check access for.
   *   In many cases this already has $item['access'] set, which makes our life
   *   quite easy.
   *
   * @return bool
   *   TRUE, if access is granted.
   */
  protected function _checkItemAccess(&$item) {
    if (!isset($item['access'])) {
      // late access check
      $router_item = menu_get_item($item['link_path']);
      return $router_item['access'];
    }
    return $item['access'];
  }
}
