<?php

require_once 'abstract.php';

class Ho_ShellImport_Shell_Import extends Mage_Shell_Abstract {
	/**
	 * Run script
	 * 
	 * @return void
	 */
	public function run() {
		$action = $this->getArg('action');
		if (empty($action)) {
			echo $this->usageHelp();
		} else {
			$actionMethodName = $action.'Action';
	        if (method_exists($this, $actionMethodName)) {
	        	$this->$actionMethodName();
	        } else {
	        	echo "Action $action not found!\n";
	        	echo $this->usageHelp();
	        	exit(1);
	        }
		}
	}
	
	
	
    /**
     * Retrieve Usage Help Message
     * 
     * @return string
     */
    public function usageHelp() {
    	$help = 'Available actions: ' . "\n";
    	$methods = get_class_methods($this);
		foreach ($methods as $method) {
			if (substr($method, -6) == 'Action') {
				$help .= '-action ' . substr($method, 0, -6);
				$helpMethod = $method.'Help';
				if (method_exists($this, $helpMethod)) {
					$help .= $this->$helpMethod();
				}
				$help .= "\n";
			}
		}
    	return $help;
    }


    /**
     * Execute a profile with the exec command, running completely on the server.
     *
     * @return mixed
     */
    public function execAction()
    {
        $profileId = (int) $this->getArg('profile');
        if (! $profileId)
        {
            echo "No profile selected." . $this->importActionHelp() . "\n";
            return;
        }

        try
        {
            /** @var $shellImport Ho_ShellImport_Model_Import */
            $shellImport = Mage::getModel('ho_shellimport/import');
            $shellImport->execRun($profileId);
        } catch (Mage_Core_Exception $e) {
            echo $e->getMessage() . "\n";
        } catch (Exception $e) {
            echo "Import unknown error:\n";
            echo $e . "\n";
        }
    }

    /**
     * Run a single batch with the import
     *
     * @return mixed
     */
    public function execBatchAction()
    {
        $batchId = (int) $this->getArg('batch');
        if (! $batchId)
        {
            echo "Oops, something went wrong: No batch ID selected." . "\n";
            return;
        }

        $rowIds = explode(',',$this->getArg('rows'));
        if (! $rowIds)
        {
            echo "Oops, something went wrong: No rows selected." . "\n";
            return;
        }

        try
        {
            /** @var $shellImport Ho_ShellImport_Model_Import */
            $shellImport = Mage::getModel('ho_shellimport/import');
            $shellImport->batchRun($batchId, $rowIds);
        } catch (Mage_Core_Exception $e) {
            echo $e->getMessage() . "\n";
        } catch (Exception $e) {
            echo "Import unknown error:\n";
            echo $e . "\n";
        }
    }


    public function execFinishAction()
    {
        $batchId = (int) $this->getArg('batch');
        if (! $batchId)
        {
            echo "Oops, something went wrong: No batch ID selected." . "\n";
            return;
        }

        try
        {
            /** @var $shellImport Ho_ShellImport_Model_Import */
            $shellImport = Mage::getModel('ho_shellimport/import');
            $shellImport->batchFinish($batchId);
        } catch (Mage_Core_Exception $e) {
            echo $e->getMessage() . "\n";
        } catch (Exception $e) {
            echo "Import unknown error:\n";
            echo $e . "\n";
        }
    }

    public function importActionHelp()
    {
        return "\n\tUsage example:\n"
              ."\tphp import.php -action exec -profile 123\n"
              ."\tYou can find the ID in the Admin Panel > Import/Export > Dataflow - Profiles"
              ."\tAdmin Panel > Import/Export > Dataflow - Advanced Profiles";
    }
}

$shell = new Ho_ShellImport_Shell_Import();
$shell->run();