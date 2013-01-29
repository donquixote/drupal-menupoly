<?php


/**
 * Using the {menu_links} db table as a source for menu items.
 */
class menupoly_MenuTreeSource_MenuLinks implements menupoly_MenuTreeSource_Interface {

  protected $trailPaths = array();
  protected $trailItems = array();

  function setTrailPaths(array $paths) {
    $this->trailPaths = $paths;
  }

  function build(array $settings) {

    $settings += array(
      'expand' => MENUPOLY_EXPAND_ACTIVE | MENUPOLY_EXPAND_EXPANDED,
    );
    // TODO: Validate settings.

    // Determine the root item.
    $root_item = $this->_fetchRootItem($settings);
    if ($root_item === FALSE) {
      return NULL;
    }

    // Fetch the actual menu items.
    $q = $this->_selectItems();
    // $q->_applyDepthCondition($root_item, $settings);
    if ($settings['expand'] === MENUPOLY_EXPAND_ALL) {
      // Expand the full tree.
      // Don't add further conditions.
      $this->_applyRootCondition($q, $root_item, $settings);
    }
    else {
      if ($settings['expand'] & MENUPOLY_EXPAND_ACTIVE) {
        $plids = $this->_fetchActiveTrailPlids($root_item, $settings);
      }
      if (isset($plids)) {
        $q->condition('plid', $plids);
        $q->condition('menu_name', $settings['menu_name']);
      }
      elseif (!empty($root_item)) {
        $q->condition('plid', $root_item['mlid']);
      }
      else {
        $q->condition('plid', 0);
        $q->condition('menu_name', $settings['menu_name']);
      }
    }

    $items = $q->execute()->fetchAllAssoc('mlid', PDO::FETCH_ASSOC);

    if ($settings['expand'] & MENUPOLY_EXPAND_EXPANDED) {
      // Expand items set as "expanded".
      $mlids = array();
      foreach ($items as $mlid => $item) {
        $mlids[$mlid] = $mlid;
      }
      if (isset($plids)) {
        // Skip items that are already expanded due to being in the active trail.
        foreach ($plids as $plid) {
          unset($mlids[$plid]);
        }
      }
      $this->_expandExpanded($items, $mlids);
    }

    menupoly_items_check_access($items);

    // Mark the active trail.
    if (!isset($plids)) {
      $plids = $this->_fetchActiveTrailPlids($root_item, $settings);
    }
    if (is_array($plids)) {
      foreach ($plids as $plid) {
        if (isset($items[$plid])) {
          $items[$plid]['active-trail'] = TRUE;
        }
      }
    }

    // Build the MenuTree object.
    $root_mlid = !empty($root_item) ? $root_item['mlid'] : 0;
    $tree = new menupoly_MenuTree($root_mlid);
    $tree->addItems($items);
    return $tree;
  }

  protected function _expandExpanded(&$items, array $plids) {
    $expanded_mlids = array();
    foreach ($plids as $k => $plid) {
      if (empty($items[$plid]['expanded'])) {
        unset($plids[$k]);
      }
    }
    $q = $this->_selectItems();
    $q->condition('plid', $plids);
    $items_new = $q->execute()->fetchAllAssoc('mlid', PDO::FETCH_ASSOC);
    $plids = array_keys($items_new);
    $items += $items_new;
    $this->_expandExpanded($items, $plids);
  }

  protected function _fetchActiveTrailPlids($root_item, $settings) {
    // Determine the active trail.
    if (!empty($settings['follow'])) {
      return;
    }
    $deep_trail_item = $this->_fetchDeepestTrailItem($root_item, $settings);
    if (empty($deep_trail_item)) {
      return;
    }
    $plids = array(0);
    // TODO: Determine min depth.
    $min_depth = 1;
    for ($i = 1; isset($deep_trail_item['p' . $i]); ++$i) {
      $plids[] = $deep_trail_item['p' . $i];
    }
    return $plids;
  }

  protected function _fetchDeepestTrailItem($root_item, array $settings) {
    $q = db_select('menu_links', 'ml')->fields('ml');
    $this->_applyRootCondition($q, $root_item, $settings);
    $q->condition('link_path', $this->trailPaths);
    $q->orderBy('depth', 'DESC');
    $q->range(0, 1);
    return $q->execute()->fetchAssoc();
  }

  protected function _fetchRootItem(array $settings) {
    $root_item = $this->_fetchSettingsRootItem($settings);
    if (empty($settings['follow'])) {
      return $root_item;
    }
    if (FALSE === $root_item) {
      return FALSE;
    }
    // Find deepest trail item within the current menu / submenu
    $root_item = $this->_fetchDeepestTrailItem($root_item, $settings);
    if (empty($root_item)) {
      return FALSE;
    }
    return $root_item;
  }

  protected function _fetchSettingsRootItem(array $settings) {
    $q = db_select('menu_links', 'ml')->fields('ml');
    if (!empty($settings['root_mlid'])) {
      $q->condition('mlid', $settings['root_mlid']);
    }
    elseif (!empty($settings['root_path'])) {
      $q->condition('link_path', $settings['root_path']);
    }
    elseif (empty($settings['menu_name'])) {
      return FALSE;
    }
    else {
      return NULL;
    }
    $item = $q->execute()->fetchAssoc();
    if (empty($item)) {
      return FALSE;
    }
    if (
      isset($settings['menu_name']) &&
      $item['menu_name'] !== $settings['menu_name']
    ) {
      return FALSE;
    }
    return $item;
  }

  protected function _applyRootCondition($q, $root_item, $settings) {
    if (!empty($root_item)) {
      $q->condition('p'. $root_item['depth'], $root_item['mlid']);
    }
    elseif (!empty($settings['menu_name'])) {
      $q->condition('menu_name', $settings['menu_name']);
    }
    else {
      throw new Exception("No root item and no menu name are given.");
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
    return $q;
  }
}
