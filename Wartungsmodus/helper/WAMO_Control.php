<?php

/**
 * @project       Wartungsmodus/Wartungsmodus
 * @file          WAMO_Control.php
 * @author        Ulrich Bittner
 * @copyright     2022 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpVoidFunctionResultUsedInspection */
/** @noinspection PhpUnused */

declare(strict_types=1);

trait WAMO_Control
{
    /**
     * Determines the trigger variables automatically.
     *
     * @param string $ObjectIdents
     * @return void
     * @throws Exception
     */
    public function DetermineVariables(string $ObjectIdents): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $this->SendDebug(__FUNCTION__, 'Identifikator: ' . $ObjectIdents, 0);
        //Determine variables first
        $determinedVariables = [];
        foreach (@IPS_GetVariableList() as $variable) {
            if ($ObjectIdents == '') {
                return;
            }
            $objectIdents = str_replace(' ', '', $ObjectIdents);
            $objectIdents = explode(',', $objectIdents);
            foreach ($objectIdents as $objectIdent) {
                $object = @IPS_GetObject($variable);
                if ($object['ObjectIdent'] == $objectIdent) {
                    $name = @IPS_GetName($variable);
                    $parent = @IPS_GetParent($variable);
                    if ($parent > 1 && @IPS_ObjectExists($parent)) { //0 = main category, 1 = none
                        $parentObject = @IPS_GetObject($parent);
                        if ($parentObject['ObjectType'] == 1) { //1 = instance
                            $name = @IPS_GetName($parent);
                        }
                    }
                    $determinedVariables[] = [
                        'Use'         => true,
                        'ID'          => $variable,
                        'Designation' => $name];
                }
            }
        }

        //Get already listed variables
        $listedVariables = json_decode($this->ReadPropertyString('VariableList'), true);
        foreach ($determinedVariables as $determinedVariable) {
            $determinedVariableID = $determinedVariable['ID'];
            if ($determinedVariableID > 1 && @IPS_ObjectExists($determinedVariableID)) {
                //Check variable id with already listed variable ids
                $add = true;
                foreach ($listedVariables as $listedVariable) {
                    $listedVariableID = $listedVariable['ID'];
                    if ($listedVariableID > 1 && @IPS_ObjectExists($determinedVariableID)) {
                        if ($determinedVariableID == $listedVariableID) {
                            $add = false;
                        }
                    }
                }
                //Add new variable to already listed variables
                if ($add) {
                    $listedVariables[] = $determinedVariable;
                }
            }
        }
        //Sort variables by name
        array_multisort(array_column($listedVariables, 'Designation'), SORT_ASC, $listedVariables);
        @IPS_SetProperty($this->InstanceID, 'VariableList', json_encode(array_values($listedVariables)));
        if (@IPS_HasChanges($this->InstanceID)) {
            @IPS_ApplyChanges($this->InstanceID);
        }
        echo 'Die Variablen wurden erfolgreich hinzugefügt!';
    }

    /**
     * Toggles the maintenance mode off or on.
     *
     * @param bool $State
     * false =  off
     * true =   on
     *
     * @return bool
     * false =  an error occurred
     * true =   successful
     * @throws Exception
     */
    public function ToggleMaintenanceMode(bool $State): bool
    {
        $this->SetValue('MaintenanceMode', $State);
        $variables = json_decode($this->ReadPropertyString('VariableList'), true);
        $result = true;
        foreach ($variables as $variable) {
            if (!$variable['Use']) {
                continue;
            }
            //false = maintenance mode is off, so variable must be switched on and vice versa
            $this->SendDebug(__FUNCTION__, 'ID: ' . $variable['ID'] . ', State: ' . json_encode(!$State), 0);
            $toggle = @RequestAction($variable['ID'], !$State);
            if (!$toggle) {
                $result = false;
            }
        }
        $this->UpdateStatus();
        return $result;
    }

    /**
     * Updates the status.
     *
     * @return void
     * @throws Exception
     */
    public function UpdateStatus(): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        if (!$this->CheckForExistingVariables()) {
            return;
        }

        $variables = json_decode($this->GetMonitoredVariables(), true);

        $this->SetValue('LastUpdate', date('d.m.Y H:i:s'));

        ##### Update overview list for WebFront

        $string = '';
        if ($this->ReadPropertyBoolean('EnableMaintenanceList')) {
            $string .= "<table style='width: 100%; border-collapse: collapse;'>";
            $string .= '<tr><td><b>Status</b></td><td><b>Name</b></td><td><b>ID</b></td></tr>';
            //Sort variables by name
            array_multisort(array_column($variables, 'Name'), SORT_ASC, $variables);
            //Rebase array
            $variables = array_values($variables);
            $separator = false;
            if (!empty($variables)) {
                //Show inactive first
                if ($this->ReadPropertyBoolean('EnableInactive')) {
                    foreach ($variables as $variable) {
                        $id = $variable['ID'];
                        if ($id != 0 && IPS_ObjectExists($id)) {
                            if ($variable['ActualStatus'] == 0) {
                                $separator = true;
                                $string .= '<tr><td>' . $variable['StatusText'] . '</td><td>' . $variable['Name'] . '</td><td>' . $id . '</td></tr>';
                            }
                        }
                    }
                }
                //Active are next
                if ($this->ReadPropertyBoolean('EnableActive')) {
                    //Check if we have an active element for a spacer
                    $activeElement = false;
                    foreach ($variables as $variable) {
                        $id = $variable['ID'];
                        if ($id != 0 && IPS_ObjectExists($id)) {
                            if ($variable['ActualStatus'] == 1) {
                                $activeElement = true;
                            }
                        }
                    }
                    if ($separator && $activeElement) {
                        $string .= '<tr><td><b>&#8205;</b></td><td><b>&#8205;</b></td><td><b>&#8205;</b></td></tr>';
                    }
                    //Active elements
                    foreach ($variables as $variable) {
                        $id = $variable['ID'];
                        if ($id != 0 && IPS_ObjectExists($id)) {
                            if ($variable['ActualStatus'] == 1) {
                                $string .= '<tr><td>' . $variable['StatusText'] . '</td><td>' . $variable['Name'] . '</td><td>' . $id . '</td></tr>';
                            }
                        }
                    }
                }
            }
            $string .= '</table>';
        }
        $this->SetValue('MaintenanceList', $string);
    }

    #################### Private

    /**
     * Checks for monitored variables.
     *
     * @return bool
     * false =  There are no monitored variables
     * true =   There are monitored variables
     * @throws Exception
     */
    private function CheckForExistingVariables(): bool
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $existing = false;
        $monitoredVariables = json_decode($this->ReadPropertyString('VariableList'), true);
        foreach ($monitoredVariables as $variable) {
            if (!$variable['Use']) {
                continue;
            }
            $existing = true;
        }
        if (!$existing) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, Es werden keine Variablen überwacht!', 0);
        }
        return $existing;
    }

    /**
     * Gets the monitored variables and their status.
     *
     * @return string
     * @throws Exception
     */
    private function GetMonitoredVariables(): string
    {
        $result = [];
        $monitoredVariables = json_decode($this->ReadPropertyString('VariableList'), true);
        foreach ($monitoredVariables as $variable) {
            if (!$variable['Use']) {
                continue;
            }
            $id = $variable['ID'];
            if ($id > 1 && @IPS_ObjectExists($id)) {
                $actualStatus = 0; //0 = inactive
                $statusText = $this->ReadPropertyString('InactiveText');
                if (GetValueBoolean($id)) {
                    $actualStatus = 1; //1 = active
                    $statusText = $this->ReadPropertyString('ActiveText');
                }
                $result[] = [
                    'ID'           => $id,
                    'Name'         => $variable['Designation'],
                    'ActualStatus' => $actualStatus,
                    'StatusText'   => $statusText];
            }
        }
        return json_encode($result);
    }
}