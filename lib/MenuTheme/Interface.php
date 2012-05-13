<?php


interface menupoly_MenuTheme_Interface {

  function renderMenuItem($item, $options, $submenu_html);

  function renderMenuItem__no_access($item, $options, $submenu_html);

  function renderMenuTree($items_html);
}
