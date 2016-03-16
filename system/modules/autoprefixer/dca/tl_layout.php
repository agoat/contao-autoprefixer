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
 
 

// palettes
$GLOBALS['TL_DCA']['tl_layout']['palettes']['default'] = str_replace('loadingOrder', 'loadingOrder,autoprefix', $GLOBALS['TL_DCA']['tl_layout']['palettes']['default']);
$GLOBALS['TL_DCA']['tl_layout']['palettes']['__selector__'][] = 'autoprefix';
$GLOBALS['TL_DCA']['tl_layout']['subpalettes']['autoprefix'] = 'browsers';

// fields
$GLOBALS['TL_DCA']['tl_layout']['fields']['autoprefix'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['autoprefix'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'eval'                    => array('submitOnChange'=>true, 'tl_class'=>'w50 m12'),
	'sql'                     => "char(1) NOT NULL default ''"
);
$GLOBALS['TL_DCA']['tl_layout']['fields']['browsers'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['browsers'],
	'exclude'                 => true,
	'default'                 => 'last 2 versions',
	'inputType'               => 'text',
	'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
	'sql'                     => "varchar(255) NOT NULL default ''"
);


	
	
class tl_layout_autoprefixer extends tl_layout
{

	public function checkBrowserQuery (DataContainer $dc)
	{
		// get block element
		$colBlocks = \ContentBlocksModel::findByPid($dc->activeRecord->pid, array('order'=>'sorting ASC'));
		
		if ($colBlocks === null)
		{
			return array();
		}
		
		$return = array();
		$strGroup = 'contentblocks';

		// generate array with elements 
		foreach ($colBlocks as $objBlock)
		{
			// group
			if ($objBlock->type == 'group')
			{
				$strGroup = $objBlock->title;
			}
			else
			{
				$return[$strGroup][$objBlock->alias] = $objBlock->title;
			}
		}

		return $return;
	}

}
