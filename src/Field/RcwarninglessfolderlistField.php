<?php

namespace RichCourt\Plugin\Content\RcGallery\Field;

defined('_JEXEC') or die;

use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;
use Joomla\CMS\Form\Field\FolderlistField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

class RcwarninglessfolderlistField extends FolderlistField
{
    protected $type = 'Rcwarninglessfolderlist';

    /**
     * Same as parent, but doesn't warn if the folder's missing.
     *
     * @return array
     */
    protected function getOptions()
    {
        $options = [];

        $path = $this->directory;

        if (!is_dir($path)) {
            $path = JPATH_ROOT . '/' . $path;
        }

        $path = Path::clean($path);

        if (!$this->hideNone) {
            $options[] = HTMLHelper::_('select.option', '-1', Text::alt('JOPTION_DO_NOT_USE', preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname)));
        }

        if (!$this->hideDefault) {
            $options[] = HTMLHelper::_('select.option', '', Text::alt('JOPTION_USE_DEFAULT', preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname)));
        }

        $folders = file_exists($path)
            ? Folder::folders($path, $this->filter, $this->recursive, true)
            : [];

        if (is_array($folders)) {
            foreach ($folders as $folder) {
                if ($this->exclude) {
                    if (preg_match(chr(1) . $this->exclude . chr(1), $folder)) {
                        continue;
                    }
                }

                $folder = trim(str_replace($path, '', $folder), '/');

                $options[] = HTMLHelper::_('select.option', $folder, $folder);
            }
        }

        return $options;
    }
}
