<?php
/**
 * Input Properties for Daterange TV
 *
 * @package daterangetv
 * @subpackage input properties
 */
$modx->lexicon->load('tv_widget', 'daterangetv:tvrenders');
$lang = $modx->lexicon->fetch('daterangetv.', true);

$modx->smarty->assign('daterangetv', $lang);

$corePath = $modx->getOption('daterangetv.core_path', null, $modx->getOption('core_path') . 'components/daterangetv/');
return $modx->smarty->fetch($corePath . 'tv/inputoptions/tpl/daterange.tpl');
?>