<?php
defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;

class PlgEditorsXtdAaj extends CMSPlugin
{
    protected $autoloadLanguage = true;

    public function onDisplay($name, $asset = '', $author = ''): CMSObject|false
    {
        $user = Factory::getApplication()->getIdentity();

        if (!$user->authorise('core.create', 'com_content')
            && !$user->authorise('core.edit', 'com_content')) {
            return false;
        }

        $link = 'index.php?option=com_aaj&view=asinpicker&tmpl=component&'
              . Session::getFormToken() . '=1'
              . '&editor=' . urlencode($name);

        $button          = new CMSObject();
        $button->modal   = true;
        $button->link    = $link;
        $button->text    = Text::_('PLG_EDITORS_XTD_AAJ_BUTTON');
        $button->name    = 'aaj';
        $button->icon    = 'cart';
        $button->iconSVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" '
            . 'fill="currentColor" width="16" height="16">'
            . '<path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm-6-16v2h2l3.6 '
            . '7.59-1.35 2.45c-.16.28-.25.61-.25.96C5 16.1 6.1 17 7 17h12v-2H7.42c-.14 '
            . '0-.25-.11-.25-.25l.03-.12.9-1.63H19c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1 '
            . '1 0 0023.25 4H5.21l-.94-2H1zm16 16c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 '
            . '2-2-.9-2-2-2z"/></svg>';
        $button->options = '{"height":"520px","width":"850px"}';

        return $button;
    }
}
