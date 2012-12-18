<?php
namespace WAYF;

class TempleteException extends \Exception {}

class Template
{
    private $_templatepath = '';
    private $_template = null;
    private $_defaultheaders = true;

    public $data = array();

    public function __construct()
    {
        $this->_templatepath = ROOT . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
    }

    public function setTemplate($template, $defaultheaders = true) 
    {
        $this->_template = $template;
        
        if (is_bool($defaultheaders)) {
            $this->_defaultheaders = $defaultheaders;
        }    
        return $this;
    }

    public function setData(array $data) {
        $this->data = $data;
        return $this;
    }

    public function __set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function render($exit = true) {
        // Extract the data into the local namespace
        extract($this->data);

        // Include the template file
        $templatefile = $this->_templatepath . $this->_template.'.tpl.php'; 

        if (file_exists($templatefile)) {
            // Include default header
            if ($this->_defaultheaders) {
                include $this->_templatepath . 'header.tpl.php';
            }

            // Include template file
            include $templatefile;

            // Include default footer
            if ($this->_defaultheaders) {
                include $this->_templatepath . 'footer.tpl.php';
            }
        } else {
            $this->showErrorPage();
        }

        if ($exit) {
            exit();
        }
    }

    private function showErrorPage()
    {
        echo <<< ERRORPAGE
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>NEWCA - Consent Administartion by WAYF</title>
        <meta charset="utf-8" />
        <meta name="application-name" content="NEWCA" />
        <meta http-equiv="Cache-Control" content="no-cache" />
        <meta http-equiv="expires" content="Mon, 22 Jul 2002 11:12:01 GMT" />
        <meta http-equiv="pragma" content="no-cache" />
        <meta name="robots" content="none" />
    </head>
    <body>
        <h1>Error</h1>
        <h2>Undefined templete requested</h2>
        <p>The template "{$this->_template}" was requested, but does not exists.</p>
        <p>Please contact the system administrator if this problem continues to exists.</p>
        <hr />
        <address>
            WAYF - Where Are You From<br />
            H. C. Andersens Boulevard 2<br />
            DK-1553 København V<br />
            Web: <a href="http://www.wayf.dk">www.wayf.dk</a><br />
            E-mail: <a href="mailto:sekretariat@wayf.dk">sekretariat@wayf.dk</a>
        </address>
    </body>
</html>
ERRORPAGE;
    }
}
