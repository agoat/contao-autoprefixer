<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (C) 2005-2016 Leo Feyer
 *
 * @package  	 AutoPrefixer
 * @author   	 Arne Stappen
 * @license  	 LGPL-3.0+ 
 * @copyright	 Arne Stappen 2016
 */

namespace Contao;



/**
 * Combines .css files into one single file and add vendor prefixes
 *
 * Usage:
 *
 *     $combiner = new AutoCombiner();
 *
 *     $combiner->add('css/style.css');
 *     $combiner->add('css/fonts.scss');
 *     $combiner->add('css/print.less');
 *
 *     echo $combiner->getCombinedFile();
 *
 */
class AutoCombiner extends \Combiner
{

	/**
	 * Generate the combined file and add vendor prefixes with autoprefixer class
	 *
	 * @param string $strUrl An optional URL to prepend
	 *
	 * @return string The path to the combined file
	 */
	public function getCombinedFile($strUrl=null)
	{
		// PageModel needed
		if (!isset($GLOBALS['objPage']))
		{
			return $strBuffer;
		}
		
		$objLayout = $GLOBALS['objPage']->getRelated('layout');
		
		// prepare browser list
		$browsers = explode(',', $objLayout->browsers);
		array_walk($browsers, function (&$value) { $value = trim(html_entity_decode($value)); });

		
		// Include library
		require_once TL_ROOT . '/vendor/vladkens/autoprefixer-php/lib/Autoprefixer.php';
		require_once TL_ROOT . '/vendor/vladkens/autoprefixer-php/lib/AutoprefixerException.php';



		if ($strUrl === null)
		{
			$strUrl = TL_ASSETS_URL;
		}

		$strTarget = substr($this->strMode, 1);
		$strKey = substr(md5($this->strKey), 0, 12);

		// Do not combine the files in debug mode (see #6450)
		if (\Config::get('debugMode'))
		{
			$return = array();

			foreach ($this->arrFiles as $arrFile)
			{
				$content = file_get_contents(TL_ROOT . '/' . $arrFile['name']);

				// Compile SCSS/LESS files into temporary files
				if ($arrFile['extension'] == self::SCSS || $arrFile['extension'] == self::LESS)
				{
					$strPath = 'assets/' . $strTarget . '/' . str_replace('/', '_', $arrFile['name']) . $this->strMode;

					$objFile = new \File($strPath, true);
					$objFile->write($this->autoprefixer($this->handleScssLess($content, $arrFile), $browsers));
					$objFile->close();

					$return[] = $strPath;
				}
				else
				{
					$strPath = 'assets/' . $strTarget . '/' . str_replace('/', '_', $arrFile['name']) . $this->strMode;

					$objFile = new \File($strPath, true);
					$objFile->write($this->autoprefixer($this->handleCss($content, $arrFile), $browsers));
					$objFile->close();

					$return[] = $strPath;
				}
			}

			return implode('"><link rel="stylesheet" href="', $return);
		}

		// Load the existing file
		if (file_exists(TL_ROOT . '/assets/' . $strTarget . '/' . $strKey . $this->strMode))
		{
			return $strUrl . 'assets/' . $strTarget . '/' . $strKey . $this->strMode;
		}

		// Create the file
		$objFile = new \File('assets/' . $strTarget . '/' . $strKey . $this->strMode, false);
		$objFile->truncate();
		$strFile = '';

		foreach ($this->arrFiles as $arrFile)
		{
			$content = file_get_contents(TL_ROOT . '/' . $arrFile['name']);

			// HOOK: modify the file content
			if (isset($GLOBALS['TL_HOOKS']['getCombinedFile']) && is_array($GLOBALS['TL_HOOKS']['getCombinedFile']))
			{
				foreach ($GLOBALS['TL_HOOKS']['getCombinedFile'] as $callback)
				{
					$this->import($callback[0]);
					$content = $this->{$callback[0]}->{$callback[1]}($content, $strKey, $this->strMode, $arrFile);
				}
			}

			if ($arrFile['extension'] == self::CSS)
			{
				$content = $this->handleCss($content, $arrFile);
			}
			elseif ($arrFile['extension'] == self::SCSS || $arrFile['extension'] == self::LESS)
			{
				$content = $this->handleScssLess($content, $arrFile);
			}

			$strFile .= $content;
		}



		// add vendor prefixes in the combined file
		$objFile->write($this->autoprefixer($strFile, $browsers));
				
		$objFile->close();

		// Create a gzipped version
		if (\Config::get('gzipScripts') && function_exists('gzencode'))
		{
			\File::putContent('assets/' . $strTarget . '/' . $strKey . $this->strMode . '.gz', gzencode(file_get_contents(TL_ROOT . '/assets/' . $strTarget . '/' . $strKey . $this->strMode), 9));
		}

		return $strUrl . 'assets/' . $strTarget . '/' . $strKey . $this->strMode;
	}

	/**
	 * Use the autoprefixer class
	 *
	 * @param string $content CSS content
	 * @param string $browsers Browsers query list
	 *
	 * @return string CSS with autoprefixes
	 *
	 * Catch and log errors from autoprefixer class
	 */	
	function autoprefixer($content, $browsers)
	{
		// Initialize the autoprefixer
		$autoprefixer = new \AutoPrefixer($browsers);
		
		// catch errors
		try {
			$content = $autoprefixer->compile($content);
		} catch (\AutoprefixerException $error) {
			$error = substr($error->getMessage(), 11);
		} catch (\Exception $error) {
			$error = $error->getMessage();
		}
		
		if ($error != '')
		{
			// make the error message a bit nicer
			$error = str_replace('undefined', 'Error in browser query', $error);
			$error = str_replace('<css input>', 'Error on line', $error);
			$error = str_replace(':', ' ', $error);
		
			// write the error into the contao log
			\System::log('AutoPrefixer couldn´t compile a css file: ' . $error, __METHOD__, TL_ERROR);
		}

		return $content;
	}

}
