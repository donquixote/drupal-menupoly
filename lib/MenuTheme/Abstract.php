<?php


/**
 * A quick theme that does not use the theme() layer for menu html.
 */
abstract class menupoly_MenuTheme_Abstract implements menupoly_MenuTheme_Interface {

  function processSubmenuItems(&$items) {
    $i = 0;
    $n = count($items);
    foreach ($items as $k => $item) {
      @$items[$k]['class'] .= ($i % 2) ? 'odd' : 'even';
      if ($i == 0) {
        $items[$k]['class'] .= ' first';
      }
      if ($i == $n-1) {
        $items[$k]['class'] .= ' last';
      }
      ++$i;
    }
  }
}
