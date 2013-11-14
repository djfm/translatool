<?php
/*
* 2007-2012 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2012 PrestaShop SA
*  @version  Release: $Revision: 7095 $
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_'))
	exit;

require_once __DIR__ . '/github.php';
require_once __DIR__ . '/lib/PHPExcel.php';

class Translatool extends Module
{
	
	public function __construct()
	{
		$this->name = 'translatool';
		$this->version = '0.1';
		$this->author = 'djfm@PrestaShop';

		parent::__construct();

		$this->displayName = 'Translatool';
		$this->description = 'Export / Import PrestaShop translations in various formats.';
		$this->confirmUninstall = 'Are you sure you want to delete this module?';
	}

	public function install()
	{
		if (!parent::install())
			return false;
		return true;
	}

	public function uninstall()
	{
		if (!parent::uninstall())
			return false;
		return true;
	}

	public function abspath($relpath)
	{
		return dirname(__FILE__) . "/$relpath";
	}


	public function getStringsFromTC($type, $varname)
	{
		$tc = new AdminTranslationsController();
		
		
		$_GET['theme'] = _THEME_NAME_;
		
		$tc->setTypeSelected($type);
		$tc->getInformations();
		
		$method = 'initForm' . ucfirst($type);


		$tc->$method();
		return $tc->tpl_view_vars[$varname];
	}

	public function getTabsKeys()
	{
		$sql = "SELECT t.class_name, tl.name FROM ps_tab t 
				INNER JOIN ps_tab_lang tl ON t.id_tab = tl.id_tab 
				INNER JOIN ps_lang l ON l.id_lang = tl.id_lang 
				WHERE l.iso_code = 'en' 
				ORDER BY tl.name";

		$sql = str_replace('ps_',_DB_PREFIX_,$sql);
		$arr = Db::getInstance()->query($sql)->fetchAll();
		
		$res = array();
		
		foreach($arr as $row)
		{
			$res[] = array('language' 			=> 'en',
							'section'  			=> '0 - Tabs',
							'storage file path' => "/translations/en/tabs.php",
							'array name' 		=> '$_TABS',
							'array key' 		=> $row[0],
							'english string' 	=> $row[1]);
		}
		
		return $res;
	}

	public function getFrontKeys()
	{
		$arr = $this->getStringsFromTC('front','tabsArray');

		$res = array();
		
		$theme = _THEME_NAME_ == 'prestashop' ? 'default' : _THEME_NAME_;

		foreach($arr as $template_name => $strings)
		{
			foreach($strings as $string => $unused)
			{
				$key = $template_name .'_' . md5($string);
				$res[] = array('language' 			=> 'en',
								'section'  			=> '1 - Front-Office',
								'storage file path' => "/themes/$theme/lang/en.php",
								'array name' 		=> '$_LANG',
								'array key' 		=> $key,
								'english string' 	=> $string,
								'group' 			=> $template_name);
			}
		}
		
		return $res;
	}
	
	public function getFrontKeys14()
	{
		$res = array();
		$templates = array_merge(scandir(_PS_THEME_DIR_), scandir(_PS_ALL_THEMES_DIR_));
		
		$found = array();
		
		foreach ($templates AS $template)
		{
			if (preg_match('/^(.*).tpl$/', $template) AND (file_exists($tpl = _PS_THEME_DIR_.$template) OR file_exists($tpl = _PS_ALL_THEMES_DIR_.$template)))
			{
				$template2 = substr(basename($template), 0, -4);
				$newLang = array();
				$fd = fopen($tpl, 'r');
				$content = fread($fd, filesize($tpl));

				/* Search language tags (eg {l s='to translate'}) */
				$regex = '/\{l s=\''._PS_TRANS_PATTERN_.'\'( js=1)?\}/U';
				preg_match_all($regex, $content, $matches);

				$theme = _THEME_NAME_;

				/* Get string translation */
				foreach($matches[1] AS $key)
				{
					if (!empty($key))
					{
						$tkey = $template2."_".md5($key);
						if(!isset($found[$tkey]))
						{
							$found[$tkey] = $key;
							$res[] = array( 'language' 			=> 'en',
											'section'  			=> '1 - Front-Office',
											'storage file path' => "/themes/$theme/lang/en.php",
											'array name' 		=> '$_LANG',
											'array key' 		=> $tkey,
											'english string' 	=> $key,
											'group' 			=> $template2);
						}
					}
				}
			}
		}
		return $res;
	}
	
	public function getBackKeys()
	{
		$arr = $this->getStringsFromTC('back','tabsArray');
		$res = array();
		
		foreach($arr as $template_name => $strings)
		{
			foreach($strings as $string => $unused)
			{
				$key = $template_name . md5($string);
				$res[] = array('language' 			=> 'en',
								'section'  			=> '2 - Back-Office',
								'storage file path' => "/translations/en/admin.php",
								'array name' 		=> '$_LANGADM',
								'array key' 		=> $key,
								'english string' 	=> $string,
								'group' 			=> $template_name);
			}
		}
		
		return $res;
	}
	
	public function getBackKeys14()
	{
		$found = array();
		$tabs = scandir(PS_ADMIN_DIR.'/tabs');
		
		if(is_dir(PS_ADMIN_DIR.'/tabs/override'))
		{
			$tabs_override = scandir(PS_ADMIN_DIR.'/tabs/override');
		}
		else
		{
			$tabs_override = array();
		}
		

		$tabs = array_merge($tabs, $tabs_override);
		$tabs[] = '../../classes/AdminTab.php';
		$tabs[] = '../../override/classes/AdminTab.php';
		
		$files = array();
		
		foreach ($tabs AS $tab)
		{
			$filename = $tab;
			if (preg_match('/^(.*)\.php$/', $tab) )
			{
				$tpl = PS_ADMIN_DIR.'/tabs/'.$filename;
				$override_tpl = PS_ADMIN_DIR.'/tabs/override/'.$filename;
				$regular = file_exists($tpl);
				$override     = file_exists($override_tpl);

				if($regular or $override)
				{
					$tab = basename(substr($tab, 0, -4));
					
					if($regular)
					{
						//echo "<p>$tpl</p>";
						$content = file_get_contents($tpl);
					}
					else $content = '';
					
					if($override)
					{
						//echo "<p><b>$override_tpl</b></p>";
						if(!file_exists($override_tpl))
						{
							var_dump($override);
							var_dump($override_tpl);
							echo "<p><b>OOPS</b></p>";
						}
						$override_content = file_get_contents($override_tpl);
						
						//echo "<pre>".htmlentities($override_content)."</pre>";
						$content .= "\n" . $override_content;
					}
					
					$regex = '/this->l\(\''._PS_TRANS_PATTERN_.'\'[\)|\,]/U';
					preg_match_all($regex, $content, $matches);
					foreach ($matches[1] AS $key)
					{
						$wkey = $tab.md5($key);
						$found[$wkey] = array("group" => $tab, "ekey" => $key);
					}
				}
			}
		}
		
		foreach (array('header.inc', 'footer.inc', 'index', 'login', 'password', 'functions') AS $tab)
		{
			$tab = PS_ADMIN_DIR.'/'.$tab.'.php';
			$fd = fopen($tab, 'r');
			$content = fread($fd, filesize($tab));
			fclose($fd);
			$regex = '/translate\(\''._PS_TRANS_PATTERN_.'\'\)/U';
			preg_match_all($regex, $content, $matches);
			foreach ($matches[1] AS $key)
			{
				$wkey = 'index'.md5($key);
				$found[$wkey] = array("group" => 'index', "ekey" => $key);
			}
		}
		
		$res = array();
		foreach($found as $wkey => $ekey)
		{
			$res[] = array( 'language' 			=> 'en',
							'section'  			=> '2 - Back-Office',
							'storage file path' => "/translations/en/admin.php",
							'array name' 		=> '$_LANGADM',
							'array key' 		=> $wkey,
							'english string' 	=> $ekey['ekey'],
							'group'				=> $ekey['group']);
		}
		
		return $res;		
	}
	
	public function getPDFKeys()
	{
		$arr = $this->getStringsFromTC('pdf','tabsArray');
		$res = array();
		
		foreach($arr as $stuff_name => $strings)
		{
			foreach($strings as $string => $unused)
			{
				$key = $stuff_name . md5($string);
				$res[] = array('language' 			=> 'en',
								'section'  			=> '3 - PDF',
								'storage file path' => "/translations/en/pdf.php",
								'array name' 		=> '$_LANGPDF',
								'array key' 		=> $key,
								'english string' 	=> $string,
								'group'				=> $stuff_name);
			}
		}
		
		return $res;
	}
	
	private function _parsePdfClass($filepath, $regex, $langArray, $tab, $tabsArray)
	{
		$content = file_get_contents($filepath);
		preg_match_all($regex, $content, $matches);
		foreach ($matches[1] as $key)
			$tabsArray[$tab][$key] = stripslashes(key_exists($tab.md5(addslashes($key)), $langArray) ? html_entity_decode($langArray[$tab.md5(addslashes($key))], ENT_COMPAT, 'UTF-8') : '');
		return $tabsArray;
	}
	
	public function getPDFKeys14()
	{
		$res = array();
		$found = array();
		
		$regex = '/self::l\(\''._PS_TRANS_PATTERN_.'\'[\)|\,]/U';
		$filepath = _PS_CLASS_DIR_.'PDF.php';
		$tab = 'PDF_invoice';
		
		$content = file_get_contents($filepath);
		preg_match_all($regex, $content, $matches);
		
		foreach ($matches[1] as $key)
		{
			$found[$tab.md5(addslashes($key))] = $key;
		}
		
		foreach($found as $wkey => $ekey)
		{
			$res[] = array( 'language' 			=> 'en',
							'section'  			=> '3 - PDF',
							'storage file path' => "/translations/en/pdf.php",
							'array name' 		=> '$_LANGPDF',
							'array key' 		=> $wkey,
							'english string' 	=> $ekey,
							'group'				=> 'PDF_invoice');
		}
		
		return $res;
		
	}

	public function getModulesKeys()
	{
		$arr = $this->getStringsFromTC('modules','modules_translations');
		
		$res = array();

		$nzg = Tools::getValue('non_zip_github');

		if($nzg == '0' or $nzg == '1')
		{
			$nonzipmodlist = $this->nonZipModules();
			if(!is_array($nonzipmodlist))
			{
				//Unsafe to continue, don't know what the modules are!
				return $res;
			}
			$zipmodlist = $this->zipModules();
		}


		foreach($arr as $theme_name => $module)
		{
			foreach($module as $module_name => $template)
			{	
				//Exclude PrestaShop-modules repo
				if($nzg == '0' and isset($nonzipmodlist[$module_name]))
				{
					continue;
				}
				//Exclude NON PrestaShop-modules repo
				else if($nzg == '1' and !isset($nonzipmodlist[$module_name]))
				{					
					continue;
				}

				if(!empty($_GET['modules']) and !in_array($module_name, $_GET['modules']))
				{
					continue;
				}

				if($theme_name == 'default' || $theme_name == 'default-bootstrap')$theme_name = 'prestashop';


				if(Tools::getValue('compatibility') == '15')
				{
					$path = "/modules/$module_name/translations/en.php";
				}
				else
				{
					$path = "/modules/$module_name/en.php";
				}
				
				if($theme_name != 'prestashop')
				{
					$path = '/themes/' . _THEME_NAME_ . $path;
				}
				else if((_PS_VERSION_ == '1.5.4.0') && !_PS_MODE_DEV_)//take Remi's bug into account
				{
					$path = "/themes/default/modules/$module_name/en.php";
				}

				foreach($template as $template_name => $strings)
				{
					foreach($strings as $string => $unused)
					{
						$key = '<{'.strtolower($module_name)."}$theme_name>".strtolower($template_name).'_'.md5($string);
						$res[] = array('language' 			=> 'en',
										'section'  			=> '4 - Modules',
										'storage file path' => $path,
										'array name' 		=> '$_MODULE',
										'array key' 		=> $key,
										'group'				=> $module_name,
										'subgroup'			=> $template_name,
										'english string' 	=> $string);
					}
				}
			}
		}
		
		return $res;
	}
	
	public function getModulesKeys14()
	{	
		include_once(PS_ADMIN_DIR.'/tabs/AdminTranslations.php');
		
		$_GET['type'] = 'modules';
		
		$rofl = new ReflectionClass("AdminTranslations");
		
		$tc = new AdminTranslations();
		
		$p_suhosin_limit_exceed = $rofl->getProperty('suhosin_limit_exceed');
		$p_suhosin_limit_exceed->setAccessible(true);
		$p_suhosin_limit_exceed->setValue($tc,true);
		
		ob_start();
		$tc->displayFormModules('en');
		ob_clean();
		
		$p_modules_translations = $rofl->getProperty('modules_translations');
		$p_modules_translations->setAccessible(true);
		$modules_translations = $p_modules_translations->getValue($tc);
		
		$res = array();
		
		foreach($modules_translations as $theme_name => $theme)
		{
			if($theme_name == 'default')$theme_name = 'prestashop';
			foreach($theme as $module_name => $files)
			{
				
				$path = "/modules/$module_name/en.php";

				if($theme_name != 'prestashop')
				{
					$path = '/themes/' . _THEME_NAME_ . $path;
				}
				else if(Tools::getValue("template_modules") == "1")
				{
					//echo "skip $theme_name : $module_name<BR/>";
					continue;
				}

				foreach($files as $file_name => $translations)
				{
					foreach($translations as $ekey => $translation_unused)
					{
						$wkey = strtolower("<{{$module_name}}$theme_name>{$file_name}_".md5($ekey));
						$res[] = array( 'language' 			=> 'en',
										'section'  			=> '4 - Modules',
										'storage file path' => $path,
										'group'				=> '',
										'array name' 		=> '$_MODULE',
										'array key' 		=> $wkey,
										'group'				=> $module_name,
										'subgroup'			=> $file_name,
										'english string' 	=> $ekey);
					}	
				}
			}
		}
		
		return $res;
	}
	
	public function getErrorsKeys()
	{
		$arr = $this->getStringsFromTC('errors','errorsArray');
		$res = array();
		
		foreach($arr as $string => $unused)
		{
			$key = md5($string);
			$res[] = array('language' 			=> 'en',
							'section'  			=> '5 - Errors',
							'storage file path' => "/translations/en/errors.php",
							'array name' 		=> '$_ERRORS',
							'array key' 		=> $key,
							'english string' 	=> $string);
		}
		
		return $res;
	}
	
	public function getErrorsKeys14()
	{
		$stringToTranslate = array();
		$dirToParse = array(PS_ADMIN_DIR.'/../',
							PS_ADMIN_DIR.'/../classes/',
							PS_ADMIN_DIR.'/../controllers/',
							PS_ADMIN_DIR.'/../override/classes/',
							PS_ADMIN_DIR.'/../override/controllers/',
							PS_ADMIN_DIR.'/',
							PS_ADMIN_DIR.'/tabs/');
		
		$modules = scandir(_PS_MODULE_DIR_);
		foreach ($modules AS $module)
		{
			if (is_dir(_PS_MODULE_DIR_.$module) && $module != '.' && $module != '..' && $module != '.svn' )
			{
				$dirToParse[] = _PS_MODULE_DIR_.$module.'/';
			}
		}
		
		foreach ($dirToParse AS $dir)
		{
			foreach (scandir($dir) AS $file)
			{
				if (preg_match('/\.php$/', $file) AND file_exists($fn = $dir.$file) AND $file != 'index.php')
				{
					if (!filesize($fn))
						continue;
					preg_match_all('/Tools::displayError\(\''._PS_TRANS_PATTERN_.'\'(, ?(true|false))?\)/U', fread(fopen($fn, 'r'), filesize($fn)), $matches);
					foreach($matches[1] AS $key)
					{
						$stringToTranslate[md5($key)] = $key;
					}
				}
			}
		}
		
		$res = array();
		foreach($stringToTranslate as $wkey => $ekey)
		{
			$res[] = array( 'language' 			=> 'en',
							'section'  			=> '5 - Errors',
							'storage file path' => "/translations/en/errors.php",
							'array name' 		=> '$_ERRORS',
							'array key' 		=> $wkey,
							'english string' 	=> $ekey);
		}
		return $res;
	}
	
	public function getFieldsKeys()
	{
		$arr = $this->getStringsFromTC('fields','tabsArray');
		$res = array();
		
		foreach($arr as $class_name => $strings)
		{
			foreach($strings as $string => $unused)
			{
				$key = $class_name .'_' . md5($string);
				$res[] = array('language' 			=> 'en',
								'section'  			=> '6 - Fields',
								'storage file path' => "/translations/en/fields.php",
								'array name' 		=> '$_FIELDS',
								'array key' 		=> $key,
								'english string' 	=> $string);
			}
		}
		
		return $res;
	}
	
	public function getFieldsKeys14()
	{
		$all_fields = array();
		foreach (scandir(_PS_CLASS_DIR_) AS $classFile)
		{
			if (!preg_match('/\.php$/', $classFile) OR $classFile == 'index.php' OR preg_match('/\.old\.php$/', $classFile))
				continue;
			$className = substr($classFile, 0, -4);
			if (!class_exists($className))
				include_once(_PS_CLASS_DIR_.$classFile);
			if (!class_exists($className))
				continue;
			if (!is_subclass_of($className, 'ObjectModel'))
				continue;
			$fields = call_user_func(array($className, 'getValidationRules'), $className);
			$totranslate = array('validate','validateLang');
			foreach($totranslate as $toto)
			{
				foreach($fields[$toto] as $name => $unused)
				{
					$wkey = $className."_".md5($name);
					$all_fields[$wkey] = $name;
				}
			}
		}
		$res = array();
		foreach($all_fields as $wkey => $ekey)
		{
			$res[] = array( 'language' 			=> 'en',
							'section'  			=> '6 - Fields',
							'storage file path' => "/translations/en/fields.php",
							'array name' 		=> '$_FIELDS',
							'array key' 		=> $wkey,
							'english string' 	=> $ekey);
		}
		return $res;
	}

	public function getMailColonColonLStrings($file)
	{
		$matches = array();

		preg_match_all('/Mail::l\(\s*([\'"])(.*?[^\\\\])\1/', file_get_contents($file), $matches);

		if(isset($matches[2]))
		{
			return $matches[2];
		}

	}

	public function getMail2Keys()
	{
		$tc = new AdminTranslationsController();

		if(!method_exists($tc, 'generateEmails'))
		{
			return array();
		}

		$_POST['lang'] = 'en';
		$strings = array();
		$tc->generateEmails(false, $strings);
		$res = array();

		foreach($strings as $string => $paths)
		{
			$res[] = array( 'language' 			=> 'en',
							'section'  			=> '8 - Mails2',
							'storage file path' => implode(',', $paths),
							'array name' 		=> '',
							'array key' 		=> md5($string),
							'english string' 	=> $string);
		}

		return $res;
	}
	
	public function getMailKeys()
	{
		$rofl = new ReflectionClass("AdminTranslationsController");
		$p_translations_informations = $rofl->getProperty('translations_informations');
		$p_translations_informations->setAccessible(true);
		
		$m_getSubjectMailContent = $rofl->getMethod('getSubjectMailContent');
		$m_getSubjectMailContent->setAccessible(true);
		
		$m_getSubjectMail = $rofl->getMethod('getSubjectMail');
		$m_getSubjectMail->setAccessible(true);
		
		$tc = new AdminTranslationsController();
		$tc->setTypeSelected('mails');
		$tc->getInformations();
		$tc->initFormMails();
		
		$translations_informations = $p_translations_informations->getValue($tc);
		$i18n_dir = $translations_informations['mails']['dir'];
		
		$core_mails = $tc->getMailFiles($i18n_dir, 'core_mail');
		
		$core_subjects = $m_getSubjectMailContent->invoke($tc,$i18n_dir);
		
		$subject_mail = array();
		
		$ftp = $tc->getFileToParseByTypeTranslation();

		$mccl = array();

		foreach ($ftp['php'] as $dir => $files)
		{
			foreach ($files as $file)
			{
				if (is_file($dir.$file) && preg_match('/\.php$/', $file))
				{
					$subject_mail = $m_getSubjectMail->invoke($tc,$dir, $file, $subject_mail);

					$mccl = array_merge($mccl, $this->getMailColonColonLStrings($dir.$file));
				}
			}
        }

		$modules_has_mails = $tc->getModulesHasMails(true);
		
		$module_mails = array();
		
		foreach ($modules_has_mails as $module_name => $module_path)
		{
			$module_mails[$module_name] = $tc->getMailFiles($module_path.'mails/en/', 'module_mail');
		}
		
		$all_mails = $module_mails;
		$all_mails['core_mails'] = $core_mails;
		
		$res = array();
		
		foreach($all_mails as $core_or_module => &$details)
		{
			foreach($details['files'] as $file_name => &$info)
			{
				if(isset($subject_mail[$file_name]))
				{
					$info['subject'] = $subject_mail[$file_name];
				}
				
				$title = array();
				preg_match("/<title>(.*?)<\/title>/", $info['html']['en'],$title);
				$info['title'] = $title[1];
				
				if($core_or_module == 'core_mails')
				{
					$mail_root = '/mails/en/';
				}
				else
				{
					$mail_root = '/modules/' . $core_or_module . '/mails/en/';	
				}
				
				if(isset($info['subject']))
                {

                    $subjects = is_array($info['subject']) ? $info['subject'] : array($info['subject']); //Behavious changed in 1.5.5...
                    
                    foreach($subjects as $subject)
                    {

                        $res[] = array( 'language' 			=> 'en',
                                        'section'  			=> '7 - Mails',
                                        'storage file path' => "/mails/en/lang.php",
                                        'array name' 		=> '$_LANGMAIL',
                                        'array key' 		=> $subject,
                                        'english string' 	=> $subject,
                                        'group'				=> $file_name,
                                        'subgroup' 			=> 'Subject');
					    $mccl = array_diff($mccl, array($subject));
                    }
				}

				if(Tools::getValue('only_mail_subjects') != 1)
				{
					
					$res[] = array( 'language' 				=> 'en',
									'section'  				=> '7 - Mails',
									'storage file path' 	=> ($sfp = $mail_root.$file_name.".html"),
									'array name' 			=> '',
									'array key' 			=> "mail:"._PS_VERSION_.":".str_replace('/en/','/[iso]/',$sfp),
									'english string' 		=> $info['html']['en'],
									'group'					=> $file_name,
									'subgroup'				=> 'HTML Version');
					
					if(isset($info['txt']))
					{
						$res[] = array( 'language' 				=> 'en',
										'section'  				=> '7 - Mails',
										'storage file path' 	=> ($sfp = $mail_root.$file_name.".txt"),
										'array name' 			=> '',
										'array key' 			=> "mail:"._PS_VERSION_.":".str_replace('/en/','/[iso]/',$sfp),
										'english string' 		=> $info['txt']['en'],
										'group'					=> $file_name,
										'subgroup'				=> 'Plain Text Version');
					}
				}
				
			}
		}
		
		if(!empty($mccl))
		{
			foreach($mccl as $subject)
			{
				$res[] = array( 'language' 			=> 'en',
								'section'  			=> '7 - Mails',
								'storage file path' => "/mails/en/lang.php",
								'array name' 		=> '$_LANGMAIL',
								'array key' 		=> $subject,
								'english string' 	=> $subject,
								'group'				=> 'generic',
								'subgroup' 			=> 'Subject');
			}
		}

		return $res;		
		
	}

	public function getMailKeys14()
	{
		include_once(PS_ADMIN_DIR.'/tabs/AdminTranslations.php');
		$tc = new AdminTranslations();
		
		$rofl = new ReflectionClass("AdminTranslations");
		$m_getSubjectMail = $rofl->getMethod("getSubjectMail");
		$m_getSubjectMail->setAccessible(true);
		
		$m_getSubjectMailContent = $rofl->getMethod("getSubjectMailContent");
		$m_getSubjectMailContent->setAccessible(true);
		
		$core_mails = array();
		$module_mails = array();
		
		$subject_mail = array();
		$modules_has_mails = $tc->getModulesHasMails();
		$arr_files_to_parse = array(
			_PS_ROOT_DIR_.'/controllers',
			_PS_ROOT_DIR_.'/classes',
			PS_ADMIN_DIR.'/tabs',
			PS_ADMIN_DIR,
		);
		
		$mccl = array();

		$arr_files_to_parse = array_merge($arr_files_to_parse, $modules_has_mails);
		foreach ($arr_files_to_parse as $path)
		{
			$subject_mail = $m_getSubjectMail->invoke($tc, $path, $subject_mail);

			foreach(scandir($path) as $file)
			{
				if(preg_match('/.*?\.php$/', $file) and is_file("$path/$file"))
				{
					$mccl = array_merge($mccl, $this->getMailColonColonLStrings("$path/$file"));
					
				}
			}
		}
		
		$core_mails = $tc->getMailFiles(_PS_MAIL_DIR_, 'en', 'core_mail');
		$core_mails['subject'] = $m_getSubjectMailContent->invoke($tc,_PS_MAIL_DIR_.'en');
		foreach ($modules_has_mails AS $module_name=>$module_path)
		{
			$module_mails[$module_name] = $tc->getMailFiles($module_path.'/mails/', 'en', 'module_mail');
			$module_mails[$module_name]['subject'] = $core_mails['subject'];
		}
		
		$all_mails = $module_mails;
		$all_mails['core_mails'] = $core_mails;
		
		$res = array();
		
		foreach($all_mails as $core_or_module => &$details)
		{
			if(!isset($details['files']) or !is_array($details['files']))continue;
			foreach($details['files'] as $file_name => &$info)
			{
				if(isset($subject_mail[$file_name]))
				{
					$info['subject'] = $subject_mail[$file_name];
				}
				
				if($core_or_module == 'core_mails')
				{
					$mail_root = '/mails/en/';
				}
				else
				{
					$mail_root = '/modules/' . $core_or_module . '/mails/en/';	
				}
				
				if(isset($info['subject']))
				{
					$res[] = array( 'language' 			=> 'en',
									'section'  			=> '7 - Mails',
									'storage file path' => "/mails/en/lang.php",
									'array name' 		=> '$_LANGMAIL',
									'array key' 		=> $info['subject'],
									'english string' 	=> $info['subject'],
									'group'				=> $file_name,
									'subgroup'			=> 'Subject');

					$mccl = array_diff($mccl, array($info['subject']));
				}
				
				if(Tools::getValue('only_mail_subjects') != 1)
				{

					$res[] = array( 'language' 			=> 'en',
									'section'  			=> '7 - Mails',
									'storage file path' => ($sfp = $mail_root.$file_name.".html"),
									'array name' 		=> '',
									'array key' 		=> "mail:"._PS_VERSION_.":".str_replace('/en/','/[iso]/',$sfp),
									'english string' 	=> $info['html']['en'],
									'group'				=> $file_name,
									'subgroup'			=> 'HTML Version');
					
					if(isset($info['txt']))
					{
						$res[] = array( 'language' 				=> 'en',
										'section'  			    => '7 - Mails',
										'storage file path' 	=> ($sfp = $mail_root.$file_name.".txt"),
										'array name' 			=> '',
										'array key' 			=> "mail:"._PS_VERSION_.":".str_replace('/en/','/[iso]/',$sfp),
										'english string' 		=> $info['txt']['en'],
										'group'				    => $file_name,
										'subgroup'				=> 'Plain Text Version');
					}
				}
			}
		}

		if(!empty($mccl))
		{
			foreach($mccl as $subject)
			{
				$res[] = array( 'language' 			=> 'en',
								'section'  			=> '7 - Mails',
								'storage file path' => "/mails/en/lang.php",
								'array name' 		=> '$_LANGMAIL',
								'array key' 		=> $subject,
								'english string' 	=> $subject,
								'group'				=> 'generic',
								'subgroup' 			=> 'Subject');
			}
		}
		
		return $res;		
		
	}
	
	/*PB*/
	private $translation_files = array();
	public function getTranslation($section, $filepath, $iso, $key)
	{
		if(false !== strpos($section, 'Mails') and false === strpos($filepath, '/lang.php'))
		{
			if(file_exists($filepath))return file_get_contents($filepath);
			else return '';
		}

		if(!isset($this->translation_files[$iso]))$this->translation_files[$iso]=array();
		if(!isset($this->translation_files[$iso][$filepath]))$this->translation_files[$iso][$filepath]=$this->getTranslationsArray($filepath);
		$array = $this->translation_files[$iso][$filepath];
		if(isset($array[$key]))return $array[$key];
		else return '';
	}

	public function getTranslationsArray($filepath, $pass=0)
    {
            $translations = array();

            $array_name = "";

$exp = <<<'NOW'
/\s*\$\w+\s*\[\s*'(.*?[^\\])'\s*\]\s*=\s*'(.*?[^\\])'\s*;\s*/
NOW;

            if(file_exists($filepath))
            {
                    $matches = array();
                    $n = preg_match_all($exp, file_get_contents($filepath), $matches);
                    if($n !== false)
                    {
                            for($i=0; $i < $n; $i+=1)
                            {
                                    $translations[$matches[1][$i]] = $matches[2][$i];
                            }
                    }
            }

            $m = array();
            if(($pass == 0) and preg_match('#(.*?)/modules/(\w+)(/translations)?/([a-z]{2})\.php$#', $filepath, $m))
            {
            	$altpath = "{$m[1]}/modules/{$m[2]}".($m[3] == '/translations' ? '' : '/translations')."/{$m[4]}.php";
            	if(file_exists($altpath))
            	{
            		$translations = array_merge($translations, $this->getTranslationsArray($altpath, 1));
            	}
            }

            return $translations;
    }

    public static function my_fputcsv($handle, $array, $delim, $quote)
    {
    	fputs($handle, implode($delim, array_map(function($item) use ($delim, $quote){
    			
    			if(false !== strpos($item, $delim) or false !== strpos($item, $quote) or false !== strpos($item, "\n"))
    			{
    				$item = $quote.preg_replace("/(?:$quote)+/", $quote.$quote, $item).$quote;
    			}

    			return $item;}, 
    		$array))."\n");
    }

	public function getAllKeys($with_translations)
	{
		$languages = Language::getLanguages(false);

		//Ignore the "Constant _PS_THEME_SELECTED_DIR_ already defined error : this is 'normal'"
		set_error_handler(function($errno, $errstr, $errfile, $errline, $errcontext){
			return $errno == 8 and strpos($errstr, "_PS_THEME_SELECTED_DIR_") >= 0;
		});//*/

				
		if(Tools::getValue('filter_sections'))
		{
			$methods = array();

			$map = array(
				"Front-Office" 	=> "getFrontKeys",
				"Back-Office"	=> "getBackKeys",
				"Modules" 		=> "getModulesKeys",
				"Errors" 		=> "getErrorsKeys",
				"Fields" 		=> "getFieldsKeys",
				"PDF" 			=> "getPDFKeys",
				"Mails" 		=> "getMailKeys",
				"Tabs"			=> "getTabsKeys",
				"Mails2" 		=> "getMail2Keys"
			);

			foreach(Tools::getValue('section') as $section)
			{
				if(isset($map[$section]))
				{
					if($section == 'Back-Office')
					{
						array_unshift($methods, $map[$section]);
					}
					else
					{
						$methods[] = $map[$section];
					}
				}
			}
		}
		else
		{
			$methods = array('getTabsKeys','getBackKeys','getFrontKeys','getPDFKeys','getModulesKeys','getErrorsKeys','getFieldsKeys','getMailKeys');
		}

		if(version_compare(_PS_VERSION_, "1.5", "<"))
		{
			$methods = array_map(function($item){return $item.'14';}, array_filter($methods, function($v){return $v != 'getTabsKeys';}));	
		}
		
		if($with_translations === false)
		{
			if(Tools::getValue('xliff') == 'yeah')
			{
				$outname = 'template_'._PS_VERSION_.'.xliff';
			}
			else if(Tools::getValue('po') == 'yeah')
			{
				$outname = 'template_'._PS_VERSION_.'.po';
			}
			else
			{
				$outname = 'template_'._PS_VERSION_.'.xml';
			}
		}
		else
		{
			$outname = 'multilang_'._PS_VERSION_.'.xls';
		}

		
		$path = dirname(__FILE__).'/'.$outname;
		
		
		if($with_translations !== false)
		{
			$xl 	   = new PHPExcel();
			$sh 	   = $xl->setActiveSheetIndex(0);

			$row = 1;

			$headers = array('Language', 'Section', 'Storage File Path', 'Array Name', 'Group', 'SubGroup', 'Array Key', 'English String');

			foreach($languages as $language)
			{
				$headers[] = $language['iso_code'];
			}

			$col = 'A';
			foreach($headers as $h)
			{
				$sh->getCell("{$col}1")->setValue($h);
				++$col;
			}

			$line = 2;

			foreach($methods as $method)
			{
				$arr = $this->$method();
				foreach($arr as $row)
				{
					$storage  		= str_replace('/en.php', '/[iso].php', $row['storage file path']);
					$storage 		= str_replace('/en/', '/[iso]/', $storage); 

					$group 			= isset($row['group']) 		? $row['group'] 	: '';
					$subgroup 		= isset($row['subgroup']) 	? $row['subgroup'] 	: '';

					$data = array('[iso]', $row['section'], $storage, $row['array name'], $group, $subgroup, $row['array key'], $row['english string']);

					foreach($languages as $language)
					{
						$iso 			= $language['iso_code'];
						$filepath 		= _PS_ROOT_DIR_ . str_replace('/en.php', "/$iso.php", str_replace('/en/', "/$iso/", $row['storage file path']));
						$translation 	= $this->getTranslation($row['section'], $filepath, $iso, $row['array key']);

						$data[]			= $translation;
					}
					
					$col = 'A';
					foreach($data as $d)
					{
						$sh->getCell("{$col}{$line}")->setValue($d);
						++$col;
					}

					++$line;
				}
			}

			$objWriter = \PHPExcel_IOFactory::createWriter($xl, 'Excel5');
			$objWriter->save($path);

		}
		else if(Tools::getValue('xliff') == 'yeah')
		{
			$root = new SimpleXMLElement("<xliff version='1.2'/>");

			$files = array();

			foreach($methods as $method)
			{
				$arr = $this->$method();
				foreach($arr as $row)
				{
					$storagepath  		= str_replace('/en.php', '/[iso].php', str_replace('/en/', '/[iso]/', $row['storage file path']));
					$m = array();
					if(preg_match('/(?:\d+\s*\-\s*)?(.*)$/', $row['section'], $m))
					{
						$category       = $m[1];
						$section		= isset($row['group']) 		? $row['group'] 	: '';
						$subsection		= isset($row['subgroup']) 	? $row['subgroup'] 	: '';

						$method 		= ($row['array name'] != '' && $row['array name'] != null) ? 'ARRAY' : 'FILE';
						$type  			= $method == 'ARRAY' ? 'STRING' : (preg_match("/\.html$/", $storagepath) ? 'HTML' : 'TXT');

						$mkey           = htmlspecialchars($row['array key']);
						$text 			= htmlspecialchars($row['english string']);
					}

					if(!isset($files[$storagepath]))
					{
						$files[$storagepath] = array();
					}

					$files[$storagepath][] = array('category' => $category, 'section' => $section, 'subsection' => $subsection, 'method' => $method, 'type' => $type, 'mkey' => $mkey, 'text' => $text);			

				}
			}

			foreach($files as $p => $messages)
			{
				$f = $root->addChild('file');
				$f->addAttribute('original', $p);
				foreach($messages as $message)
				{
					$tu  = $f->addChild("trans-unit");
					$tu->addAttribute('id', $message['mkey']);
					$src = $tu->addChild('source', $message['text']);
					$src->addAttribute('xml:lang', 'en');
				}
			}

			$root->asXML($path);
		}
		else if(Tools::getValue('po') == 'yeah')
		{
			require_once dirname(__FILE__).'/lib/php-po/PoFile.php';
			$po = new PoFileCore();
			foreach($methods as $meth)
			{
				$arr = $this->$meth();
				foreach($arr as $row)
                {
                    if(trim($row['english string']) == '')continue;

					$storagepath  		= str_replace('/en.php', '/[iso].php', $row['storage file path']);
					
					$m = array();
					if(preg_match('/(?:\d+\s*\-\s*)?(.*)$/', $row['section'], $m))
					{
						$category       = $m[1];
						$section		= isset($row['group']) 		? $row['group'] 	: '';
						$subsection		= isset($row['subgroup']) 	? $row['subgroup'] 	: '';

						if($category !== 'Mails2')
						{
							$storagepath 		= str_replace('/en/', '/[iso]/', $storagepath); 
						}

						if($category == 'Modules' && ($exp=Tools::getValue('module_regexp'))!='')
						{
							if(!preg_match($exp, $section))
							{
								continue;
							}
						}

						$method 		= ($row['array name'] != '' && $row['array name'] != null) ? 'ARRAY' : 'FILE';
						$type  			= $method == 'ARRAY' ? 'STRING' : (preg_match("/\.html$/", $storagepath) ? 'HTML' : 'TXT');

						$ctxt = array();
						if($category !== '')$ctxt[] = $category;
						if($section !== '')$ctxt[] = $section;
						if($subsection !== '')$ctxt[] = $subsection;

						$entry = &$po->addMessage(str_replace("\'", "'", $row['english string']), '');
						$entry['msgctxt'] 	= "\"".implode(' > ', $ctxt)."\"";
						$entry['comments']	= array(
							'#:' => array("{$row['array key']}@$storagepath")
						);
					}
				}
			}
			$po->write($path);
		}
		else
		{
			$root = new SimpleXMLElement("<messages/>");
			
			foreach($methods as $meth)
			{
				$arr = $this->$meth();
				foreach($arr as $row)
                {
                    if(trim($row['english string']) == '')continue;

					$storagepath  		= str_replace('/en.php', '/[iso].php', $row['storage file path']);
					
					$m = array();
					if(preg_match('/(?:\d+\s*\-\s*)?(.*)$/', $row['section'], $m))
					{
						$category       = $m[1];
						$section		= isset($row['group']) 		? $row['group'] 	: '';
						$subsection		= isset($row['subgroup']) 	? $row['subgroup'] 	: '';

						if($category !== 'Mails2')
						{
							$storagepath 		= str_replace('/en/', '/[iso]/', $storagepath); 
						}

						if($category == 'Modules' && ($exp=Tools::getValue('module_regexp'))!='')
						{
							if(!preg_match($exp, $section))
							{
								//echo "ignoring $section cuz $exp<BR/>";
								continue;
							}
						}

						$method 		= ($row['array name'] != '' && $row['array name'] != null) ? 'ARRAY' : 'FILE';
						$type  			= $method == 'ARRAY' ? 'STRING' : (preg_match("/\.html$/", $storagepath) ? 'HTML' : 'TXT');

						$message = $root->addChild('message');		
						$message->addChild('category'	, $category			);
						$message->addChild('section'	, $section 			);
						$message->addChild('subsection'	, $subsection		);
						$message->addChild('method'		, $method 			);
						$message->addChild('type'		, $type 			);
						$message->addChild('custom'		, $row['array name']);  
						$message->addChild('path'		, $storagepath		);
						
                        $message->addChild('mkey', htmlspecialchars($row['array key']));
						$message->addChild('text', htmlspecialchars($row['english string']));
					}
				}
			}

			$root->asXML($path);

		}
		
		restore_error_handler();

		return $outname;
	}

	public function nonZipModules()
	{
		$modules = array();

		$gh = new GitHub\Client();

		foreach($gh->dir("PrestaShop", "PrestaShop-modules") as $entry)
		{
			if($entry['type'] == 'dir')
			{
				$modules[$entry['name']] = $entry;
			}
		}

		return $modules;
	}

	public function zipModules()
	{
		$modules = array();

		$gh = new GitHub\Client();

		foreach($gh->dir("PrestaShop", "PrestaShop", "modules") as $entry)
		{
			if($entry['type'] == 'dir')
			{
				$modules[$entry['name']] = $entry;
			}
		}

		return $modules;
	}

	public function getContent()
	{
		global $smarty;
		
		$download_url = false;
		$download_template_url = false;
		$action = Tools::getValue('action');

		if($action == 'export')
		{
			$download_url = 'http://'.Tools::getShopDomain().__PS_BASE_URI__.'modules/translatool/'.$this->getAllKeys(true);
			$smarty->assign('yay', "Should be OK :)");
		}
		else if($action == 'export_template')
		{
			$download_template_url = 'http://'.Tools::getShopDomain().__PS_BASE_URI__.'modules/translatool/'.$this->getAllKeys(false);
			$smarty->assign('yay', "Exported template!");
		}
		else if($action == 'import')
		{
			if(isset($_FILES['csv']) and $_FILES['csv']['error'] == 0)
			{
				$res = $this->import($_FILES['csv']['tmp_name']);

				if($res === true)
				{
					$smarty->assign('yay','Successfully imported translations!');
				}
				else
				{
					$smarty->assign('oops',$res);
				}
			}
			else
			{
				$smarty->assign('oops','Cannot upload file!');
			}
		}
		
		$languages = Language::getLanguages(false);
		$smarty->assign('languages',$languages);
		$smarty->assign('token',Tools::getValue('token'));
		$smarty->assign('modules', Module::getModulesInstalled());
		

		$smarty->assign('download_url'			, $download_url);
		$smarty->assign('download_template_url'	, $download_template_url);

		$smarty->assign('v15', version_compare(_PS_VERSION_, "1.5", ">="));
		
		return $smarty->fetch($this->abspath('views/templates/back/content.tpl.html'));
	}

	public static function CSVForEach($file, $func)
	{
	        $f = fopen($file, 'r');
	        $first_line = fgets($f);
	        rewind($f);

	        //guess separator
	        if(substr_count($first_line, ";") > substr_count($first_line, ","))
	        {
	                $separator=";";
	        }
	        else
	        {
	                $separator=",";
	        }

	        $headers = fgetcsv($f, 0, $separator, '"', '"');

            while($row = fgetcsv($f, 0, $separator, '"', '"'))
            {
                    $row = array_combine($headers, $row);
                    $func($row);
            }

	        fclose($f);
	}


	public static function slashify($str)
    {
            return preg_replace('/\\\\*([\'])/', "\\\\$1", $str);
    }


	public function import($file)
	{
		$xl = \PHPExcel_IOFactory::load($file);
		$sh = $xl->setActiveSheetIndex(0);
		
		$h2c  = array();
		$isos = array();

		for($col = 'A'; $v=$sh->getCell("{$col}1")->getValue(); ++$col)
		{
			$h2c[$v] = $col;
			if(preg_match('/^[a-z]{2}$/', $v))
			{
				$isos[] = $v;
			}
		}

		$files = array();

		for($line=2; $sh->getCell("A$line")->getValue(); ++$line)
		{
			foreach($isos as $iso)
			{
				$path 			= _PS_ROOT_DIR_ . '/' . str_replace('[iso]', $iso, $sh->getCell($h2c['Storage File Path'].$line)->getValue());
				$array_name		= $sh->getCell($h2c['Array Name'].$line)->getValue();
				$key			= $sh->getCell($h2c['Array Key'].$line)->getValue();
				$translation    = $sh->getCell($h2c[$iso].$line)->getValue();

				if($array_name)
				{
					if(!isset($files[$path]))
					{
						$files[$path] = array('array_name' => $array_name, 'translations' => array());
					}

					$files[$path]['translations'][$key] = $translation;
				}
			}
		}
		

		foreach($files as $path => $data)
		{
			if(file_exists($path))
			{
				$previous_translations = $this->getTranslationsArray($path);
			}
			else
			{
				$previous_translations = array();
			}

			if(Tools::getValue('overwrite') == 'overwrite')
			{
				//Overwrite translations
				$translations = array_merge($previous_translations, $data['translations']);
			}
			else
			{
				//Take previous translations in priority
				$translations = array_merge($data['translations'], $previous_translations);
			}
			

			$code = "";

			if($data['array_name'] != '$_TABS')
			{
				$code = "<?php\n\nglobal {$data['array_name']};\n\n{$data['array_name']} = array();\n\n";
			}
			else
			{
				$code = "<?php\n\n{$data['array_name']} = array();\n\n";
			}

			foreach($translations as $key => $translation)
			{
				if(trim($translation) != '')
				{
					$code .= "{$data['array_name']}['".static::slashify($key)."'] = '" .static::slashify(trim($translation))."';\n";
				}
			}

			if($data['array_name'] == '$_TABS')
			{
				$code .= 'return $_TABS;';
			}
			
			/*
			if(file_exists($path))
			{
				rename($path, $path.".old");
			}
			*/
			file_put_contents($path, $code);
		}

		return true;

	}
	
}
