<?php


class menupoly_ServiceFactory {

  /**
   * @return menupoly_ModuleInfo
   *   Object with hook implementations
   */
  function get_info($cache) {
    return new menupoly_ModuleInfo();
  }

  /**
   * @return menupoly_BlockInfo
   *   Object with hook_block_info() and hook_block_view() implementations
   */
  function get_blocks($cache) {
    return new menupoly_BlockInfo();
  }

  /**
   * @return menupoly_AccessChecker
   *   Object which can check access for menu items.
   */
  function get_access($cache) {
    return new menupoly_AccessChecker();
  }

  /**
   * @return menupoly_Main
   *   Object with methods for the main public API.
   */
  function get_main($cache) {
    return new menupoly_Main($cache);
  }

  function get_settingsProcessor($cache) {
    return new menupoly_SettingsProcessor();
  }

  /**
   * @return menupoly_MenuTreeSource_Interface
   *   Object that can create menu trees
   */
  function call_1_menuTreeSource($cache, $type) {
    switch ($type) {
      case 'menu_links':
        $source = new menupoly_MenuTreeSource_MenuLinks($cache->access);
        $source->setTrailPaths($cache->trailPaths);
        return $source;
    }
  }

  /**
   * Paths to determine the "active trail".
   *
   * @return array
   *   Paths for the active trail, starting with the current page, and moving up
   *   towards the root (= front page).
   */
  function get_trailPaths($cache) {
    if (module_exists('crumbs')) {
      return array_keys(crumbs_get_trail());
    }
    else {
      $paths = array();
      $item = menu_get_item();
      if ($item['access']) {
        if ($item['tab_root']) {
          $paths[] = $item['tab_root'];
        }
        $paths[] = $item['href'];
      }
      return $paths;
    }
  }
}
