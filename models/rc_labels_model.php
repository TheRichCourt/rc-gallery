<?php

/********************************************************************
Product		: RC Justified Gallery
Date		: 19/02/2016
Copyright	: Rich Court 2016
Contact		: http://www.therichcourt.com
Licence		: GNU General Public License
*********************************************************************/

// no direct access
defined( '_JEXEC' ) or die;

Class RCLabels {
    
    var $labels = array();
    var $labelsExist = false;
       
    public function getLabelsFromFile($folderPath) {
        
        $labelsFile = $folderPath . '/labels.txt';
        
        if (!file_exists($labelsFile)) {
            return false;
        } else {
            $this->labelsExist = true;
        }
        
        $labelsFileContent = file_get_contents($labelsFile);
        $rows = explode("\n", $labelsFileContent);
        
        foreach($rows as $row => $data) {
            $row_data = explode('|', $data);
            
            if (count($row_data) > 1) {
                $fileName = $row_data[0];
                $title = $row_data[1];
                $label = array(
                    'imageTitle' => $title,
                );
      
                $this->labels[$fileName] = $label;
            }
        }
    }
    
    public function getTitle($fileName) {
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