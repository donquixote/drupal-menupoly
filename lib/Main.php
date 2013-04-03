<?php

class menupoly_Main {

  protected $services;

  /**
   * @param menupoly_ServiceCache
   *   Object which provides lazy-created service objects.
   */
  function __construct($services) {
    $this->services = $services;
  }

  /**
   * @param array $settings
   *   Array of settings that define a menu tree.
   *
   * @return array
   *   Drupal-renderable array.
   */
  function settingsToRenderArray($settings) {
    return array(
      '#theme' => 'menupoly',
      '#menupoly' => $settings,
    );
  }

  /**
   * @param array $settings
   *   Array of settings that define a menu tree.
   *
   * @return string
   *   Rendered HTML
   */
  function settingsToHtml($settings) {

    $tree = $this->settingsToMenuTree($settings);

    if (!empty($tree)) {
      // Render the tree.
      $menu_theme = $this->services->settingsProcessor->settingsResolveMenuTheme($settings);
      $html = $tree->render($menu_theme);
      return $html;
    }

  }

  /**
   * @param array $settings
   *   Array of settings that define a menu tree.
   *
   * @return menupoly_MenuTree
   *   Menu tree object, ready to render itself.
   */
  function settingsToMenuTree($settings) {

    $this->services->settingsProcessor->processSettings($settings);

    $source = $this->services->menuTreeSource('menu_links');
    if (!is_object($source)) {
      throw new Exception("Source must be an object.");
    }

    list($root_mlid, $items) = $source->build($settings);

    $this->services->accessChecker->itemsCheckAccess($items);

    // Build the MenuTree object.
    $tree = new menupoly_MenuTree($root_mlid);
    $tree->addItems($items);

    return $tree;
  }
}
