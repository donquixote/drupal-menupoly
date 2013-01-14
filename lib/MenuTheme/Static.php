<?php


/**
 * A quick theme that does not use the theme() layer for menu html.
 */
class menupoly_MenuTheme_Static extends menupoly_MenuTheme_Abstract {

  function renderMenuItem($item, $options, $submenu_html) {
    $link_html = l($item['title'], $item['href'], $options);
    $attr = $this->_itemAttributes($item, $options, $submenu_html);
    return $attr->renderTag('li', $link_html . $submenu_html);
  }

  function renderMenuItem__no_access($item, $options, $submenu_html) {
    $link_html = $options['html'] ? $item['title'] : check_plain($item['title']);
    $attr = $this->_itemAttributes($item, $options, $submenu_html);
    $attr->addClass('no-access');
    return $attr->renderTag('li', $link_html . $submenu_html);
  }

  protected function _itemAttributes($item, $options, $submenu_html) {
    $attr = htmltag_tag_attributes();
    if (@$item['class']) {
      $attr->addClass($item['class']);
    }
    $attr->addClass($submenu_html ? 'expanded' : ($item['has_children'] ? 'collapsed' : 'leaf'));
    $attr->addClassesIf(array(
      'active' => @$item['active'],
      'active-trail' => @$item['active-trail'],
    ));
    return $attr;
  }

  function renderMenuTree($items_html) {
    $items_html = implode('', $items_html);
    $attributes = htmltag_class_attribute('menu');
    return $attributes->renderTag('ul', $items_html);
  }
}
