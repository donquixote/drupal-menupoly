<?php

/**
 * We have this stuff in a separate class so it can be unit-tested.
 */
class menupoly_SettingsProcessor {

  /**
   * @param array &$settings
   *   Array of settings that defines a menu tree.
   */
  function processSettings(&$settings) {

    // Resolve menu name suffix, to automatically pick the menu in the current
    // language. E.g. if the menu name is specified as 'primary-links', and a
    $settings['menu_name_original'] = $settings['menu_name'];
    $settings['menu_name'] = $this->settingsResolveMenuName($settings);

    $settings += array(
      'expand' => MENUPOLY_EXPAND_ACTIVE | MENUPOLY_EXPAND_EXPANDED,
    );
  }

  /**
   * @param array $settings
   *   Array of settings that define a menu tree.
   *
   * @return menupoly_MenuTheme_Interface
   *   Object that builds the HTML pieces for a given menu tree.
   */
  function settingsResolveMenuTheme($settings) {

    if (empty($settings['menu_theme'])) {
      return new menupoly_MenuTheme_Static();
    }
    else {
      return $settings['menu_theme'];
    }
  }

  /**
   * Dynamically determine the menu name.
   */
  function settingsResolveMenuName($settings) {
    if (empty($settings['menu_name'])) {
      throw new Exception("No menu name set in settings array.");
    }
    if (!empty($settings['i18n_menu_name_suffix'])) {
      $i18n_menu_name = $settings['menu_name'] . '-'. $GLOBALS['language']->language;
      if ($this->_menuExists($i18n_menu_name)) {
        return $i18n_menu_name;
      }
    }
    if ($this->_menuExists($settings['menu_name'])) {
      return $settings['menu_name'];
    }
    else {
      throw new Exception("There is no menu with menu_name = '$settings[menu_name]'");
    }
  }

  protected function _menuExists($menu_name) {
    return FALSE !== menu_load($menu_name);
  }
}
