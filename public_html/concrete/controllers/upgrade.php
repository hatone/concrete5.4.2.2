<?php 
defined('C5_EXECUTE') or die("Access Denied.");
if (!defined('E_DEPRECATED')) {
	error_reporting(E_ALL ^ E_NOTICE);
} else {
	error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);
}
ini_set('display_errors', 1);
if (!ini_get('safe_mode')) {
	@set_time_limit(0);
}

date_default_timezone_set(@date_default_timezone_get());

class UpgradeController extends Controller {

	private $notes = array();
	private $upgrades = array();
	private $site_version = null;
	public $upgrade_db = true;
	
	public function on_start() {
		$cnt = Loader::controller('/dashboard/system/update');
		$cnt->secCheck();
		// if you just reverted, but didn't manually clear out your files - cache would be a prob here.
		$ca = new Cache();
		$ca->flush();
		
		$this->site_version = Config::get('SITE_APP_VERSION');
	}
	
	public function view() {
		if ($this->get('force') == 1 || $this->get('source') == 'dashboard_update') {
			$this->do_upgrade();
		} else {	
			$sav = $this->site_version;
	
			if (!$sav) {
				$message = t('Unable to determine your current version of Concrete. Upgrading cannot continue.');
			} else 	if (version_compare($sav, APP_VERSION, '>')) {
				$message = t('Upgrading from <b>%s</b>', $sav) . '<br/>';
				$message .= t('Upgrading to <b>%s</b>', APP_VERSION) . '<br/><br/>';
				$message .= t('Your current website uses a version of Concrete5 greater than this one. You cannot upgrade.');
				$this->set('message', $message);
			} else if (version_compare($sav, APP_VERSION, '=')) {
				$this->set('message', t('Your site is already up to date! The current version of Concrete5 is <b>%s</b>. You should remove this file for security.', APP_VERSION));
			} else {
				if ($this->post('do_upgrade')) {
					$this->do_upgrade();
				} elseif(version_compare($sav, '5.3.2', '<')) {
					$this->set('hide_force',true);
					$this->set('message',t('You must first upgrade your site to version 5.3.2'));
				} else {
					// do the upgrade
					$this->set_upgrades();
					$allnotes = array();
					foreach($this->upgrades as $ugh) {
						if (method_exists($ugh, 'notes')) {
							$notes = $ugh->notes();
							if ($notes != '') {
								if (is_array($notes)) {
									$allnotes = array_merge($allnotes, $notes);
								} else {
									$allnotes[] = $notes;
								}
							}
						}
					}
					
					$message = '';
					$message .= t('Upgrading from <b>%s</b>', $sav) . '<br/>';
					$message .= t('Upgrading to <b>%s</b>', APP_VERSION) . '<br/><br/>';
	
					if (count($allnotes) > 0) { 
						$message .= '<ul>';
						foreach($allnotes as $n) {
							$message .= '<li>' . $n . '</li>';
						}
						$message .= '</ul><br/>';
					}
					
					$this->set('do_upgrade', true);			
					$this->set('message', $message);
				}
			}
		}		
	}
	
	private function set_upgrades() {
		$ugvs = array();
		
		
		$sav = $this->site_version;

		if (version_compare($sav, '5.0.0b1', '<')) { 
			$ugvs[] = "version_500a1";
		}
		if (version_compare($sav, '5.0.0b2', '<')) { 
			$ugvs[] = "version_500b1";
		}
		if (version_compare($sav, '5.0.0', '<')) { 
			$ugvs[] = "version_500b2";
		}
		if (version_compare($sav, '5.1.0', '<')) { 
			$ugvs[] = "version_500";
		}
		if (version_compare($sav, '5.2.0', '<')) { 
			$ugvs[] = "version_510";
		}
		if (version_compare($sav, '5.3.0', '<')) { 
			$ugvs[] = "version_520";
		}
		if (version_compare($sav, '5.3.2', '<')) { 
			$ugvs[] = "version_530";
		}

		if (version_compare($sav, '5.3.3', '<')) { 
			$ugvs[] = "version_532";
		}

		if (version_compare($sav, '5.3.3.1', '<')) { 
			$ugvs[] = "version_533";
		}
		if (version_compare($sav, '5.4.0', '<')) { 
			$ugvs[] = "version_5331";
			$ugvs[] = "version_540";
		}
		if (version_compare($sav, '5.4.1', '<')) { 
			$ugvs[] = "version_5406";
			$ugvs[] = "version_541";
		}
		if (version_compare($sav, '5.4.2', '<')) { 
			$ugvs[] = "version_5411";
			$ugvs[] = "version_542";
		}
		if (version_compare($sav, '5.4.2.1', '<')) { 
			$ugvs[] = "version_5421";
		}
		foreach($ugvs as $ugh) {
			$this->upgrades[] = Loader::helper('concrete/upgrade/' . $ugh);
		}
	}
	
	public function refresh_schema() {
		if ($this->upgrade_db) {
			$installDirectory = DIR_BASE_CORE . '/config';
			$file = $installDirectory . '/db.xml';
			if (!file_exists($file)) {
				throw new Exception(t('Unable to locate database import file.'));
			}		
			$err = Package::installDB($file);
			
			// now we refresh the block schema
			$btl = new BlockTypeList();
			$btArray = $btl->getInstalledList();
			foreach($btArray as $bt) {
				$bt->refresh();
			}
			$this->upgrade_db = false;
		}
	}
	
	private function do_upgrade() {
		$runMessages = array();
		$prepareMessages = array();
		try {
			$ca = new Cache();
			$ca->flush();
			$this->set_upgrades();
			foreach($this->upgrades as $ugh) {
				if (method_exists($ugh, 'prepare')) {
					$prepareMessages[] =$ugh->prepare($this);
				}
			}
			
			foreach($this->upgrades as $ugh) {
				if (method_exists($ugh, 'run')) {
					$runMessages[] = $ugh->run();
				}
			}
			
			$message = '';
			if(is_array($prepareMessages) && count($prepareMessages)) {
				foreach($prepareMessages as $m) {
					if(is_array($m)) {
						$message .= implode("<br/>",$m);
					}	
				}
			}
			
			if(is_array($runMessages) && count($runMessages)) {
				foreach($runMessages as $m) {
					if(is_array($m)) {
						$message .= implode("<br/>",$m);
					}	
				}
				
				if(strlen($message)) {
					$this->set('had_failures',true);
				}
			
			}			
			$upgrade = true;
		} catch(Exception $e) {
			$upgrade = false;
			$message .= t('An Unexpected Error occurred while upgrading: %s', $e->getMessage());
		}
		
		if ($upgrade) {
			$completeMessage .= t('Upgrade to <b>%s</b> complete!', APP_VERSION) . '<br/><br/>';
			Config::save('SITE_APP_VERSION', APP_VERSION);
		}
		$this->set('completeMessage',$completeMessage);	
		$this->set('message', $message);
	}
}
	
