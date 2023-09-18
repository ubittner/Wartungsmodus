<?php

/**
 * @project       Wartungsmodus/Wartungsmodus
 * @file          WAMO_Config.php
 * @author        Ulrich Bittner
 * @copyright     2022 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpUnused */

declare(strict_types=1);

trait WAMO_Config
{
    /**
     * Reloads the configuration form.
     *
     * @return void
     */
    public function ReloadConfig(): void
    {
        $this->ReloadForm();
    }

    /**
     * Expands or collapses the expansion panels.
     *
     * @param bool $State
     * false =  collapse,
     * true =   expand
     *
     * @return void
     */
    public function ExpandExpansionPanels(bool $State): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->UpdateFormField('Panel' . $i, 'expanded', $State);
        }
    }

    /**
     * Modifies a configuration button.
     *
     * @param string $Field
     * @param string $Caption
     * @param int $ObjectID
     * @return void
     */
    public function ModifyButton(string $Field, string $Caption, int $ObjectID): void
    {
        $state = false;
        if ($ObjectID > 1 && @IPS_ObjectExists($ObjectID)) { //0 = main category, 1 = none
            $state = true;
        }
        $this->UpdateFormField($Field, 'caption', $Caption);
        $this->UpdateFormField($Field, 'visible', $state);
        $this->UpdateFormField($Field, 'objectID', $ObjectID);
    }

    /**
     * Gets the configuration form.
     *
     * @return false|string
     * @throws Exception
     */
    public function GetConfigurationForm()
    {
        $form = [];

        ########## Elements

        //Configuration buttons
        $form['elements'][0] =
            [
                'type'  => 'RowLayout',
                'items' => [
                    [
                        'type'    => 'Button',
                        'caption' => 'Konfiguration ausklappen',
                        'onClick' => self::MODULE_PREFIX . '_ExpandExpansionPanels($id, true);'
                    ],
                    [
                        'type'    => 'Button',
                        'caption' => 'Konfiguration einklappen',
                        'onClick' => self::MODULE_PREFIX . '_ExpandExpansionPanels($id, false);'
                    ],
                    [
                        'type'    => 'Button',
                        'caption' => 'Konfiguration neu laden',
                        'onClick' => self::MODULE_PREFIX . '_ReloadConfig($id);'
                    ]
                ]
            ];

        //Info
        $library = IPS_GetLibrary(self::LIBRARY_GUID);
        $module = IPS_GetModule(self::MODULE_GUID);
        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel1',
            'caption' => 'Info',
            'items'   => [
                [
                    'type'    => 'Label',
                    'name'    => 'ModuleID',
                    'caption' => "ID:\t\t\t" . $this->InstanceID
                ],
                [
                    'type'    => 'Label',
                    'caption' => "Modul:\t\t" . $module['ModuleName']
                ],
                [
                    'type'    => 'Label',
                    'caption' => "Präfix:\t\t" . $module['Prefix']
                ],
                [
                    'type'    => 'Label',
                    'caption' => "Version:\t\t" . $library['Version'] . '-' . $library['Build'] . ', ' . date('d.m.Y', $library['Date'])
                ],
                [
                    'type'    => 'Label',
                    'caption' => "Entwickler:\t" . $library['Author']
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'Note',
                    'caption' => 'Notiz',
                    'width'   => '600px'
                ]
            ]
        ];

        //Variables
        $variableValues = [];
        $variables = json_decode($this->ReadPropertyString('VariableList'), true);
        foreach ($variables as $variable) {
            $stateName = 'fehlerhaft';
            $rowColor = '#FFC0C0'; //red
            $id = $variable['ID'];
            if ($id > 1 && @IPS_ObjectExists($id)) { //0 = main category, 1 = none
                $stateName = 'Aktiv';
                $rowColor = '#C0FFC0'; //light green
                $value = GetValueBoolean($id);
                if (!$value) {
                    $stateName = 'Inaktiv';
                    $rowColor = '#FFC0C0'; //red
                }
                $this->SendDebug(__FUNCTION__, 'ID: ' . $id . ', Value: ' . json_encode($value) . ', Status: ' . $stateName, 0);
            }
            if (!$variable['Use']) {
                $stateName = 'Deaktiviert';
                $rowColor = '#DFDFDF'; //grey
            }
            $variableValues[] = ['ActualState' => $stateName, 'VariableID' => $id, 'rowColor' => $rowColor];
        }

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel2',
            'caption' => 'Variablen',
            'items'   => [
                [
                    'type'     => 'List',
                    'name'     => 'VariableList',
                    'rowCount' => 15,
                    'add'      => true,
                    'delete'   => true,
                    'columns'  => [
                        [
                            'name'    => 'Use',
                            'caption' => 'Aktiviert',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'ActualState',
                            'caption' => 'Aktueller Status',
                            'width'   => '150px',
                            'add'     => ''
                        ],
                        [
                            'caption' => 'ID',
                            'name'    => 'VariableID',
                            'width'   => '100px',
                            'add'     => '',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "VariableListConfigurationButton", "ID " . $VariableList["ID"] . " bearbeiten", $VariableList["ID"]);',
                        ],
                        [
                            'name'    => 'Designation',
                            'caption' => 'Bezeichnung',
                            'width'   => '300px',
                            'add'     => '',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "VariableListConfigurationButton", "ID " . $VariableList["ID"] . " bearbeiten", $VariableList["ID"]);',
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Variable',
                            'name'    => 'ID',
                            'width'   => '400px',
                            'add'     => 0,
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "VariableListConfigurationButton", "ID " . $VariableList["ID"] . " bearbeiten", $VariableList["ID"]);',
                            'edit'    => [
                                'type'     => 'SelectVariable'
                            ]
                        ]
                    ],
                    'values' => $variableValues
                ],
                [
                    'type'     => 'OpenObjectButton',
                    'name'     => 'VariableListConfigurationButton',
                    'caption'  => 'Bearbeiten',
                    'visible'  => false,
                    'objectID' => 0
                ]
            ]
        ];

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel3',
            'caption' => 'Wartungsliste',
            'items'   => [
                [
                    'type'    => 'Label',
                    'caption' => 'Anzeigeoption Inaktiv',
                    'bold'    => true
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableInactive',
                    'caption' => 'Inaktiv'
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'InactiveText',
                    'caption' => 'Bezeichnung'
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Anzeigeoption Aktiv',
                    'bold'    => true
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableActive',
                    'caption' => 'Aktiv anzeigen'
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'ActiveText',
                    'caption' => 'Bezeichnung'
                ]
            ]
        ];

        //Automatic status update
        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel4',
            'caption' => 'Aktualisierung',
            'items'   => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'AutomaticStatusUpdate',
                    'caption' => 'Automatische Aktualisierung'
                ],
                [
                    'type'    => 'NumberSpinner',
                    'name'    => 'StatusUpdateInterval',
                    'caption' => 'Intervall',
                    'suffix'  => 'Sekunden'
                ]
            ]
        ];

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel5',
            'caption' => 'Visualisierung',
            'items'   => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableMaintenanceMode',
                    'caption' => 'Wartungsmodus'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableLastUpdate',
                    'caption' => 'Letzte Aktualisierung'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableUpdateStatus',
                    'caption' => 'Aktualisierung'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableMaintenanceList',
                    'caption' => 'Wartungsliste'
                ]
            ]
        ];

        ########## Actions

        //Variables
        $form['actions'][] =
            [
                'type'    => 'Label',
                'caption' => 'Variablen'
            ];

        $form['actions'][] =
            [
                'type'  => 'RowLayout',
                'items' => [
                    [
                        'type'    => 'ValidationTextBox',
                        'name'    => 'ObjectIdents',
                        'caption' => 'Identifikator',
                        'value'   => 'Active'
                    ],
                    [
                        'type'    => 'Label',
                        'caption' => ' '
                    ],
                    [
                        'type'    => 'PopupButton',
                        'caption' => 'Variablen ermitteln',
                        'popup'   => [
                            'caption' => 'Variablen wirklich automatisch ermitteln und hinzufügen?',
                            'items'   => [
                                [
                                    'type'    => 'Button',
                                    'caption' => 'Ermitteln',
                                    'onClick' => self::MODULE_PREFIX . '_DetermineVariables($id, $ObjectIdents);'
                                ],
                                [
                                    'type'    => 'ProgressBar',
                                    'name'    => 'DetermineVariableProgress',
                                    'caption' => 'Fortschritt',
                                    'minimum' => 0,
                                    'maximum' => 100,
                                    'visible' => false
                                ],
                                [
                                    'type'    => 'Label',
                                    'name'    => 'DetermineVariableProgressInfo',
                                    'caption' => '',
                                    'visible' => false
                                ]
                            ]
                        ]
                    ]
                ]
            ];

        $form['actions'][] =
            [
                'type'    => 'Label',
                'caption' => ' '
            ];

        //Test center
        $form['actions'][] =
            [
                'type' => 'TestCenter'
            ];

        $form['actions'][] =
            [
                'type'    => 'Label',
                'caption' => ' '
            ];

        //Registered references
        $registeredReferences = [];
        $references = $this->GetReferenceList();
        foreach ($references as $reference) {
            $name = 'Objekt #' . $reference . ' existiert nicht';
            $rowColor = '#FFC0C0'; //red
            if (@IPS_ObjectExists($reference)) {
                $name = IPS_GetName($reference);
                $rowColor = '#C0FFC0'; //light green
            }
            $registeredReferences[] = [
                'ObjectID' => $reference,
                'Name'     => $name,
                'rowColor' => $rowColor];
        }

        //Registered messages
        $registeredMessages = [];
        $messages = $this->GetMessageList();
        foreach ($messages as $id => $messageID) {
            $name = 'Objekt #' . $id . ' existiert nicht';
            $rowColor = '#FFC0C0'; //red
            if (@IPS_ObjectExists($id)) {
                $name = IPS_GetName($id);
                $rowColor = '#C0FFC0'; //light green
            }
            switch ($messageID) {
                case [10001]:
                    $messageDescription = 'IPS_KERNELSTARTED';
                    break;

                case [10603]:
                    $messageDescription = 'VM_UPDATE';
                    break;

                default:
                    $messageDescription = 'keine Bezeichnung';
            }
            $registeredMessages[] = [
                'ObjectID'           => $id,
                'Name'               => $name,
                'MessageID'          => $messageID,
                'MessageDescription' => $messageDescription,
                'rowColor'           => $rowColor];
        }

        //Developer area
        $form['actions'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Entwicklerbereich',
            'items'   => [
                [
                    'type'     => 'List',
                    'name'     => 'RegisteredReferences',
                    'caption'  => 'Registrierte Referenzen',
                    'rowCount' => 10,
                    'sort'     => [
                        'column'    => 'ObjectID',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'caption' => 'ID',
                            'name'    => 'ObjectID',
                            'width'   => '150px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredReferencesConfigurationButton", "ID " . $RegisteredReferences["ObjectID"] . " aufrufen", $RegisteredReferences["ObjectID"]);'
                        ],
                        [
                            'caption' => 'Name',
                            'name'    => 'Name',
                            'width'   => '300px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredReferencesConfigurationButton", "ID " . $RegisteredReferences["ObjectID"] . " aufrufen", $RegisteredReferences["ObjectID"]);'
                        ]
                    ],
                    'values' => $registeredReferences
                ],
                [
                    'type'     => 'OpenObjectButton',
                    'name'     => 'RegisteredReferencesConfigurationButton',
                    'caption'  => 'Aufrufen',
                    'visible'  => false,
                    'objectID' => 0
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'     => 'List',
                    'name'     => 'RegisteredMessages',
                    'caption'  => 'Registrierte Nachrichten',
                    'rowCount' => 10,
                    'sort'     => [
                        'column'    => 'ObjectID',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'caption' => 'ID',
                            'name'    => 'ObjectID',
                            'width'   => '150px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredMessagesConfigurationButton", "ID " . $RegisteredMessages["ObjectID"] . " aufrufen", $RegisteredMessages["ObjectID"]);'
                        ],
                        [
                            'caption' => 'Name',
                            'name'    => 'Name',
                            'width'   => '300px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredMessagesConfigurationButton", "ID " . $RegisteredMessages["ObjectID"] . " aufrufen", $RegisteredMessages["ObjectID"]);'
                        ],
                        [
                            'caption' => 'Nachrichten ID',
                            'name'    => 'MessageID',
                            'width'   => '150px'
                        ],
                        [
                            'caption' => 'Nachrichten Bezeichnung',
                            'name'    => 'MessageDescription',
                            'width'   => '250px'
                        ]
                    ],
                    'values' => $registeredMessages
                ],
                [
                    'type'     => 'OpenObjectButton',
                    'name'     => 'RegisteredMessagesConfigurationButton',
                    'caption'  => 'Aufrufen',
                    'visible'  => false,
                    'objectID' => 0
                ]
            ]
        ];

        //Dummy info message
        $form['actions'][] =
            [
                'type'    => 'PopupAlert',
                'name'    => 'InfoMessage',
                'visible' => false,
                'popup'   => [
                    'closeCaption' => 'OK',
                    'items'        => [
                        [
                            'type'    => 'Label',
                            'name'    => 'InfoMessageLabel',
                            'caption' => '',
                            'visible' => true
                        ]
                    ]
                ]
            ];

        ########## Status

        $form['status'][] = [
            'code'    => 101,
            'icon'    => 'active',
            'caption' => $module['ModuleName'] . ' wird erstellt',
        ];
        $form['status'][] = [
            'code'    => 102,
            'icon'    => 'active',
            'caption' => $module['ModuleName'] . ' ist aktiv',
        ];
        $form['status'][] = [
            'code'    => 103,
            'icon'    => 'active',
            'caption' => $module['ModuleName'] . ' wird gelöscht',
        ];
        $form['status'][] = [
            'code'    => 104,
            'icon'    => 'inactive',
            'caption' => $module['ModuleName'] . ' ist inaktiv',
        ];
        $form['status'][] = [
            'code'    => 200,
            'icon'    => 'inactive',
            'caption' => 'Es ist Fehler aufgetreten, weitere Informationen unter Meldungen, im Log oder Debug!',
        ];

        return json_encode($form);
    }
}