<?php
/**
 * @method setProfileId(int $profile)
 * @method int getProfileId()
 * @method setUsername(string $username)
 * @method string getUsername()
 * @method Ho_ShellImport_Model_Import setPassword(string $password)
 * @method string getPassword()
 * @method Ho_ShellImport_Model_Import setBatchCount(int $count)
 * @method int getNumberOfRecords()
 */

class Ho_ShellImport_Model_Import extends Mage_Core_Model_Abstract
{

    /**
     * @param $profileId
     *
     * @return Ho_ShellImport_Model_Import
     */
    public function execRun($profileId)
    {
        /** @var $profile Mage_Dataflow_Model_Profile */
        $profile = Mage::getModel('dataflow/profile');
        if ($profileId) {
            $profile->load($profileId);
            if (!$profile->getId()) {
                Mage::throwException('Could not load profile '.$profileId);
            }
        }

        Mage::register('current_convert_profile', $profile);

        //run the profile
        echo $this->getMemoryUsage() . " - Preparing profile...";
        $profile->run();
        echo "done\n";

        foreach ($profile->getExceptions() as $e) {
            echo $this->getMemoryUsage() . " - " . $e->getMessage() . "\n";
        }

        /** @var $batchModel Mage_Dataflow_Model_Batch */
        $batchModel = Mage::getSingleton('dataflow/batch');
        if (! $batchModel->getId() || ! $batchModel->getAdapter()) {
            Mage::throwException('Could not load dataflow batch');
        }
        $this->addData($batchModel->getParams());

        /** @var $batchImportModel Mage_Dataflow_Model_Batch_Import */
        $batchImportModel = $batchModel->getBatchImportModel();

        /** @var $adapter Ho_ReprintPrinterSupplies_Model_Convert_Adapter_Supplies */
        $adapter = Mage::getModel($batchModel->getAdapter());

        $importIds = $batchImportModel->getIdCollection();
        $importCount = count($importIds);

        echo $this->getMemoryUsage() . " - Starting import with batches of {$this->getNumberOfRecords()}\n";

        $chunks = array_chunk($importIds, $this->getNumberOfRecords());

        echo $this->getMemoryUsage() . " - 0/{$importCount} - start\n";

        $baseDir = Mage::getBaseDir().'/shell';
        foreach($chunks as $cnt => $chunk)
        {
            $currentImportCount = $cnt*$this->getNumberOfRecords()+count($chunk);

            $rows = implode(',',$chunk);
            $buffer = shell_exec("php $baseDir/import.php -action execBatch -batch {$batchModel->getId()} -rows \"{$rows}\"");

            if (empty($buffer))
            {
                Mage::throwException($this->getMemoryUsage() . " - {$currentImportCount}/{$importCount} - Response is empty");
            }
            else
            {
                $result = json_decode($buffer);

                echo $this->getMemoryUsage() . " - {$currentImportCount}/{$importCount} - $result->savedRows rows saved\n";
                if (count($result->errors))
                {
                    foreach ($result->errors as $error)
                    {
                        echo $this->getMemoryUsage() . " - $error\n";
                    }
                }
            }
        }

        echo $this->getMemoryUsage() . " - Finishing...\n";
        $buffer = shell_exec("php $baseDir/import.php -action execFinish -batch {$batchModel->getId()}");
        echo $buffer."\n";

        return $this;
    }


    /**
     * Run a single Batch, this method is heavily based on:
     * @see Mage_Adminhtml_System_Convert_ProfileController::batchRunAction
     *
     * @param $batchId
     * @param $rowIds
     * @return mixed
     */
    public function batchRun($batchId, $rowIds)
    {
        // for increased compatability for models that expect an ajax request.
        Mage::app()->getRequest()->setParam('batch_id',$batchId);
        Mage::app()->getRequest()->setParam('rows',$rowIds);

        /* @var $batchModel Mage_Dataflow_Model_Batch */
        $batchModel = Mage::getModel('dataflow/batch')->load($batchId);

        if (!$batchModel->getId()) {
            return;
        }
        if (!is_array($rowIds) || count($rowIds) < 1) {
            return;
        }
        if (!$batchModel->getAdapter()) {
            return;
        }

        $batchImportModel = $batchModel->getBatchImportModel();
        $batchImportModel->getIdCollection();

        $adapter = Mage::getModel($batchModel->getAdapter());
        $adapter->setBatchParams($batchModel->getParams());

        $errors = array();
        $saved  = 0;
        foreach ($rowIds as $importId) {
            $batchImportModel->load($importId);
            if (!$batchImportModel->getId()) {
                $errors[] = Mage::helper('dataflow')->__('Skip undefined row.');
                continue;
            }

            try {
                $importData = $batchImportModel->getBatchData();
                $adapter->saveRow($importData);
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
                continue;
            }
            $saved ++;
        }

        if (method_exists($adapter, 'getEventPrefix')) {
            /**
             * Event for process rules relations after products import
             */
            Mage::dispatchEvent($adapter->getEventPrefix() . '_finish_before', array(
                'adapter' => $adapter
            ));

            /**
             * Clear affected ids for adapter possible reuse
             */
            $adapter->clearAffectedEntityIds();
        }

        $result = array(
            'savedRows' => $saved,
            'errors'    => $errors
        );
        echo Mage::helper('core')->jsonEncode($result);
    }


    /**
     * When the profile is finished, we run the last action. This method is heavily based on:
     * @see Mage_Adminhtml_System_Convert_ProfileController::batchFinishAction
     *
     * @param $batchId
     */
    public function batchFinish($batchId)
    {
        // for increased compatability for models that expect an ajax request.
        Mage::app()->getRequest()->setParam('id',$batchId);
        if ($batchId) {
            $batchModel = Mage::getModel('dataflow/batch')->load($batchId);
            /* @var $batchModel Mage_Dataflow_Model_Batch */

            if ($batchModel->getId()) {
                $result = array();
                try {
                    $batchModel->beforeFinish();
                } catch (Mage_Core_Exception $e) {
                    $result['error'] = $e->getMessage();
                } catch (Exception $e) {
                    $result['error'] = Mage::helper('adminhtml')->__('An error occurred while finishing process. Please refresh the cache');
                }
                $batchModel->delete();
                echo Mage::helper('core')->jsonEncode($result);
            }
        }
    }


    /**
     * Get the current memory usage
     * @return string
     */
    function getMemoryUsage()
    {
        $size = memory_get_usage();
        $unit=array('B','KB','MB','GB','TB','PB');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
    }


}