<?php

defined('JPATH_PLATFORM') or die;

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.path');

JFormHelper::loadFieldClass('folderlist');

class JFormFieldRCWarninglessFolderList extends JFormFieldFolderList
{
    protected $type = 'RCWarninglessFolderList';

    /**
     * Same as parent, but doesn't warn if the folder's missing
     *
     * @return void
     */
    protected function getOptions()
    {
        $options = array();

        $path = $this->directory;

        if (!is_dir($path)) {
            $path = JPATH_ROOT . '/' . $path;
        }

        $path = JPath::clean($path);

        // Prepend some default options based on field attributes.
        if (!$this->hideNone) {
            $options[] = JHtml::_('select.option', '-1', JText::alt('JOPTION_DO_NOT_USE', preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname)));
        }

        if (!$this->hideDefault) {
            $options[] = JHtml::_('select.option', '', JText::alt('JOPTION_USE_DEFAULT', preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname)));
        }

        // ******* Only bit that differs from parent - parent doesn't check if the directory exists, and therefore throws a warning if it doesn't
        // Get a list of folders in the search path with the given filter.
        $folders = file_exists($path)
            ? JFolder::folders($path, $this->filter, $this->recursive, true)
            : []
        ;

        // Build the options list from the list of folders.
        if (is_array($folders)) {
            foreach ($folders as $folder) {
                // Check to see if the file is in the exclude mask.
                if ($this->exclude) {
                    if (preg_match(chr(1) . $this->exclude . chr(1), $folder)) {
                        continue;
                    }
                }

                // Remove the root part and the leading /
                $folder = trim(str_replace($path, '', $folder), '/');

                $options[] = JHtml::_('select.option', $folder, $folder);
            }
        }

        // Merge any additional options in the XML definition.
        //$options = array_merge(parent::getOptions(), $options);

        return $options;
    }
}
