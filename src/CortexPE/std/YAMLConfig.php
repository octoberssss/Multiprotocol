<?php


namespace CortexPE\std;


final class YAMLConfig
{
    // todo: unset & unsetNested perhaps? maybe in the future when it's actually needed
    /** @var string */
    private $fileName;
    /** @var array */
    private $data;
    /** @var array */
    private $defaults;
    /** @var array */
    private $nestingCache = [];

    public function __construct(string $fileName, array $defaults = [], bool $correct = false)
    {
        $this->fileName = $fileName;
        $this->defaults = $defaults;
        $this->read();
        if ($correct) {
            $orig = $this->data;
            $this->data = array_replace_recursive($defaults, $this->data);
            if ($orig != $this->data) { // https://stackoverflow.com/a/5678990 we don't care about their order...
                $this->save();
            }
        }
    }

    public function read()
    {
        $this->nestingCache = [];
        if (file_exists($this->fileName)) {
            $this->data = yaml_parse_file($this->fileName);
        } else {
            $this->data = $this->defaults;
            $this->save();
        }
    }

    public function save()
    {
        yaml_emit_file($this->fileName, $this->data, YAML_UTF8_ENCODING, YAML_LN_BREAK);
    }

    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    public function unset(string $key): void
    {
        unset($this->data[$key]);
    }

    public function getNested(string $key, $default = null)
    {
        if (isset($this->nestingCache[$key])) return $this->nestingCache[$key];
        $value = null;
        foreach (explode(".", $key) as $k => $_key) {
            $src = $k > 0 ? $value : $this->data;
            if (!isset($src[$_key])) return $this->nestingCache[$key] = $default;
            $value = $src[$_key];
        }
        return $this->nestingCache[$key] = ($value ?? $default);
    }

    public function setNested(string $key, $value): void
    {
        $this->nestingCache[$key] = $value;
        $parts = explode(".", $key);
        $end = count($parts) - 1;
        $last = &$this->data;
        foreach ($parts as $k => $_key) {
            if ($k === $end) {
                $last[$_key] = $value;
            } else {
                if (!isset($last[$_key])) {
                    $last[$_key] = [];
                }
                $last = &$last[$_key];
            }
        }
        unset($last);
    }

    public function getAll(): array
    {
        return $this->data;
    }
}