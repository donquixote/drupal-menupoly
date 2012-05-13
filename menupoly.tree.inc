<?php


function menupoly_load_tree(array $config, array $seeds) {

  $source = new menupoly_MenuItemSource_MenuLinks($config['menu_name']);
  if (!empty($config['root_mlid'])) {
    $source->setRootItem($config['root_mlid']);
  }
  else if (!empty($config['root_path'])) {
    $source->setRootPath($config['root_path']);
  }

  $loader = new menupoly_MenuTreeLoader($source, $config['mindepth'], $config['maxdepth'], $config['expand']);
  $loader->addSeed($config['root_mlid']);
  foreach ($seeds as $path) {
    if ($loader->addSeedPath($path)) {
      break;
    }
  }

  $items = $loader->getItems();
  menupoly_items_check_access($items);
  $submenus = $loader->getSubmenus();
  $new_root_mlid = $loader->getRootKey();
  $tree = new menupoly_MenuTree($submenus, $items);
  return $tree;
}


/**
 * This function is much more innocent than _menu_tree_check_access().
 * It does not actually remove anything,
 * it just sets the $item['access'] to either TRUE or FALSE.
 */
function menupoly_items_check_access(&$items) {

  // Collect node links
  $node_links = array();
  foreach ($items as $mlid => &$item) {
    if ($item['router_path'] === 'node/%') {
      $nid = substr($item['link_path'], 5);
      if (is_numeric($nid)) {
        $node_links[(int)$nid][$mlid] = $mlid;
      }
    }
  }

  // Adapted from menu_tree_check_access()
  if (!empty($node_links)) {
    $nids = array_keys($node_links);
    $select = db_select('node', 'n');
    $select->addField('n', 'nid');
    $select->condition('n.status', 1);
    $select->condition('n.nid', $nids, 'IN');
    $select->addTag('node_access');
    $nids = $select->execute()->fetchCol();
    foreach ($nids as $nid) {
      foreach ($node_links[$nid] as $mlid) {
        $items[$mlid]['access'] = TRUE;
      }
    }
  }

  // Process all items that are not node links.
  foreach ($items as $mlid => &$item) {
    _menu_link_translate($item);
  }
}
