<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pkmilestones
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2017 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die;


require_once JPATH_COMPONENT . '/helpers/route.php';

$lang = JFactory::getLanguage();

if (!$lang->load('com_pkmilestones', JPATH_ADMINISTRATOR)) {
    $lang->load('com_pkmilestones', JPATH_ADMINISTRATOR . '/components/com_pkmilestones');
}

$controller = JControllerLegacy::getInstance('PKmilestones');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();