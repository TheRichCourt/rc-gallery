<?php

defined('_JEXEC') or die;

class RCLabels
{
    /** @var array */
    private $labels = [];

    /** @var bool */
    private $labelsExist = false;

    /**
     * Get image labels from user created file
     *
     * @param string $folderPath
     * @return bool|void
     */
    public function getLabelsFromFile($folderPath)
    {
        $labelsFile = $folderPath . '/labels.txt';

        if (!file_exists($labelsFile)) {
            return false;
        } else {
            $this->labelsExist = true;
        }

        $labelsFileContent = file_get_contents($labelsFile);
        $rows = explode("\n", $labelsFileContent);

        foreach ($rows as $row => $data) {
            $row_data = explode('|', $data);

            if (count($row_data) > 1) {
                $fileName = $row_data[0];
                $title = $row_data[1];
                $label = [
                    'imageTitle' => $title,
                ];

                $this->labels[$fileName] = $label;
            }
        }
    }

    /**
     * Extract the image title from the file
     *
     * @param string $fileName
     * @return void
     */
    public function getTitle($fileName)
    {
        if (!$this->labelsExist) {
            return false;
        } else {
            if (empty($this->labels[$fileName]['imageTitle'])) {
                return false;
            } else {
                return $this->labels[$fileName]['imageTitle'];
            }
        }
    }
}
