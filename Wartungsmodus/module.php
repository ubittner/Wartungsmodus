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

include_once __DIR__ . '/helper/WM_autoload.php';

class Wartungsmodus extends IPSModule
{
    //Helper
    use WM_Config;
    use WM_Control;

    //Constants
    private const MODULE_NAME = 'Wartungsmodus';
    private const MODULE_PREFIX = 'WM';
    private const MODULE_VERSION = '1.0-1, 28.10.2022';

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        ########## Properties

        //Functions
        $this->RegisterPropertyString('Note', '');
        $this->RegisterPropertyBoolean('EnableMaintenanceMode', false);
        //Trigger list
        $this->RegisterPropertyString('VariableList', '[]');

        ########## Variables

        //Active
        $id = @$this->GetIDForIdent('MaintenanceMode');
        $this->RegisterVariableBoolean('MaintenanceMode', 'Wartungsmodus', '~Switch', 10);
        $this->EnableAction('MaintenanceMode');
        if (!$id) {
            $this->SetValue('MaintenanceMode', false);
            IPS_SetIcon(@$this->GetIDForIdent('MaintenanceMode'), 'Gear');
        }
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
            }
        }

        //WebFront options
        IPS_SetHidden($this->GetIDForIdent('MaintenanceMode'), !$this->ReadPropertyBoolean('EnableMaintenanceMode'));

        $this->ToggleMaintenanceMode($this->GetValue('MaintenanceMode'));
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        $this->SendDebug(__FUNCTION__, $TimeStamp . ', SenderID: ' . $SenderID . ', Message: ' . $Message . ', Data: ' . print_r($Data, true), 0);
        if (!empty($Data)) {
            foreach ($Data as $key => $value) {
                $this->SendDebug(__FUNCTION__, 'Data[' . $key . '] = ' . json_encode($value), 0);
            }
        }
        if ($Message == IPS_KERNELSTARTED) {
            $this->KernelReady();
        }
    }

    #################### Request Action

    public function RequestAction($Ident, $Value)
    {
        if ($Ident == 'MaintenanceMode') {
            $this->ToggleMaintenanceMode($Value);
        }
    }

    #################### Private

    private function KernelReady(): void
    {
        $this->ApplyChanges();
    }
}