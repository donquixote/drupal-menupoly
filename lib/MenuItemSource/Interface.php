<?php


interface menupoly_MenuItemSource_Interface {

  function setRootItem($new_root_key);

  /**
   * @param $min_depth
   * @param $max_depth
   */
  function hasItems($min_depth = NULL, $max_depth = NULL);

  /**
   * @param $item_key
   *   A key to identify a menu item.
   *   For menu links this would be the mlid.
   *   A custom structure could have a different primary key for menu items.
   */
  function fetchItem($item_key);

  /**
   * @param $parent_keys
   *   A key to identify the parent item.
   *   For menu links this would be the mlid / plid.
   *   A custom structure could have a different primary key for menu items.
   */
  function fetchChildItems($parent_keys);

  /**
   * @param $min_depth
   * @param $max_depth
   */
  function fetchDepthItems($mind_depth = NULL, $max_depth = NULL);
}
