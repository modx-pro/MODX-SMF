<?php
/** @var array $scriptProperties */
/** @var SMF $SMF */
if (!$SMF = $modx->getService('smf', 'SMF', $modx->getOption('smf_core_path', null, $modx->getOption('core_path') . 'components/smf/') . 'model/smf/', $scriptProperties)) {
	return 'Could not load SMF class!';
}

