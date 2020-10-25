<?php
if (!class_exists('emerchant')) {
	require_once MODX_BASE_PATH."assets/plugins/emerchant/classes/class.emerchant.php";		
}

$em = new emerchant($modx, $params);	
if (!$ownerTPL) $ownerTPL='@CODE: [+inner+]';
if (!isset($noneTPL)) $noneTPL='@CODE: ';
if ($prepare) $em->issetRowPrepare($prepare);
echo $em->makeCart($tpl,$ownerTPL,$noneTPL);
return;
