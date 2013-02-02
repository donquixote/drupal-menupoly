<?php

class menupoly_MenuTreeSource_MenuLinks_RootCondition_RootItem {

  protected $rootItem;

  function __construct($root_item) {
    $this->rootItem = $root_item;
  }

  function apply($q) {
    $q->condition('p'. $this->rootItem['depth'], $this->rootItem['mlid']);
  }

  function applyFinal($q, $settings, $trail_mlids) {
    $mindepth = $this->rootItem['depth'] + 1;
    $maxdepth = isset($settings['depth']) ? $mindepth + $settings['depth'] - 1 : NULL;
    $depth = isset($settings['depth']) ? $settings['depth'] : NULL;
    if ($settings['expand'] === MENUPOLY_EXPAND_ALL) {
      // Expand the full tree.
      // Don't add further conditions.
      $this->apply($q);
      if (isset($maxdepth)) {
        $q->condition('depth', $maxdepth, '<=');
      }
    }
    else {
      if ($settings['expand'] & MENUPOLY_EXPAND_ACTIVE) {
        $plids = array_slice($trail_mlids, $mindepth - 1, $depth);
        $plids[] = $this->rootItem['mlid'];
        $plids = array_unique($plids);
        if (count($plids) > 1) {
          // Note: If $plids is empty, this condition will always be false.
          $q->condition('plid', $plids);
          return;
        }
      }
      $q->condition('plid', $this->rootItem['mlid']);
    }
  }

  function getRootMlid() {
    return $this->rootItem['mlid'];
  }

  function getRootDepth() {
    return $this->rootItem['depth'];
  }
}
