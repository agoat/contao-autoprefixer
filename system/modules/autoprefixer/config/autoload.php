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

 

/**
 * Register the classes (only for FE rendering)
 */
ClassLoader::addClasses(array
(
	'Contao\AutoPrefixer' => 'system/modules/autoprefixer/classes/AutoPrefixer.php',
	'Contao\AutoCombiner' => 'system/modules/autoprefixer/classes/AutoCombiner.php'
));

