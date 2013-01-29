<?php


interface menupoly_MenuTreeSource_Interface {

  function setTrailPaths(array $paths);

  function build(array $settings);
}
