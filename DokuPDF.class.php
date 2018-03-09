<?php
/**
 * Wrapper around the mpdf library class
 *
 * This class overrides some functions to make mpdf make use of DokuWiki'
 * standard tools instead of its own.
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
global $conf;
if(!defined('_MPDF_TEMP_PATH')) define('_MPDF_TEMP_PATH', $conf['tmpdir'] . '/dwpdf/' . rand(1, 1000) . '/');

require_once __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/DokuImageProcessorDecorator.class.php';

/**
 * Class DokuPDF
 * Some DokuWiki specific extentions
 */
class DokuPDF extends \Mpdf\Mpdf {

    /**
     * DokuPDF constructor.
     *
     * @param string $pagesize
     * @param string $orientation
     * @param int $fontsize
     */
    function __construct($pagesize = 'A4', $orientation = 'portrait', $fontsize = 12) {
        global $conf;

        io_mkdir_p(_MPDF_TEMP_PATH);

        $format = $pagesize;
        if($orientation == 'landscape') {
            $format .= '-L';
        }

        switch($conf['lang']) {
            case 'zh':
            case 'zh-tw':
            case 'ja':
            case 'ko':
                $mode = '+aCJK';
                break;
            default:
                $mode = 'UTF-8-s';

        }

        // we're always UTF-8
        $defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];
        $customFont = array(
            'wqymicrohei' => array(
                'R' => 'wqy-microhei.ttc',
                'B' => 'wqy-microhei.ttc',
                'I' => 'wqy-microhei.ttc',
                'TTCfontID' => array(
                    'R' => 1,
                    'B' => 1,
                    'I' => 1
                )
            )
        );
        $config = array(
            'mode' => $mode,
            'format' => $format,
            'fontsize' => $fontsize,
            'ImageProcessorClass' => DokuImageProcessorDecorator::class,
            'tempDir' => _MPDF_TEMP_PATH, //$conf['tmpdir'] . '/tmp/dwpdf'
            'fontdata' => $fontData + $customFont,
        );
        parent::__construct($config);

        $this->autoScriptToLang = true;
        $this->baseScript = 35; // \Mpdf\Ucdn::SCRIPT_HAN 以中文为基准，中文不会被执行autoScriptToLang
        $this->autoVietnamese = true;
        $this->autoArabic = true;
        $this->autoLangToFont = true;

        $this->ignore_invalid_utf8 = true;
        $this->tabSpaces = 4;
    }

    /**
     * Cleanup temp dir
     */
    function __destruct() {
        io_rmdir(_MPDF_TEMP_PATH, true);
    }

    /**
     * Decode all paths, since DokuWiki uses XHTML compliant URLs
     *
     * @param string $path
     * @param string $basepath
     */
    function GetFullPath(&$path, $basepath = '') {
        $path = htmlspecialchars_decode($path);
        parent::GetFullPath($path, $basepath);
    }
}
