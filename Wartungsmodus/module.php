<?php

/**
 * @project       Wartungsmodus/Wartungsmodus
 * @file          module.php
 * @author        Ulrich Bittner
 * @copyright     2022 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpUnused */

declare(strict_types=1);

include_once __DIR__ . '/helper/WAMO_autoload.php';

class Wartungsmodus extends IPSModule
{
    //Helper
    use WAMO_Config;
    use WAMO_Control;

    //Constants
    private const MODULE_NAME = 'Wartungsmodus';
    private const MODULE_PREFIX = 'WAMO';
    private const MODULE_VERSION = '1.0-3, 01.02.2023';

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        ########## Properties

        //Functions
        $this->RegisterPropertyString('Note', '');
        $this->RegisterPropertyBoolean('EnableMaintenanceMode', false);
        $this->RegisterPropertyBoolean('EnableLastUpdate', true);
        $this->RegisterPropertyBoolean('EnableUpdateStatus', true);
        $this->RegisterPropertyBoolean('EnableMaintenanceList', true);
        $this->RegisterPropertyBoolean('EnableInactive', true);
        $this->RegisterPropertyString('InactiveText', '🔴 Inaktiv');
        $this->RegisterPropertyBoolean('EnableActive', true);
        $this->RegisterPropertyString('ActiveText', '🟢 Aktiv');
        //Trigger list
        $this->RegisterPropertyString('VariableList', '[]');
        //Update
        $this->RegisterPropertyBoolean('AutomaticStatusUpdate', false);
        $this->RegisterPropertyInteger('StatusUpdateInterval', 60);

        ########## Variables

        //Active
        $id = @$this->GetIDForIdent('MaintenanceMode');
        $this->RegisterVariableBoolean('MaintenanceMode', 'Wartungsmodus', '~Switch', 10);
        $this->EnableAction('MaintenanceMode');
        if (!$id) {
            $this->SetValue('MaintenanceMode', false);
            IPS_SetIcon(@$this->GetIDForIdent('MaintenanceMode'), 'Gear');
        }

        //Last update
        $id = @$this->GetIDForIdent('LastUpdate');
        $this->RegisterVariableString('LastUpdate', 'Letzte Aktualisierung', '', 20);
        if (!$id) {
            IPS_SetIcon($this->GetIDForIdent('LastUpdate'), 'Clock');
        }

        //Update status
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.UpdateStatus';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileAssociation($profile, 0, 'Aktualisieren', 'Repeat', -1);
        $this->RegisterVariableInteger('UpdateStatus', 'Aktualisierung', $profile, 30);
        $this->EnableAction('UpdateStatus');

        //Maintenance list
        $id = @$this->GetIDForIdent('MaintenanceList');
        $this->RegisterVariableString('MaintenanceList', 'Wartungsliste', 'HTMLBox', 40);
        if (!$id) {
            IPS_SetIcon($this->GetIDForIdent('MaintenanceList'), 'Database');
        }

        ########## Timer

        //Status update
        $this->RegisterTimer('StatusUpdate', 0, self::MODULE_PREFIX . '_UpdateStatus(' . $this->InstanceID . ');');
    }

    public function ApplyChanges()
    {
        //Wait until IP-Symcon is started
        $this->RegisterMessage(0, IPS_KERNELSTARTED);

        //Never delete this line!
        parent::ApplyChanges();

        //Check runlevel
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }

        //Delete all references
        foreach ($this->GetReferenceList() as $referenceID) {
            $this->UnregisterReference($referenceID);
        }

        //Delete all update messages
        foreach ($this->GetMessageList() as $senderID => $messages) {
            foreach ($messages as $message) {
                if ($message == VM_UPDATE) {
                    $this->UnregisterMessage($senderID, VM_UPDATE);
                }
            }
        }

        //Register references and update messages
        $variables = json_decode($this->ReadPropertyString('VariableList'), true);
        foreach ($variables as $variable) {
            if (!$variable['Use']) {
                continue;
            }
            $id = $variable['ID'];
            if ($id > 1 && @IPS_ObjectExists($id)) { //0 = main category, 1 = none
                $this->RegisterReference($id);
                $this->RegisterMessage($id, VM_UPDATE);
            }
        }

        //WebFront options
        IPS_SetHidden($this->GetIDForIdent('MaintenanceMode'), !$this->ReadPropertyBoolean('EnableMaintenanceMode'));
        IPS_SetHidden($this->GetIDForIdent('LastUpdate'), !$this->ReadPropertyBoolean('EnableLastUpdate'));
        IPS_SetHidden($this->GetIDForIdent('UpdateStatus'), !$this->ReadPropertyBoolean('EnableUpdateStatus'));
        IPS_SetHidden($this->GetIDForIdent('MaintenanceList'), !$this->ReadPropertyBoolean('EnableMaintenanceList'));

        $this->ToggleMaintenanceMode($this->GetValue('MaintenanceMode'));

        //Set automatic status update timer
        $milliseconds = 0;
        if ($this->ReadPropertyBoolean('AutomaticStatusUpdate')) {
            $milliseconds = $this->ReadPropertyInteger('StatusUpdateInterval') * 1000;
        }
        $this->SetTimerInterval('StatusUpdate', $milliseconds);

        //Update status
        $this->UpdateStatus();
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();

        //Delete profiles
        $profiles = ['UpdateStatus'];
        foreach ($profiles as $profile) {
            $profileName = self::MODULE_PREFIX . '.' . $this->InstanceID . '.' . $profile;
            if (@IPS_VariableProfileExists($profileName)) {
                IPS_DeleteVariableProfile($profileName);
            }
        }
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data): void
    {
        $this->SendDebug(__FUNCTION__, $TimeStamp . ', SenderID: ' . $SenderID . ', Message: ' . $Message . ', Data: ' . print_r($Data, true), 0);
        switch ($Message) {
            case IPS_KERNELSTARTED:
                $this->KernelReady();
                break;

            case VM_UPDATE:
                //$Data[0] = actual value
                //$Data[1] = value changed
                //$Data[2] = last value
                //$Data[3] = timestamp actual value
                //$Data[4] = timestamp value changed
                //$Data[5] = timestamp last value
                $this->UpdateStatus();
                break;

        }
    }

    #################### Request Action

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'MaintenanceMode':
                $this->ToggleMaintenanceMode($Value);
                break;

            case 'UpdateStatus':
                $this->UpdateStatus();
                break;

        }
    }

    #################### Private

    private function KernelReady(): void
    {
        $this->ApplyChanges();
    }
}