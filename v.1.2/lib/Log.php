<?php
/**
 * @todo Ability to set output format as a parameter, i.e. [%ID] %LINENO - %MSG
 */
class sspmod_kvalidate_Log
{
    protected $data;

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        return null;
    }

    public function __isset($name) {
        return isset($this->data[$name]);
    }

    public function __unset($name) {
        unset($this->data[$name]);
    }

    public function __toString()
    {
        if (!isset($this->data['msg']) && !isset($this->data['id'])) {
            return print_r($this->_data, true);
        }

        return '[' . $this->data['id'] . '] ' . $this->data['msg'];
    }
}
