<?php
/**
 * @package      pkg_projectknife
 * @subpackage   plg_projectknife_projects
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2017 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('JPATH_PLATFORM') or die;


JLoader::register('JFormFieldRules', JPATH_SITE . '/libraries/joomla/form/fields/rules.php');
JLoader::register('JFormRulePKRules', __DIR__ . '/../rules/pkrules.php');


class JFormFieldPKRules extends JFormFieldRules
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 */
	protected $type = 'PKRules';


    protected function getActions($component, $section)
    {
        $actions    = array($component => JAccess::getActions($component, $section));
        $components = PKApplicationHelper::getComponents();

        if ($section == 'component') {
            $section = 'project';
        }

        foreach ($components AS $cmp)
        {
            if (!$cmp->enabled || $cmp->element == $component) {
                continue;
            }

            $tmp = JAccess::getActions($cmp->element, $section);

            if (is_array($tmp) && count($tmp)) {
                $actions[$cmp->element] = $tmp;
            }
        }

        return $actions;
    }


    /**
	 * Method to get the field input markup for Access Control Lists.
	 * Optionally can be associated with a specific component and section.
	 *
	 * @return  string  The field input markup.
	 */
	protected function getInput()
	{
	    $app = JFactory::getApplication();

		JHtml::_('bootstrap.tooltip');

        if (!$app->isSite()) {
            // Add Javascript for permission change
    		JHtml::_('script', 'system/permissions.js', false, true);

    		// Load JavaScript message titles
    		JText::script('ERROR');
    		JText::script('WARNING');
    		JText::script('NOTICE');
    		JText::script('MESSAGE');

    		// Add strings for JavaScript error translations.
    		JText::script('JLIB_JS_AJAX_ERROR_CONNECTION_ABORT');
    		JText::script('JLIB_JS_AJAX_ERROR_NO_CONTENT');
    		JText::script('JLIB_JS_AJAX_ERROR_OTHER');
    		JText::script('JLIB_JS_AJAX_ERROR_PARSE');
    		JText::script('JLIB_JS_AJAX_ERROR_TIMEOUT');

            $permission_onchange = 'onchange="sendPermissions.call(this, event)"';
        }
        else {
            $permission_onchange = '';
        }

		// Initialise some field attributes.
		$section    = $this->section;
		$assetField = $this->assetField;
		$component  = empty($this->component) ? 'root.1' : $this->component;

		// Current view is global config?
		$isGlobalConfig = $component === 'root.1';

		// Get the actions for the asset.
		$actions = $this->getActions($component, $section);

		// Iterate over the children and add to the actions.
		foreach ($this->element->children() as $el)
		{
			if ($el->getName() == 'action')
			{
				$actions[] = (object) array('name' => (string) $el['name'], 'title' => (string) $el['title'],
					'description' => (string) $el['description']);
			}
		}

		// Get the asset id.
		// Note that for global configuration, com_config injects asset_id = 1 into the form.
		$assetId       = $this->form->getValue($assetField);
		$newItem       = empty($assetId) && $isGlobalConfig === false && $section !== 'component';
		$parentAssetId = null;

		// If the asset id is empty (component or new item).
		if (empty($assetId))
		{
			// Get the component asset id as fallback.
			$db = JFactory::getDbo();
			$query = $db->getQuery(true)
				->select($db->quoteName('id'))
				->from($db->quoteName('#__assets'))
				->where($db->quoteName('name') . ' = ' . $db->quote($component));

			$db->setQuery($query);

			$assetId = (int) $db->loadResult();

			/**
			 * @to do: incorrect info
			 * When creating a new item (not saving) it uses the calculated permissions from the component (item <-> component <-> global config).
			 * But if we have a section too (item <-> section(s) <-> component <-> global config) this is not correct.
			 * Also, currently it uses the component permission, but should use the calculated permissions for achild of the component/section.
			 */
		}

		// If not in global config we need the parent_id asset to calculate permissions.
		if (!$isGlobalConfig) {
			// In this case we need to get the component rules too.
			$db = JFactory::getDbo();

			$query = $db->getQuery(true)
				->select($db->quoteName('parent_id'))
				->from($db->quoteName('#__assets'))
				->where($db->quoteName('id') . ' = ' . $assetId);

			$db->setQuery($query);

			$parentAssetId = (int) $db->loadResult();
		}

		// Full width format.

		// Get the rules for just this asset (non-recursive).
		$assetRules = JAccess::getAssetRules($assetId, false, false);

		// Get the available user groups.
		$groups = $this->getUserGroups();

		// Ajax request data.
		$ajaxUri = JRoute::_('index.php?option=com_config&task=config.store&format=json&' . JSession::getFormToken() . '=1');

		// Prepare output
		$html = array();

		// Description
		$html[] = '<p class="rule-desc">' . JText::_('JLIB_RULES_SETTINGS_DESC') . '</p>';

		// Begin tabs
		$html[] = '<div class="tabbable tabs-left" data-ajaxuri="' . $ajaxUri . '" id="permissions-sliders">';

		// Building tab nav
		$html[] = '<ul class="nav nav-tabs">';

		foreach ($groups as $group)
		{
			// Initial Active Tab
			$active = '';

			if ((int) $group->value === 1)
			{
				$active = 'active';
			}

			$html[] = '<li class="' . $active . '">';
			$html[] = '<a href="#permission-' . $group->value . '" data-toggle="tab">';
			$html[] = JLayoutHelper::render('joomla.html.treeprefix', array('level' => $group->level + 1)) . $group->text;
			$html[] = '</a>';
			$html[] = '</li>';
		}

		$html[] = '</ul>';

		$html[] = '<div class="tab-content">';

		// Start a row for each user group.
		foreach ($groups as $group)
		{
			// Initial Active Pane
			$active = '';

			if ((int) $group->value === 1)
			{
				$active = ' active';
			}

			$html[] = '<div class="tab-pane' . $active . '" id="permission-' . $group->value . '">';

			// Check if this group has super user permissions
			$isSuperUserGroup = JAccess::checkGroup($group->value, 'core.admin');

			foreach ($actions as $action_group => $action_options)
			{
			    if ($section == 'component' && $action_group == 'com_pkprojects') {
                    $action_group .= '_global';
                }

                $html[] = '<table class="table table-striped">';
    			$html[] = '<thead>';
    			$html[] = '<tr>';

    			$html[] = '<th class="actions" id="actions-th' . $group->value . '">';
    			$html[] = '<span class="acl-action">' . JText::_(strtoupper($action_group) . '_PERMISSIONS_HEADING') . '</span>';
    			$html[] = '</th>';

    			$html[] = '<th class="settings" id="settings-th' . $group->value . '">';
    			$html[] = '<span class="acl-action">' . JText::_('JLIB_RULES_SELECT_SETTING') . '</span>';
    			$html[] = '</th>';

    			$html[] = '<th id="aclactionth' . $group->value . '">';
    			$html[] = '<span class="acl-action">' . JText::_('JLIB_RULES_CALCULATED_SETTING') . '</span>';
    			$html[] = '</th>';

    			$html[] = '</tr>';
    			$html[] = '</thead>';
    			$html[] = '<tbody>';

			    foreach ($action_options AS $action)
                {
                    $html[] = '<tr>';
    				$html[] = '<td headers="actions-th' . $group->value . '" class="span4">';
    				$html[] = '<label for="' . $this->id . '_' . $action->name . '_' . $group->value . '" class="hasTooltip" title="'
    					. JHtml::_('tooltipText', $action->title, $action->description) . '">';
    				$html[] = JText::_($action->title);
    				$html[] = '</label>';
    				$html[] = '</td>';

    				$html[] = '<td headers="settings-th' . $group->value . '" class="span4">';

    				$html[] = '<select ' . $permission_onchange . ' data-chosen="true" class="input-small novalidate"'
    					. ' name="' . $this->name . '[' . $action->name . '][' . $group->value . ']"'
    					. ' id="' . $this->id . '_' . $action->name	. '_' . $group->value . '"'
    					. ' title="' . JText::sprintf('JLIB_RULES_SELECT_ALLOW_DENY_GROUP', JText::_($action->title), trim($group->text)) . '">';

    				/**
    				 * Possible values:
    				 * null = not set means inherited
    				 * false = denied
    				 * true = allowed
    				 */

    				// Get the actual setting for the action for this group.
    				$assetRule = $newItem === false ? $assetRules->allow($action->name, $group->value) : null;

    				// Build the dropdowns for the permissions sliders

    				// The parent group has "Not Set", all children can rightly "Inherit" from that.
    				$html[] = '<option value=""' . ($assetRule === null ? ' selected="selected"' : '') . '>'
    					. JText::_(empty($group->parent_id) && $isGlobalConfig ? 'JLIB_RULES_NOT_SET' : 'JLIB_RULES_INHERITED') . '</option>';
    				$html[] = '<option value="1"' . ($assetRule === true ? ' selected="selected"' : '') . '>' . JText::_('JLIB_RULES_ALLOWED')
    					. '</option>';
    				$html[] = '<option value="0"' . ($assetRule === false ? ' selected="selected"' : '') . '>' . JText::_('JLIB_RULES_DENIED')
    					. '</option>';

    				$html[] = '</select>&#160; ';

    				$html[] = '<span id="icon_' . $this->id . '_' . $action->name . '_' . $group->value . '"' . '></span>';
    				$html[] = '</td>';

    				// Build the Calculated Settings column.
    				$html[] = '<td headers="aclactionth' . $group->value . '"  class="span4">';

    				$result = array();

    				// Get the group, group parent id, and group global config recursive calculated permission for the chosen action.
    				$inheritedGroupRule            = JAccess::checkGroup((int) $group->value, $action->name, $assetId);
    				$inheritedGroupParentAssetRule = !empty($parentAssetId) ? JAccess::checkGroup($group->value, $action->name, $parentAssetId) : null;
    				$inheritedParentGroupRule      = !empty($group->parent_id) ? JAccess::checkGroup($group->parent_id, $action->name, $assetId) : null;

    				// Current group is a Super User group, so calculated setting is "Allowed (Super User)".
    				if ($isSuperUserGroup)
    				{
    					$result['class'] = 'label label-success';
    					$result['text'] = '<span class="icon-lock icon-white"></span>' . JText::_('JLIB_RULES_ALLOWED_ADMIN');
    				}
    				// Not super user.
    				else
    				{
    					// First get the real recursive calculated setting and add (Inherited) to it.

    					// If recursive calculated setting is "Denied" or null. Calculated permission is "Not Allowed (Inherited)".
    					if ($inheritedGroupRule === null || $inheritedGroupRule === false)
    					{
    						$result['class'] = 'label label-important';
    						$result['text']  = JText::_('JLIB_RULES_NOT_ALLOWED_INHERITED');
    					}
    					// If recursive calculated setting is "Allowed". Calculated permission is "Allowed (Inherited)".
    					else
    					{
    						$result['class'] = 'label label-success';
    						$result['text']  = JText::_('JLIB_RULES_ALLOWED_INHERITED');
    					}

    					// Second part: Overwrite the calculated permissions labels if there is an explicit permission in the current group.

    					/**
    					 * @to do: incorrect info
    					 * If a component as a permission that doesn't exists in global config (ex: frontend editing in com_modules) by default
    					 * we get "Not Allowed (Inherited)" when we should get "Not Allowed (Default)".
    					 */

    					// If there is an explicit permission "Not Allowed". Calculated permission is "Not Allowed".
    					if ($assetRule === false)
    					{
    						$result['class'] = 'label label-important';
    						$result['text']  = JText::_('JLIB_RULES_NOT_ALLOWED');
    					}
    					// If there is an explicit permission is "Allowed". Calculated permission is "Allowed".
    					elseif ($assetRule === true)
    					{
    						$result['class'] = 'label label-success';
    						$result['text']  = JText::_('JLIB_RULES_ALLOWED');
    					}

    					// Third part: Overwrite the calculated permissions labels for special cases.

    					// Global configuration with "Not Set" permission. Calculated permission is "Not Allowed (Default)".
    					if (empty($group->parent_id) && $isGlobalConfig === true && $assetRule === null)
    					{
    						$result['class'] = 'label label-important';
    						$result['text']  = JText::_('JLIB_RULES_NOT_ALLOWED_DEFAULT');
    					}
    					/**
    					 * Component/Item with explicit "Denied" permission at parent Asset (Category, Component or Global config) configuration.
    					 * Or some parent group has an explicit "Denied".
    					 * Calculated permission is "Not Allowed (Locked)".
    					 */
    					elseif ($inheritedGroupParentAssetRule === false || $inheritedParentGroupRule === false)
    					{
    						$result['class'] = 'label label-important';
    						$result['text']  = '<span class="icon-lock icon-white"></span>' . JText::_('JLIB_RULES_NOT_ALLOWED_LOCKED');
    					}
    				}

    				$html[] = '<span class="' . $result['class'] . '">' . $result['text'] . '</span>';
    				$html[] = '</td>';
    				$html[] = '</tr>';
                }

                $html[] = '</tbody>';
                $html[] = '</table>';

			}

			$html[] = '</div>';
		}

		$html[] = '</div></div>';
		$html[] = '<div class="clr"></div>';
		$html[] = '<div class="alert">';

		if ($section === 'component' || $section === null)
		{
			$html[] = JText::_('JLIB_RULES_SETTING_NOTES');
		}
		else
		{
			$html[] = JText::_('JLIB_RULES_SETTING_NOTES_ITEM');
		}

		$html[] = '</div>';

		return implode("\n", $html);
	}
}