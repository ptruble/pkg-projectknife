<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pkprojects
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;


JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');


$list_order     = $this->escape($this->state->get('list.ordering', 'a.actual_due_date'));
$list_dir       = $this->escape($this->state->get('list.direction', 'asc'));
$list_order_sec = $this->escape($this->state->get('list.ordering_sec', 'a.progress'));
$list_dir_sec   = $this->escape($this->state->get('list.direction_sec', 'asc'));

if ($list_order == 'a.ordering' || $list_order == 'ordering') {
    $order_url = 'index.php?option=com_pkprojects&task=projects.saveOrderAjax&tmpl=component';
    JHtml::_('sortablelist.sortable', 'projectList', 'adminForm', strtolower($list_dir), $order_url);
}

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
        if (task == "projects.copy_dialog") {
            jQuery("#copyDialog").modal("show");
        }
        else {
            Joomla.submitform(task, document.getElementById("adminForm"));
        }
    };
');
?>
<form action="<?php echo JRoute::_('index.php?option=com_pkprojects&view=projects'); ?>" method="post" name="adminForm" id="adminForm">
    <?php
    // Sidebar
    if (!empty($this->sidebar)) {
        echo '<div id="j-sidebar-container" class="span2">' . $this->sidebar . '</div><div id="j-main-container" class="span10">';
    }
    else {
        echo '<div id="j-main-container">';
    }

    echo $this->loadTemplate('filter');

    if (empty($this->items)) :
        echo '<div class="alert alert-no-items">' . JText::_('JGLOBAL_NO_MATCHING_RESULTS') . '</div>';
    else :
        ?>
        <table class="table table-striped" id="projectList">
            <thead>
                <th width="1%" class="nowrap center hidden-phone">
                    <?php echo JHtml::_('grid.sort', '<i class="icon-menu-2"></i>', 'a.ordering', $list_dir, $list_order, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
                </th>
                <th width="1%" class="center">
                    <?php echo JHtml::_('grid.checkall'); ?>
                </th>
                <th width="1%" style="min-width:55px" class="nowrap center">
                    <?php echo JHtml::_('grid.sort', 'JSTATUS', 'a.published', $list_dir, $list_order); ?>
                </th>
                <th>
                    <?php echo JHtml::_('grid.sort', 'JGLOBAL_TITLE', 'a.title', $list_dir, $list_order); ?>
                </th>
                <th width="12%" class="nowrap hidden-phone">
                    <?php echo JHtml::_('grid.sort', 'PKGLOBAL_PROGRESS', 'a.progress', $list_dir, $list_order); ?>
                </th>
                <th width="12%" class="nowrap">
                    <?php echo JHtml::_('grid.sort', 'PKGLOBAL_START_DATE', 'a.start_date', $list_dir, $list_order); ?>
                </th>
                <th width="12%" class="nowrap">
                    <?php echo JHtml::_('grid.sort', 'PKGLOBAL_DUE_DATE', 'a.due_date', $list_dir, $list_order); ?>
                </th>
                <th width="15%" class="nowrap hidden-phone">
                    <?php echo JHtml::_('grid.sort',  'JGRID_HEADING_ACCESS', 'access_level', $list_dir, $list_order); ?>
                </th>
                <th width="5%" class="nowrap hidden-phone">
                    <?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ID', 'a.id', $list_dir, $list_order); ?>
                </th>
            </thead>
            <tbody>
                <?php
                echo $this->loadTemplate('items');
                ?>
            </tbody>
        </table>
        <?php
        endif;
        echo $this->pagination->getListFooter();
        echo $this->loadTemplate('copy');
        ?>
        <input type="hidden" name="task" value="" />
        <input type="hidden" name="boxchecked" value="0" />
        <input type="hidden" id="filter_order" name="filter_order" value="<?php echo $list_order; ?>" />
        <input type="hidden" id="filter_order_sec" name="filter_order_sec" value="<?php echo $list_order_sec; ?>" />
        <input type="hidden" id="filter_order_Dir" name="filter_order_Dir" value="<?php echo $list_dir; ?>" />
        <input type="hidden" id="filter_order_sec_Dir" name="filter_order_sec_Dir" value="<?php echo $list_dir_sec; ?>" />
        <?php echo JHtml::_('form.token'); ?>
    </div>
</form>