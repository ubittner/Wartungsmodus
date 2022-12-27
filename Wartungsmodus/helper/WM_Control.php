<?php

/**
 * @project       Wartungsmodus/Wartungsmodus
 * @file          WM_Control.php
 * @author        Ulrich Bittner
 * @copyright     2022 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpVoidFunctionResultUsedInspection */
/** @noinspection PhpUnused */

declare(strict_types=1);

trait WM_Control
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
            $toggle = @RequestAction($variable['ID'], !$State);
            if (!$toggle) {
                $result = false;
            }
        }
        return $result;
    }
}