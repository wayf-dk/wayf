<?php
/**
 * JAKOB
 *
 * @category   WAYF
 * @package    NEWCA
 * @subpackage Translation
 * @author     Jacob Christiansen <jach@wayf.dk>
 * @copyright  Copyright (c) 2011 Jacob Christiansen, WAYF (http://www.wayf.dk)
 * @license    http://www.opensource.org/licenses/mit-license.php MIT License
 * @version    $Id$
 * @link       $URL$
 */

/**
 * @namespace
 */
namespace WAYF;

/**
 * Translation class
 *
 * Class for holding and processing translation
 * 
 * @author Jacob Christiansen <jach@wayf.dk>
 */
class Translation
{
    protected $data = array();
    protected $lang = 'en';

    public function __construct($lang = 'en')
    {
        // Include translation
        include ROOT . 'translation' . DIRECTORY_SEPARATOR . 'main.php';
        $this->data = $translation;

        $this->lang = $lang;
    }

    /**
     * Translate parsed 
     */
    public function t($term)
    {
        if (!isset($this->data[$term][$this->lang])) {
            return 'NOT TRANSLATED';
        }
        return $this->data[$term][$this->lang];
    }

    public function setLanguage($lang)
    {
        $this->lang = $lang;
    }
}
