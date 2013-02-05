<?php


/**
 * A menu theme that renders the links without the ul / li wrapping.
 * This is quite a minimal implementation without all the bells and whistles.
 * We could inherit from menupoly_MenuTheme_Abstract to get some stuff for free,
 * but we don't.
 */
class menupoly_example_MenuTheme_FlatLinksWithSeparator implements menupoly_MenuTheme_Interface {

  protected $separator;

  function __construct($separator = '') {
    $this->separator = $separator;
  }

  function processSubmenuItems(&$items) {
    // We could use this for zebra striping and first / last classes,
    // but we don't.
  }

  function renderMenuItem($item, $options, $submenu_html) {
    // We would normally take care of active / active-trail stuff etc,
    // but we try to be simple here.
    // Also note that we totally ignore the submenu! We assume that this theme
    // is only ever used for one-level menu display.
    return l($item['title'], $item['href']);
  }

  function renderMenuItem__no_access($item, $options, $submenu_html) {
    // We could use this to render items where the user has no access,
    // greyed out or sth, but we don't.
    return NULL;
  }

  function renderMenuTree($items_html) {
    // Connect the links with a separator, and we are done.
    return implode($this->separator, $items_html);
  }
}
