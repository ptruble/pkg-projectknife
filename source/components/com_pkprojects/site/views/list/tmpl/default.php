<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pkprojects
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die;


use Joomla\Registry\Registry;


JHtml::_('stylesheet', 'projectknife/lib_projectknife/core.css', false, true, false, false, true);
JHtml::_('stylesheet', 'projectknife/com_pkprojects/projects.css', false, true, false, false, true);
JHtml::_('behavior.multiselect');
JHtml::_('bootstrap.tooltip');
JHtml::_('formbehavior.chosen', 'select');
PKGrid::script();


JFactory::getDocument()->addScriptDeclaration('
    Joomla.orderTable = function()
    {
        jQuery("#filter_order").val(jQuery("#sortTable").val());
        jQuery("#filter_order_sec").val(jQuery("#sortTable_sec").val());

        jQuery("#filter_order_Dir").val(jQuery("#directionTable").val());
        jQuery("#filter_order_sec_Dir").val(jQuery("#directionTable_sec").val());

        Joomla.submitform("", document.getElementById("adminForm"));
    };

    Joomla.submitbutton = function(task)
    {
        if (task == "list.copy_dialog") {
            jQuery("#copyDialog").modal("show");
        }
        else {
            Joomla.submitform(task, document.getElementById("adminForm"));
        }
    };
');
?>
<div class="grid project-list">
    <?php if ($this->params->get('show_page_heading', 1) == '1') : ?>
        <h1><?php echo $this->escape($this->params->get('page_heading')); ?></h1>
    <?php endif; ?>

    <form name="adminForm" id="adminForm" action="<?php echo JRoute::_('index.php?option=com_pkprojects&view=list&Itemid=' . PKApplicationHelper::getMenuItemId('active')); ?>" method="post">
        <?php
        // Toolbar
        echo $this->toolbar;

        // Items

        echo '<div id="projectList">' . $this->loadTemplate('items') . '</div>';

        // Copy options
        echo $this->loadTemplate('copy');

        // Bottom pagination
        if ($this->pagination->get('pages.total') > 1) :
            ?>
            <div class="pagination center">
                <?php echo $this->pagination->getPagesLinks(); ?>
            </div>
            <p class="counter center"><?php echo $this->pagination->getPagesCounter(); ?></p>
        <?php endif; ?>
        <input type="hidden" id="boxchecked" name="boxchecked" value="0" />
        <input type="hidden" name="task" value="" />
        <input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->escape($this->state->get('list.ordering', 'a.actual_due_date')); ?>" />
        <input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->escape($this->state->get('list.direction', 'asc')); ?>" />
        <input type="hidden" name="filter_order_sec" id="filter_order_sec" value="<?php echo $this->escape($this->state->get('list.ordering_sec', 'a.progress')); ?>" />
        <input type="hidden" name="filter_order_sec_Dir" id="filter_order_sec_Dir" value="<?php echo $this->escape($this->state->get('list.direction_sec', 'asc')); ?>" />
        <?php
            echo JHtml::_('form.token');

            // Load additional hidden fields through plugins
            JPluginHelper::importPlugin('projectknife');
            $dispatcher = JEventDispatcher::getInstance();

            $filters = array();
            $dispatcher->trigger('onProjectknifeDisplayHiddenFilter', array('com_pkprojects.list', &$filters));

            echo implode("\n", $filters);
        ?>
    </form>
</div>