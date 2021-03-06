<?php

/**
 * Main Class for Daterange TV
 *
 * @package daterangetv
 * @subpackage class
 */
class DaterangeTV
{
    /**
     * A reference to the modX instance
     * @var modX $modx
     */
    public $modx;

    /**
     * The namespace
     * @var string $namespace
     */
    public $namespace = 'daterangetv';

    /**
     * The version
     * @var string $version
     */
    public $version = '1.2.2';

    /**
     * The class options
     * @var array $options
     */
    public $options = array();

    /**
     * DaterangeTV constructor
     *
     * @param modX $modx A reference to the modX instance.
     * @param array $options An array of options. Optional.
     */
    function __construct(modX &$modx, $options = array())
    {
        $this->modx = &$modx;

        $corePath = $this->getOption('core_path', $options, $this->modx->getOption('core_path') . 'components/daterangetv/');
        $assetsPath = $this->getOption('assets_path', $options, $this->modx->getOption('assets_path') . 'components/daterangetv/');
        $assetsUrl = $this->getOption('assets_url', $options, $this->modx->getOption('assets_url') . 'components/daterangetv/');

        // Load some default paths for easier management
        $this->options = array_merge(array(
            'namespace' => $this->namespace,
            'version' => $this->version,
            'assetsPath' => $assetsPath,
            'assetsUrl' => $assetsUrl,
            'cssUrl' => $assetsUrl . 'css/',
            'jsUrl' => $assetsUrl . 'js/',
            'imagesUrl' => $assetsUrl . 'images/',
            'corePath' => $corePath,
            'modelPath' => $corePath . 'model/',
            'vendorPath' => $corePath . 'vendor/',
            'chunksPath' => $corePath . 'elements/chunks/',
            'pagesPath' => $corePath . 'elements/pages/',
            'snippetsPath' => $corePath . 'elements/snippets/',
            'pluginsPath' => $corePath . 'elements/plugins/',
            'controllersPath' => $corePath . 'controllers/',
            'processorsPath' => $corePath . 'processors/',
            'templatesPath' => $corePath . 'templates/',
            'connectorUrl' => $assetsUrl . 'connector.php',
        ), $options);

        // set default options
        $this->options = array_merge($this->options, array());

        $this->modx->lexicon->load('daterangetv:default');
    }

    /**
     * Get a local configuration option or a namespaced system setting by key.
     *
     * @param string $key The option key to search for.
     * @param array $options An array of options that override local options.
     * @param mixed $default The default value returned if the option is not found locally or as a
     * namespaced system setting; by default this value is null.
     * @return mixed The option value or the default value specified.
     */
    public function getOption($key, $options = array(), $default = null)
    {
        $option = $default;
        if (!empty($key) && is_string($key)) {
            if ($options != null && array_key_exists($key, $options)) {
                $option = $options[$key];
            } elseif (array_key_exists($key, $this->options)) {
                $option = $this->options[$key];
            } elseif (array_key_exists("{$this->namespace}.{$key}", $this->modx->config)) {
                $option = $this->modx->getOption("{$this->namespace}.{$key}");
            }
        }
        return $option;
    }

    /**
     * Render supporting javascript to try and help it work with MIGX etc
     */
    public function includeScriptAssets()
    {
        $this->modx->regClientStartupScript($this->options['assetsUrl'] . 'mgr/js/daterangetv.js?v=v' . $this->version);
        $this->modx->regClientStartupScript($this->options['assetsUrl'] . 'mgr/js/daterangetv.renderer.js?v=v' . $this->version);
    }

    /**
     * Return a formatted daterange
     * @param $value
     * @param array $properties
     * @return string
     */
    public function getDaterange($value, $properties = array())
    {
        $format = $this->getOption('format', $properties);
        $format_trimmed = trim(str_replace('%', '', $format));
        $dayPos = false;
        $monthPos = false;
        
        if(preg_match('/(a|A|d|e|j|u|w)/', $format_trimmed, $dayformat, PREG_OFFSET_CAPTURE)===1){
            $dayPos = $dayformat[0][1];
        } else {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'A valid strftime DAY format has not been specified or an incorrect syntax was used.');
        }
        if(preg_match('/(b|B|h|m)/', $format_trimmed, $monthformat, PREG_OFFSET_CAPTURE)===1){
            $monthPos = $monthformat[0][1];
        } else {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'A valid strftime MONTH format has not been specified or an incorrect syntax was used.');
        }
        
        $daysBeforeMonths = $dayPos !== false && $monthPos !== false && ($monthPos > $dayPos) ? true : false ;
        $yearsFirst = $monthPos === 0 || $dayPos === 0 ? false : true ;

        $separator = $this->getOption('separator', $properties);
        $locale = $this->getOption('locale', $properties, false);
        
        $format = explode('|', $format);
        
        /*
        // @smg6511: not sure how this would result in a different array than above...
        if (count($format) != 3) {
            $format = explode('|', $this->getOption('format'));
        }
        */

        // get value
        $daterange = explode('||', $value);
        $start = (isset($daterange[0]) && $daterange[0] != '') ? intval(strtotime($daterange[0])) : 0;
        $end = (isset($daterange[1]) && $daterange[1] != '') ? intval(strtotime($daterange[1])) : 0;
        $result = '';

        // set locale
        if ($locale) {
            $currentLocale = setlocale(LC_ALL, 0);
            if (!setlocale(LC_ALL, $locale)) {
                $this->modx->log(modX::LOG_LEVEL_DEBUG, 'DaterangeTV: Locale ' . $locale . 'not valid!');
            }
        }

        // calculate daterange output
        if (intval($end) > intval($start)) {
            $start_day = date('d', $start);
            $start_month = date('m', $start);
            $start_year = date('Y', $start);

            $end_day = date('d', $end);
            $end_month = date('m', $end);
            $end_year = date('Y', $end);

            if ($start_year != $end_year) {
                $result  = trim(strftime($format[0] . $format[1] . $format[2], $start)) . $separator . trim(strftime($format[0] . $format[1] . $format[2], $end));
            } elseif ($start_month != $end_month) {
                if ($yearsFirst){
                    $result = trim(strftime($format[0] . $format[1] . $format[2], $start)) . $separator . trim(strftime($format[1] . $format[2], $end));
                } else {
                    $result = trim(strftime($format[0] . $format[1], $start)) . $separator . trim(strftime($format[0] . $format[1] . $format[2], $end));
                }
            } elseif ($start_day != $end_day) {
                if ($yearsFirst){
                    if ($daysBeforeMonths){
                        $result = trim(strftime($format[0] . $format[1], $start)) . $separator . trim(strftime($format[1] . $format[2], $end));
                    } else {
                        $result = trim(strftime($format[0] . $format[1] . $format[2], $start)) . $separator . trim(strftime($format[2], $end));
                    }
                } else {
                    if ($daysBeforeMonths){
                        $result = trim(strftime($format[0], $start)) . $separator . trim(strftime($format[0] . $format[1] . $format[2], $end));
                    } else {
                       $result = trim(strftime($format[0] . $format[1], $start)) . $separator . trim(strftime($format[1] . $format[2], $end)); 
                    }
                }
            } else {
                $result = trim(strftime($format[0] . $format[1] . $format[2], $start));
            }
        } else {
            $result = trim(strftime($format[0] . $format[1] . $format[2], $start));
        }

        // reset locale
        if (isset($currentLocale)) {
            if (!setlocale(LC_ALL, $currentLocale)) {
                $this->modx->log(modX::LOG_LEVEL_DEBUG, 'DaterangeTV: Old locale ' . $currentLocale . 'not valid!');
            }
        }
        return ($result);
    }
}
