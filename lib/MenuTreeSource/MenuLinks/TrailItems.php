<?php


class menupoly_MenuTreeSource_MenuLinks_TrailItems {

  protected $trailPaths;

  function __construct($trail_paths) {
    $this->trailPaths = $trail_paths;
  }

  function mlids($root_condition) {
    $deep_trail_item = $this->deepest($root_condition);
    if (empty($deep_trail_item)) {
      return array(0);
    }
    $mlids = array(0);
    for ($i = 1; !empty($deep_trail_item['p' . $i]); ++$i) {
      $mlids[] = $deep_trail_item['p' . $i];
    }
    return $mlids;
  }

  /**
   * Of all menu links whose path is in the current active trail, this method
   * returns the one where
   * - the path is th deepest within the active trail paths
   * - the depth of the menu link is highest
   */
  function deepest($root_condition) {
    $mlid = $this->fetchDeepest($root_condition, 'mlid');
    $link = $this->fetchLink($mlid);
    return $link;
  }

  /**
   * Same as above, but returns the parent of this item.
   */
  function parentOfDeepest($root_condition) {
    $mlid = $this->fetchDeepest($root_condition, 'plid');
    $link = $this->fetchLink($mlid);
    return $link;
  }

  /**
   * Of all menu links whose path is in the current active trail, this method
   * returns the one where
   * - the path is th deepest within the active trail paths
   * - the depth is exactly as $depth.
   */
  function withDepth($root_condition, $depth) {
    $mlid = $this->fetchDeepest($root_condition, 'mlid', $depth);
    return $this->fetchLink($mlid);
  }

  protected function fetchDeepest($root_condition, $field, $depth = NULL) {
    $q = db_select('menu_links', 'ml')->fields('ml', array('link_path', $field));
    $root_condition->apply($q);
    $q->condition('link_path', $this->trailPaths);
    if (isset($depth)) {
      $q->condition('depth', $depth);
    }
    else {
      $q->orderBy('depth', 'ASC');
    }
    $sorted = array();
    foreach ($q->execute()->fetchAll(PDO::FETCH_ASSOC) as $item) {
      $sorted[$item['link_path']] = $item[$field];
    }
    foreach (array_reverse($this->trailPaths) as $path) {
      if (isset($sorted[$path])) {
        return $sorted[$path];
      }
    }
  }

  protected function fetchLink($mlid) {
    if (!empty($mlid)) {
      $q = db_select('menu_links', 'ml')->fields('ml');
      $q->condition('mlid', $mlid);
      $link = $q->execute()->fetchAssoc();
    }
    return isset($link) ? $link : NULL;
  }
}
