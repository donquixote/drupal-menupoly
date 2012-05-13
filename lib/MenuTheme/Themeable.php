<?php


class menupoly_MenuTheme_Themeable extends menupoly_MenuTheme_Abstract {

  function renderMenuItem($item, $options, $submenu_html) {
    $link_html = theme('menu_item_link', $item);
    return theme('menu_item', $link_html, $item['has_children'], $submenu_html);
  }

  function renderMenuItem__no_access($item, $options, $submenu_html) {
    $link_html = $options['html'] ? $item['title'] : check_plain($item['title']);
    return theme('menu_item', $link_html, $item['has_children'], $submenu_html);
  }

  function renderMenuTree($items_html, $attributes) {
    $items_html = implode('', $items_html);
    return theme('menu_tree', $items_html);
  }
}
