<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';  // globale Funktionen
require_once __DIR__ . '/../libs/local.php';   // lokale Funktionen

class NUTClient extends IPSModule
{
    use NUTClientCommonLib;
    use NUTClientLocalLib;

    public static $NUTC_STATUS_OL = 0;
    public static $NUTC_STATUS_OB = 1;
    public static $NUTC_STATUS_LB = 2;
    public static $NUTC_STATUS_HB = 3;
    public static $NUTC_STATUS_RB = 4;
    public static $NUTC_STATUS_CHRG = 5;
    public static $NUTC_STATUS_DISCHRG = 6;
    public static $NUTC_STATUS_BYPASS = 7;
    public static $NUTC_STATUS_CAL = 8;
    public static $NUTC_STATUS_OFF = 9;
    public static $NUTC_STATUS_OVER = 10;
    public static $NUTC_STATUS_TRIM = 11;
    public static $NUTC_STATUS_BOOST = 12;
    public static $NUTC_STATUS_FSD = 13;
    public static $NUTC_STATUS_UNKNOWN = 14;

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyBoolean('module_disable', false);

        $this->RegisterPropertyString('hostname', '');
        $this->RegisterPropertyInteger('port', 3493);

        $this->RegisterPropertyString('user', '');
        $this->RegisterPropertyString('password', '');

        $this->RegisterPropertyString('upsname', 'ups');

        $this->RegisterPropertyInteger('update_interval', '30');

        $this->RegisterPropertyString('use_fields', '[]');

        $this->RegisterPropertyString('add_fields', '[]');
        $this->RegisterPropertyInteger('convert_script', 0);

        $this->CreateVarProfile('NUTC.sec', VARIABLETYPE_INTEGER, ' s', 0, 0, 0, 0, 'Clock');
        $this->CreateVarProfile('NUTC.Percent', VARIABLETYPE_FLOAT, ' %', 0, 0, 0, 0, '');

        $associations = [];
        $associations[] = ['Wert' => self::$NUTC_STATUS_OL,      'Name' => $this->Translate('on line'), 'Farbe' => 0x008000];
        $associations[] = ['Wert' => self::$NUTC_STATUS_OB,      'Name' => $this->Translate('on battery'), 'Farbe' => 0xFFA500];
        $associations[] = ['Wert' => self::$NUTC_STATUS_LB,      'Name' => $this->Translate('low battery'), 'Farbe' => 0xEE0000];
        $associations[] = ['Wert' => self::$NUTC_STATUS_HB,      'Name' => $this->Translate('high battery'), 'Farbe' => -1];
        $associations[] = ['Wert' => self::$NUTC_STATUS_RB,      'Name' => $this->Translate('battery needs replacement'), 'Farbe' => 0xFFFF00];
        $associations[] = ['Wert' => self::$NUTC_STATUS_CHRG,    'Name' => $this->Translate('battery is charging'), 'Farbe' => -1];
        $associations[] = ['Wert' => self::$NUTC_STATUS_DISCHRG, 'Name' => $this->Translate('battery is discharging'), 'Farbe' => -1];
        $associations[] = ['Wert' => self::$NUTC_STATUS_BYPASS,  'Name' => $this->Translate('bypass circuit activated'), 'Farbe' => -1];
        $associations[] = ['Wert' => self::$NUTC_STATUS_CAL,     'Name' => $this->Translate('UPS is calibrating'), 'Farbe' => -1];
        $associations[] = ['Wert' => self::$NUTC_STATUS_OFF,     'Name' => $this->Translate('UPS is offline'), 'Farbe' => 0xFF00FF];
        $associations[] = ['Wert' => self::$NUTC_STATUS_OVER,    'Name' => $this->Translate('UPS is overloaded'), 'Farbe' => 0xFF00FF];
        $associations[] = ['Wert' => self::$NUTC_STATUS_TRIM,    'Name' => $this->Translate('trimming incoming voltage'), 'Farbe' => -1];
        $associations[] = ['Wert' => self::$NUTC_STATUS_BOOST,   'Name' => $this->Translate('boosting incoming voltage'), 'Farbe' => -1];
        $associations[] = ['Wert' => self::$NUTC_STATUS_FSD,     'Name' => $this->Translate('forced shutdown'), 'Farbe' => 0xFFA500];
        $associations[] = ['Wert' => self::$NUTC_STATUS_UNKNOWN, 'Name' => $this->Translate('unknown state'), 'Farbe' => -1];
        $this->CreateVarProfile('NUTC.Status', VARIABLETYPE_INTEGER, '', 0, 0, 0, 1, '', $associations);

        $this->CreateVarProfile('NUTC.sec', VARIABLETYPE_INTEGER, ' s', 0, 0, 0, 0, 'Clock');
        $this->CreateVarProfile('NUTC.Frequency', VARIABLETYPE_INTEGER, ' Hz', 0, 0, 0, 0, '');

        $this->CreateVarProfile('NUTC.Temperature', VARIABLETYPE_FLOAT, ' °C', 0, 0, 0, 1, 'Temperature');
        $this->CreateVarProfile('NUTC.Voltage', VARIABLETYPE_FLOAT, ' V', 0, 0, 0, 1, '');
        $this->CreateVarProfile('NUTC.Current', VARIABLETYPE_FLOAT, ' A', 0, 0, 0, 0, '');
        $this->CreateVarProfile('NUTC.Capacity', VARIABLETYPE_FLOAT, ' Ah', 0, 0, 0, 1, '');
        $this->CreateVarProfile('NUTC.Power', VARIABLETYPE_FLOAT, ' W', 0, 0, 0, 0, '');

        $this->RegisterTimer('UpdateData', 0, 'NUTC_UpdateData(' . $this->InstanceID . ');');
        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);

        if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) {
            $this->SetUpdateInterval();
        }
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $module_disable = $this->ReadPropertyBoolean('module_disable');
        if ($module_disable) {
            $this->SetTimerInterval('UpdateData', 0);
            $this->SetStatus(IS_INACTIVE);
            return;
        }

        $hostname = $this->ReadPropertyString('hostname');
        $port = $this->ReadPropertyInteger('port');

        if ($hostname == '' || $port <= 0) {
            $this->SetStatus(IS_INACTIVE);
            return;
        }

        $upsname = $this->ReadPropertyString('upsname');
        $ups_list = $this->ExecuteList('UPS', '');
        $ups_found = false;
        foreach ($ups_list as $ups) {
            if ($ups['id'] == $upsname) {
                $ups_found = true;
            }
        }
        if ($ups_found == false) {
            $this->SetStatus(IS_UPSIDUNKNOWN);
            return false;
        }

        $vpos = 1;
        $varList = [];

        $identList = [];
        $use_fields = json_decode($this->ReadPropertyString('use_fields'), true);
        $fieldMap = $this->getFieldMap();
        foreach ($fieldMap as $map) {
            $ident = $this->GetArrayElem($map, 'ident', '');
            $use = false;
            foreach ($use_fields as $field) {
                if ($ident == $this->GetArrayElem($field, 'ident', '')) {
                    $use = (bool) $this->GetArrayElem($field, 'use', false);
                    break;
                }
            }
            if ($use) {
                $identList[] = $ident;
            }
            $ident = 'DP_' . str_replace('.', '_', $ident);
            $desc = $this->GetArrayElem($map, 'desc', '');
            $vartype = $this->GetArrayElem($map, 'type', '');
            $varprof = $this->GetArrayElem($map, 'prof', '');
            $this->SendDebug(__FUNCTION__, 'register variable: ident=' . $ident . ', vartype=' . $vartype . ', varprof=' . $varprof . ', use=' . $this->bool2str($use), 0);
            $r = $this->MaintainVariable($ident, $this->Translate($desc), $vartype, $varprof, $vpos++, $use);
            if ($r == false) {
                $this->SendDebug(__FUNCTION__, 'failed to register variable', 0);
            }
            $varList[] = $ident;

            if ($ident == 'DP_ups_status') {
                $ident .= '_info';
                $this->SendDebug(__FUNCTION__, 'additional register variable: ident=' . $ident, 0);
                $r = $this->MaintainVariable($ident, $this->Translate('Additional status'), VARIABLETYPE_STRING, '', $vpos++, $use);
                if ($r == false) {
                    $this->SendDebug(__FUNCTION__, 'failed to register variable', 0);
                }
                $varList[] = $ident;
            }
        }

        $vpos = 50;

        $add_fields = json_decode($this->ReadPropertyString('add_fields'), true);
        foreach ($add_fields as $field) {
            $this->SendDebug(__FUNCTION__, 'field=' . print_r($field, true), 0);
            $ident = $this->GetArrayElem($field, 'ident', '');
            $vartype = $this->GetArrayElem($field, 'vartype', -1);
            if ($ident == '' || $vartype == -1) {
                continue;
            }
            $desc = $ident;
            $ident = 'DP_' . str_replace('.', '_', $ident);
            $this->SendDebug(__FUNCTION__, 'register variable: ident=' . $ident . ', vartype=' . $vartype, 0);
            $r = $this->MaintainVariable($ident, $desc, $vartype, '', $vpos++, true);
            if ($r == false) {
                $this->SendDebug(__FUNCTION__, 'failed to register variable', 0);
            }
            $varList[] = $ident;
        }

        $vpos = 100;

        $this->MaintainVariable('LastUpdate', $this->Translate('Last update'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, true);

        $objList = [];
        $this->findVariables($this->InstanceID, $objList);
        foreach ($objList as $obj) {
            $ident = $obj['ObjectIdent'];
            if (!in_array($ident, $varList)) {
                $this->SendDebug(__FUNCTION__, 'unregister variable: ident=' . $ident, 0);
                $this->UnregisterVariable($ident);
            }
        }

        $refs = $this->GetReferenceList();
        foreach ($refs as $ref) {
            $this->UnregisterReference($ref);
        }
        $propertyNames = ['convert_script'];
        foreach ($propertyNames as $name) {
            $oid = $this->ReadPropertyInteger($name);
            if ($oid > 0) {
                $this->RegisterReference($oid);
            }
        }

        if (IPS_GetKernelRunlevel() == KR_READY) {
            $this->SetUpdateInterval();
        }

        $this->SetStatus(IS_ACTIVE);
    }

    protected function SetUpdateInterval()
    {
        $sec = $this->ReadPropertyInteger('update_interval');
        $msec = $sec > 0 ? $sec * 1000 : 0;
        $this->SetTimerInterval('UpdateData', $msec);
    }

    private function findVariables($objID, &$objList)
    {
        $chldIDs = IPS_GetChildrenIDs($objID);
        foreach ($chldIDs as $chldID) {
            $obj = IPS_GetObject($chldID);
            switch ($obj['ObjectType']) {
                case OBJECTTYPE_VARIABLE:
                    if (preg_match('#^DP_#', $obj['ObjectIdent'], $r)) {
                        $objList[] = $obj;
                    }
                    break;
                case OBJECTTYPE_CATEGORY:
                    $this->findVariables($chldID, $objList);
                    break;
                default:
                    break;
            }
        }
    }

    public function GetConfigurationForm()
    {
        $formElements = $this->GetFormElements();
        $formActions = $this->GetFormActions();
        $formStatus = $this->GetFormStatus();

        $form = json_encode(['elements' => $formElements, 'actions' => $formActions, 'status' => $formStatus]);
        if ($form == '') {
            $this->SendDebug(__FUNCTION__, 'json_error=' . json_last_error_msg(), 0);
            $this->SendDebug(__FUNCTION__, '=> formElements=' . print_r($formElements, true), 0);
            $this->SendDebug(__FUNCTION__, '=> formActions=' . print_r($formActions, true), 0);
            $this->SendDebug(__FUNCTION__, '=> formStatus=' . print_r($formStatus, true), 0);
        }
        return $form;
    }

    private function UpdateFields(object $use_fields)
    {
        $values = [];
        $fieldMap = $this->getFieldMap();
        foreach ($fieldMap as $map) {
            $ident = $this->GetArrayElem($map, 'ident', '');
            $desc = $this->GetArrayElem($map, 'desc', '');
            $use = false;
            foreach ($use_fields as $field) {
                if ($ident == $this->GetArrayElem($field, 'ident', '')) {
                    $use = (bool) $this->GetArrayElem($field, 'use', false);
                    break;
                }
            }
            $values[] = ['ident' => $ident, 'desc' => $this->Translate($desc), 'use' => $use];
        }
        $this->UpdateFormField('use_fields', 'values', json_encode($values));
    }

    private function GetFormElements()
    {
        $formElements = [];

        $formElements[] = [
            'type'    => 'CheckBox',
            'name'    => 'module_disable',
            'caption' => 'Instance is disabled'
        ];

        $items = [];
        $items[] = [
            'type'    => 'ValidationTextBox',
            'name'    => 'hostname',
            'caption' => 'Hostname'
        ];
        $items[] = [
            'type'    => 'NumberSpinner',
            'name'    => 'port',
            'caption' => 'Port'
        ];

        $items[] = [
            'type'    => 'Label',
        ];
        $items[] = [
            'type'    => 'ValidationTextBox',
            'name'    => 'upsname',
            'caption' => 'UPS-Identification'
        ];

        $items[] = [
            'type'    => 'Label',
        ];
        $items[] = [
            'type'    => 'Label',
            'caption' => 'Update data every X seconds'
        ];
        $items[] = [
            'type'    => 'NumberSpinner',
            'name'    => 'update_interval',
            'caption' => 'Interval',
            'suffix'  => 'Seconds'
        ];

        $formElements[] = [
            'type'      => 'ExpansionPanel',
            'caption'   => 'Basic configuration',
            'items'     => $items
        ];

        $items = [];
        $items[] = [
            'type'    => 'ValidationTextBox',
            'name'    => 'user',
            'caption' => 'Username'
        ];
        $items[] = [
            'type'    => 'PasswordTextBox',
            'name'    => 'password',
            'caption' => 'Password'
        ];
        $formElements[] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Authentification (optional)',
            'items'   => $items
        ];

        $items = [];

        $values = [];
        $fieldMap = $this->getFieldMap();
        $use_fields = json_decode($this->ReadPropertyString('use_fields'), true);
        foreach ($fieldMap as $map) {
            $ident = $this->GetArrayElem($map, 'ident', '');
            $desc = $this->GetArrayElem($map, 'desc', '');
            $use = false;
            foreach ($use_fields as $field) {
                if ($ident == $this->GetArrayElem($field, 'ident', '')) {
                    $use = (bool) $this->GetArrayElem($field, 'use', false);
                    break;
                }
            }
            $values[] = ['ident' => $ident, 'desc' => $this->Translate($desc), 'use' => $use];
        }

        $columns = [];
        $columns[] = [
            'caption' => 'Datapoint',
            'name'    => 'ident',
            'width'   => '200px',
            'save'    => true
        ];
        $columns[] = [
            'caption' => 'Description',
            'name'    => 'desc',
            'width'   => 'auto'
        ];
        $columns[] = [
            'caption' => 'Use',
            'name'    => 'use',
            'width'   => '100px',
            'edit'    => [
                'type' => 'CheckBox'
            ]
        ];

        $items[] = [
            'type'     => 'List',
            'name'     => 'use_fields',
            'caption'  => 'Predefined datapoints',
            'rowCount' => count($values),
            'add'      => false,
            'delete'   => false,
            'columns'  => $columns,
            'values'   => $values
        ];

        $columns = [];
        $columns[] = [
            'caption' => 'Datapoint',
            'name'    => 'ident',
            'add'     => '',
            'width'   => 'auto',
            'edit'    => [
                'type' => 'ValidationTextBox'
            ]
        ];
        $columns[] = [
            'caption' => 'Variable type',
            'name'    => 'vartype',
            'add'     => VARIABLETYPE_STRING,
            'width'   => '150px',
            'edit'    => [
                'type'    => 'Select',
                'options' => [
                    ['caption' => 'Boolean', 'value' => VARIABLETYPE_BOOLEAN],
                    ['caption' => 'Integer', 'value' => VARIABLETYPE_INTEGER],
                    ['caption' => 'Float', 'value' => VARIABLETYPE_FLOAT],
                    ['caption' => 'String', 'value' => VARIABLETYPE_STRING],
                ]
            ]
        ];

        $items[] = [
            'type'     => 'List',
            'name'     => 'add_fields',
            'caption'  => 'Additional datapoints',
            'rowCount' => 10,
            'add'      => true,
            'delete'   => true,
            'columns'  => $columns
        ];
        $items[] = [
            'type'    => 'SelectScript',
            'name'    => 'convert_script',
            'caption' => 'convert values'
        ];

        $formElements[] = [
            'type'    => 'ExpansionPanel',
            'items'   => $items,
            'caption' => 'Variables',
            // 'onClick' => 'NUTC_UpdateFields($id, $use_fields);'
        ];

        return $formElements;
    }

    public function ShowVars()
    {
        if ($this->GetStatus() == IS_INACTIVE) {
            $this->SendDebug(__FUNCTION__, 'instance is inactive, skip', 0);
            echo $this->translate('Instance is inactive') . PHP_EOL;
            return;
        }

        $fieldMap = $this->getFieldMap();
        $vars = $this->ExecuteList('VAR', '');

        $txt = $this->translate('predefined datapoints') . PHP_EOL;
        foreach ($fieldMap as $map) {
            $ident = $this->GetArrayElem($map, 'ident', '');
            foreach ($vars as $var) {
                if ($ident == $var['varname']) {
                    $txt .= ' - ' . $var['varname'] . ' = "' . $var['val'] . '"' . PHP_EOL;
                    break;
                }
            }
        }
        $txt .= PHP_EOL;

        $txt .= $this->translate('additional datapoints') . PHP_EOL;
        foreach ($vars as $var) {
            $predef = false;
            foreach ($fieldMap as $map) {
                $ident = $this->GetArrayElem($map, 'ident', '');
                if ($ident == $var['varname']) {
                    $predef = true;
                    break;
                }
            }
            if ($predef) {
                continue;
            }
            $txt .= ' - ' . $var['varname'] . ' = "' . $var['val'] . '"' . PHP_EOL;
        }

        echo $txt;
    }

    private function GetFormActions()
    {
        $formActions = [];

        $items = [];
        $items[] = [
            'type'    => 'Button',
            'caption' => 'Test access',
            'onClick' => 'NUTC_TestAccess($id);'
        ];
        $items[] = [
            'type'    => 'Button',
            'caption' => 'Show variables',
            'onClick' => 'NUTC_ShowVars($id);'
        ];
        $items[] = [
            'type'    => 'Button',
            'label'   => 'Description of the variables',
            'onClick' => 'echo \'https://networkupstools.org/docs/user-manual.chunked/apcs01.html#_examples\';'
        ];
        $items[] = [
            'type'    => 'Label',
        ];
        $items[] = [
            'type'    => 'Button',
            'caption' => 'Update data',
            'onClick' => 'NUTC_UpdateData($id);'
        ];

        $formActions[] = [
            'type' => 'RowLayout',
            'items'=> $items,
        ];

        return $formActions;
    }

    public function TestAccess()
    {
        if ($this->GetStatus() == IS_INACTIVE) {
            $this->SendDebug(__FUNCTION__, 'instance is inactive, skip', 0);
            echo $this->translate('Instance is inactive') . PHP_EOL;
            return;
        }

        $line = $this->ExecuteVersion();
        if ($line == false) {
            $txt = $this->translate('access failed') . PHP_EOL;
            $txt .= PHP_EOL;
        } else {
            $txt = $this->translate('access succeeded') . PHP_EOL;
            $txt .= PHP_EOL;
            $txt .= $line . PHP_EOL;
            $txt .= PHP_EOL;

            $upsname = $this->ReadPropertyString('upsname');
            $ups_list = $this->ExecuteList('UPS', '');
            $n_ups = count($ups_list);
            $ups_found = false;
            if ($n_ups > 0) {
                $txt .= $n_ups . ' ' . $this->Translate('UPS found') . PHP_EOL;
                foreach ($ups_list as $ups) {
                    $this->SendDebug(__FUNCTION__, 'ups=' . print_r($ups, true), 0);
                    $txt .= ' - ' . $ups['id'] . PHP_EOL;
                    if ($ups['id'] == $upsname) {
                        $ups_found = true;
                    }
                }
            }
            if ($ups_found == false) {
                $txt .= PHP_EOL . $this->Translate('Warning: the specified UPS ID is unknown') . PHP_EOL;
                $this->SetStatus(IS_UPSIDUNKNOWN);
            }

            $b = false;
            $vars = $this->ExecuteList('VAR', '');
            $use_fields = json_decode($this->ReadPropertyString('use_fields'), true);
            foreach ($use_fields as $field) {
                $use = (bool) $this->GetArrayElem($field, 'use', false);
                if (!$use) {
                    continue;
                }
                $ident = $this->GetArrayElem($field, 'ident', '');
                $found = false;
                foreach ($vars as $var) {
                    if ($ident == $var['varname']) {
                        $found = true;
                        break;
                    }
                }
                if ($found == false) {
                    if ($b == false) {
                        $txt .= PHP_EOL . $this->Translate('datapoints not found in data') . PHP_EOL;
                        $b = true;
                    }
                    $txt .= ' - ' . $ident . PHP_EOL;
                }
            }

            $add_fields = json_decode($this->ReadPropertyString('add_fields'), true);
            foreach ($add_fields as $field) {
                $ident = $field['ident'];
                $found = false;
                foreach ($vars as $var) {
                    if ($ident == $var['varname']) {
                        $found = true;
                        break;
                    }
                }
                if ($found == false) {
                    if ($b == false) {
                        $txt .= PHP_EOL . $this->Translate('datapoints not found in data') . PHP_EOL;
                        $b = true;
                    }
                    $txt .= ' - ' . $ident . PHP_EOL;
                }
            }
        }

        echo $txt;
    }

    public function UpdateData()
    {
        $convert_script = $this->ReadPropertyInteger('convert_script');

        $vars = $this->ExecuteList('VAR', '');
        $this->SendDebug(__FUNCTION__, 'data=' . print_r($vars, true), 0);
        if ($vars == false) {
            $this->SetStatus(IS_NOSERVICE);
            return;
        }

        $fieldMap = $this->getFieldMap();
        $this->SendDebug(__FUNCTION__, 'fieldMap="' . print_r($fieldMap, true) . '"', 0);
        $identV = [];
        foreach ($fieldMap as $map) {
            $identV[] = $this->GetArrayElem($map, 'ident', '');
        }
        $identS = implode(',', $identV);
        $this->SendDebug(__FUNCTION__, 'known idents=' . $identS, 0);

        $use_fields = json_decode($this->ReadPropertyString('use_fields'), true);
        $use_fieldsV = [];
        foreach ($use_fields as $field) {
            if ((bool) $this->GetArrayElem($field, 'use', false)) {
                $use_fieldsV[] = $this->GetArrayElem($field, 'ident', '');
            }
        }
        $use_fieldsS = implode(',', $use_fieldsV);
        $this->SendDebug(__FUNCTION__, 'use fields=' . $use_fieldsS, 0);

        foreach ($vars as $var) {
            $ident = $var['varname'];
            $value = $var['val'];

            $vartype = VARIABLETYPE_STRING;
            $varprof = '';
            foreach ($fieldMap as $map) {
                if ($ident == $this->GetArrayElem($map, 'ident', '')) {
                    $vartype = $this->GetArrayElem($map, 'type', '');
                    $varprof = $this->GetArrayElem($map, 'prof', '');
                    break;
                }
            }

            foreach ($use_fields as $field) {
                if ($ident == $this->GetArrayElem($field, 'ident', '')) {
                    $use = (bool) $this->GetArrayElem($field, 'use', false);
                    if (!$use) {
                        $this->SendDebug(__FUNCTION__, 'ignore ident "' . $ident . '", value=' . $value, 0);
                        continue;
                    }

                    if ($convert_script > 0) {
                        $vartype = $this->GetArrayElem($field, 'vartype', -1);
                        $info = [
                            'InstanceID'    => $this->InstanceID,
                            'ident'         => $ident,
                            'vartype'       => $vartype,
                            'value'         => $value,
                        ];
                        $r = IPS_RunScriptWaitEx($convert_script, $info);
                        $this->SendDebug(__FUNCTION__, 'convert: ident=' . $ident . ', orgval=' . $value . ', value=' . ($r == false ? '<nop>' : $r), 0);
                        if ($r != false) {
                            $value = $r;
                        }
                    }

                    $ident = 'DP_' . str_replace('.', '_', $ident);
                    if ($ident == 'DP_ups_status') {
                        $this->decodeStatus($value, $code, $info);
                        $this->SendDebug(__FUNCTION__, 'use ident "' . $ident . '", value=' . $value . ' => ' . $code, 0);
                        $this->SetValue($ident, $code);
                        $ident .= '_info';
                        $this->SendDebug(__FUNCTION__, 'use ident "' . $ident . '", value=' . $value . ' => ' . $info, 0);
                        $this->SetValue($ident, $info);
                    } else {
                        $this->SendDebug(__FUNCTION__, 'use ident "' . $ident . '", value=' . $value, 0);
                        switch ($vartype) {
                            case VARIABLETYPE_INTEGER:
                                $this->SetValue($ident, intval($value));
                                break;
                            case VARIABLETYPE_FLOAT:
                                $this->SetValue($ident, floatval($value));
                                break;
                            default:
                                $this->SetValue($ident, $value);
                                break;
                        }
                    }
                    break;
                }
            }
        }

        foreach ($use_fields as $field) {
            $use = (bool) $this->GetArrayElem($field, 'use', false);
            if (!$use) {
                continue;
            }
            $ident = $this->GetArrayElem($field, 'ident', '');
            $found = false;
            foreach ($vars as $var) {
                if ($ident == $var['varname']) {
                    $found = true;
                    break;
                }
            }
            if ($found == false) {
                $this->SendDebug(__FUNCTION__, 'configured ident "' . $ident . '" not found in receviced data', 0);
            }
        }

        $add_fields = json_decode($this->ReadPropertyString('add_fields'), true);
        foreach ($vars as $var) {
            $ident = $var['varname'];
            $value = $var['val'];
            foreach ($add_fields as $field) {
                if ($field['ident'] != $ident) {
                    continue;
                }

                if ($convert_script > 0) {
                    $vartype = $this->GetArrayElem($field, 'vartype', -1);
                    $info = [
                        'InstanceID'    => $this->InstanceID,
                        'ident'         => $ident,
                        'vartype'       => $vartype,
                        'value'         => $value,
                    ];
                    $r = IPS_RunScriptWaitEx($convert_script, $info);
                    $this->SendDebug(__FUNCTION__, 'convert: ident=' . $ident . ', orgval=' . $value . ', value=' . ($r == false ? '<nop>' : $r), 0);
                    if ($r != false) {
                        $value = $r;
                    }
                }

                switch ($vartype) {
                    case VARIABLETYPE_BOOLEAN:
                        $this->SetValue($ident, boolval($value));
                        break;
                    case VARIABLETYPE_INTEGER:
                        $this->SetValue($ident, intval($value));
                        break;
                    case VARIABLETYPE_FLOAT:
                        $this->SetValue($ident, floatval($value));
                        break;
                    default:
                        $this->SetValue($ident, $value);
                        break;
                }

                $ident = 'DP_' . str_replace('.', '_', $ident);
                $this->SendDebug(__FUNCTION__, 'use ident "' . $ident . '", value=' . $value, 0);
                $this->SetValue($ident, $value);
            }
        }

        $this->SetValue('LastUpdate', time());

        $model = '';
        $serial = '';
        foreach ($vars as $var) {
            if ($var['varname'] == 'ups.model') {
                $model = $var['val'];
            }
            if ($var['varname'] == 'ups.serial') {
                $serial = $var['val'];
            }
        }

        $info = $model . ' (#' . $serial . ')';
        $this->SetSummary($info);
        $this->SetStatus(IS_ACTIVE);
    }

    private function doCommunication($fp, $cmd, $args, &$lines)
    {
        $query = $cmd;
        if ($args != '') {
            $query .= ' ' . $args;
        }
        $query .= "\n";
        $this->SendDebug(__FUNCTION__, 'query=' . $query, 0);
        if (fwrite($fp, $query) == false) {
            $this->SendDebug(__FUNCTION__, 'fwrite() failed', 0);
            fclose($fp);
            return false;
        }

        $finished = false;
        $timed_out = false;
        $lines = [];
        while (!feof($fp)) {
            $line = fgets($fp, 1024);
            $info = stream_get_meta_data($fp);
            if ($info['timed_out']) {
                $timed_out = true;
                break;
            }
            $line = str_replace("\n", '', $line);

            switch ($cmd) {
            case 'LIST':
                $lines[] = $line;
                if (preg_match('/^END LIST/', $line)) {
                    $finished = true;
                }
                break;
            case 'GET':
            case 'SET':
            case 'INSTCMD':
            case 'LOGIN':
            case 'LOGOUT':
            case 'USERNAME':
            case 'PASSWORD':
            case 'HELP':
            case 'VER':
                $lines[] = $line;
                $finished = true;
                break;
            default:
                $lines[] = $line;
            }
            if ($finished) {
                break;
            }
        }
        $info = stream_get_meta_data($fp);
        if ($info['timed_out']) {
            $timed_out = true;
        }

        return $timed_out;
    }

    private function performQuery(string $cmd, string $args)
    {
        $hostname = $this->ReadPropertyString('hostname');
        $port = $this->ReadPropertyInteger('port');

        $fp = @fsockopen($hostname, $port, $errno, $errstr, 5);
        if (!$fp) {
            $this->SendDebug(__FUNCTION__, 'fsockopen(' . $hostname . ',' . $port . ') failed: error=' . $errstr . '(' . $errno . ')', 0);
            $use_fields = json_decode($this->ReadPropertyString('use_fields'), true);
            foreach ($use_fields as $field) {
                if ($this->GetArrayElem($field, 'ident', '') == 'DP_ups_status') {
                    $this->SetValue('DP_ups_status', self::$NUTC_STATUS_UNKNOWN);
                    $this->SetValue('DP_ups_status_info', $this->Translate('unable to connect NUT-server'));
                }
            }
            $this->SetValue('LastUpdate', time());
            return false;
        }
        stream_set_timeout($fp, 2);

        $user = $this->ReadPropertyString('user');
        $password = $this->ReadPropertyString('password');
        if ($user != '' && $password != '') {
            $this->SendDebug(__FUNCTION__, 'user=' . $user . ', password=' . $password, 0);

            $timed_out = $this->doCommunication($fp, 'USERNAME', $user, $lines);
            if ($timed_out) {
                $this->SendDebug(__FUNCTION__, 'socket: timeout', 0);
                fclose($fp);
                return false;
            }
            $err = $this->extractError($lines);
            if ($err != false) {
                $this->SendDebug(__FUNCTION__, 'got error ' . $err, 0);
                fclose($fp);
                return false;
            }
            $timed_out = $this->doCommunication($fp, 'PASSWORD', $password, $lines);
            if ($timed_out) {
                $this->SendDebug(__FUNCTION__, 'socket: timeout', 0);
                fclose($fp);
                return false;
            }
            $err = $this->extractError($lines);
            if ($err != false) {
                $this->SendDebug(__FUNCTION__, 'got error ' . $err, 0);
                fclose($fp);
                return false;
            }
        }

        $lines = [];
        $timed_out = $this->doCommunication($fp, $cmd, $args, $lines);

        fclose($fp);
        if ($timed_out) {
            $this->SendDebug(__FUNCTION__, 'socket: timeout', 0);
            return false;
        }
        if ($lines == []) {
            $this->SendDebug(__FUNCTION__, 'got no lines', 0);
            return false;
        }
        $this->SendDebug(__FUNCTION__, 'received ' . count($lines) . ' lines', 0);
        return $lines;
    }

    private function extractError($lines)
    {
        if (count($lines) > 0 && preg_match('/^ERR (.*)$/', $lines[0], $r)) {
            return $r[1];
        }
        return false;
    }

    private function checkOK($lines)
    {
        if (count($lines) > 0 && $lines[0] == 'OK') {
            return true;
        }
        return false;
    }

    public function ExecuteList(string $subcmd, string $varname)
    {
        $this->SendDebug(__FUNCTION__, 'subcmd=' . $subcmd . ', varname=' . $varname, 0);
        switch ($subcmd) {
            case 'VAR':
            case 'CMD':
            case 'RW':
            case 'CLIENT':
                $upsname = $this->ReadPropertyString('upsname');
                if ($upsname == '') {
                    $this->SendDebug(__FUNCTION__, 'missing name for subcmd ' . $subcmd, 0);
                    return false;
                }
                $query = $subcmd . ' ' . $upsname;
                break;
            case 'ENUM':
            case 'RANGE':
                $upsname = $this->ReadPropertyString('upsname');
                if ($upsname == '') {
                    $this->SendDebug(__FUNCTION__, 'missing name for subcmd ' . $subcmd, 0);
                    return false;
                }
                $query = $subcmd . ' ' . $upsname . ' ' . $varname;
                break;
            default:
                $query = $subcmd;
                break;
        }

        $lines = $this->performQuery('LIST', $query);
        if ($lines == '') {
            return false;
        }
        $arr = [];
        foreach ($lines as $line) {
            if (preg_match('/^BEGIN /', $line)) {
                continue;
            }
            if (preg_match('/^END /', $line)) {
                continue;
            }
            switch ($subcmd) {
                case 'UPS':
                    if (preg_match('/^[^ ]* ([^ ]*) (.*)$/', $line, $r)) {
                        $arr[] = ['id' => $r[1], 'desc' => $r[2]];
                    }
                    break;
                case 'VAR':
                case 'RW':
                    if (preg_match('/^[^ ]* ([^ ]*) ([^ ]*) "([^"]*)"$/', $line, $r)) {
                        $arr[] = ['varname' => $r[2], 'val' => rtrim($r[3])];
                    }
                    break;
                case 'CMD':
                    if (preg_match('/^[^ ]* ([^ ]*) (.*)$/', $line, $r)) {
                        $arr[] = ['cmd' => $r[2]];
                    }
                    break;
                case 'CLIENT':
                    if (preg_match('/^[^ ]* ([^ ]*) (.*)$/', $line, $r)) {
                        $arr[] = ['id' => $r[1], 'ip' => $r[2]];
                    }
                    break;
                case 'RANGE':
                    if (preg_match('/^[^ ]* ([^ ]*) ([^ ]*) "([^"]*)" "([^"]*)"$/', $line, $r)) {
                        $arr[] = ['varname' => $r[2], 'min' => rtrim($r[3]), 'max' => rtrim($r[4])];
                    }
                    break;
                case 'ENUM':
                    if (preg_match('/^[^ ]* ([^ ]*) ([^ ]*) "([^"]*)"$/', $line, $r)) {
                        $arr[] = ['varname' => $r[2], 'val' => rtrim($r[3])];
                    }
                    break;
                default:
                    break;
            }
        }
        return $arr;
    }

    public function ExecuteGet(string $subcmd, string $varname)
    {
        $this->SendDebug(__FUNCTION__, 'subcmd=' . $subcmd . ', varname=' . $varname, 0);
        switch ($subcmd) {
            case 'UPSDESC':
            case 'NUMLOGINS':
                $upsname = $this->ReadPropertyString('upsname');
                if ($upsname == '') {
                    $this->SendDebug(__FUNCTION__, 'missing name for subcmd ' . $subcmd, 0);
                    return false;
                }
                $query = $subcmd . ' ' . $upsname;
                break;
            case 'VAR':
            case 'TYPE':
            case 'DESC':
            case 'CMDDESC':
                $upsname = $this->ReadPropertyString('upsname');
                if ($upsname == '') {
                    $this->SendDebug(__FUNCTION__, 'missing name for subcmd ' . $subcmd, 0);
                    return false;
                }
                $query = $subcmd . ' ' . $upsname . ' ' . $varname;
                break;
            default:
                $query = $subcmd . ' ' . $varname;
                break;
        }

        $lines = $this->performQuery('GET', $query);
        if ($lines == false || count($lines) == 0) {
            return false;
        }
        $line = $lines[0];
        $elem = [];
        switch ($subcmd) {
            case 'VAR':
                if (preg_match('/^[^ ]* ([^ ]*) ([^ ]*) "([^"]*)"$/', $line, $r)) {
                    $elem = ['varname' => $r[2], 'val' => rtrim($r[3])];
                }
                break;
            case 'TYPE':
                if (preg_match('/^[^ ]* ([^ ]*) ([^ ]*) (.*)$/', $line, $r)) {
                    $elem = ['varname' => $r[2], 'type' => $r[3]];
                }
                break;
            case 'DESC':
                if (preg_match('/^[^ ]* ([^ ]*) ([^ ]*) "([^"]*)"$/', $line, $r)) {
                    $elem = ['varname' => $r[2], 'desc' => rtrim($r[3])];
                }
                break;
            case 'UPSDESC':
                if (preg_match('/^[^ ]* ([^ ]*) "([^"]*)"$/', $line, $r)) {
                    $elem = ['id' => $r[1], 'desc' => rtrim($r[2])];
                }
                break;
            case 'CMDDESC':
                if (preg_match('/^[^ ]* ([^ ]*) ([^ ]*) "([^"]*)"$/', $line, $r)) {
                    $elem = ['cmd' => $r[2], 'desc' => rtrim($r[3])];
                }
                break;
            case 'NUMLOGINS':
                if (preg_match('/^[^ ]* ([^ ]*) (.*)$/', $line, $r)) {
                    $elem = ['id' => $r[1], 'num' => $r[2]];
                }
                break;
            default:
                break;
        }
        return $elem;
    }

    public function ExecuteSet(string $varname, string $value)
    {
        $this->SendDebug(__FUNCTION__, 'varname=' . $varname . ', value=' . $value, 0);

        $upsname = $this->ReadPropertyString('upsname');
        if ($upsname == '') {
            $this->SendDebug(__FUNCTION__, 'missing name', 0);
            return false;
        }
        $lines = $this->performQuery('SET', 'VAR ' . $upsname . ' ' . $varname . ' "' . $value . '"');
        $err = $this->extractError($lines);
        if ($err != false) {
            $this->SendDebug(__FUNCTION__, 'got error ' . $err, 0);
            return false;
        }
        return true;
    }

    public function ExecuteCmd(string $cmdname)
    {
        $this->SendDebug(__FUNCTION__, 'cmdname=' . $cmdname, 0);

        $upsname = $this->ReadPropertyString('upsname');
        if ($upsname == '') {
            $this->SendDebug(__FUNCTION__, 'missing name', 0);
            return false;
        }
        $lines = $this->performQuery('INSTCMD', $upsname . ' ' . $cmdname);
        $err = $this->extractError($lines);
        if ($err != false) {
            $this->SendDebug(__FUNCTION__, 'got error ' . $err, 0);
            return false;
        }
        return true;
    }

    public function ExecuteHelp()
    {
        $this->SendDebug(__FUNCTION__, '', 0);
        $lines = $this->performQuery('HELP', '');
        if ($lines == false || count($lines) == 0) {
            return false;
        }
        return $lines[0];
    }

    public function ExecuteVersion()
    {
        $this->SendDebug(__FUNCTION__, '', 0);
        $lines = $this->performQuery('VER', '');
        if ($lines == false || count($lines) == 0) {
            return false;
        }
        return $lines[0];
    }

    public function ExecuteLogin()
    {
        $this->SendDebug(__FUNCTION__, '', 0);

        $upsname = $this->ReadPropertyString('upsname');
        if ($upsname == '') {
            $this->SendDebug(__FUNCTION__, 'missing name', 0);
            return false;
        }
        $lines = $this->performQuery('LOGIN', $upsname);
        $err = $this->extractError($lines);
        if ($err != false) {
            $this->SendDebug(__FUNCTION__, 'got error ' . $err, 0);
            return false;
        }
        return true;
    }

    public function ExecuteLogout()
    {
        $this->SendDebug(__FUNCTION__, '', 0);

        $lines = $this->performQuery('LOGOUT', '');
        $err = $this->extractError($lines);
        if ($err != false) {
            $this->SendDebug(__FUNCTION__, 'got error ' . $err, 0);
            return false;
        }
        return true;
    }

    private function decodeStatus($tags, &$code, &$info)
    {
        $maps = [
            [
                'tag'   => 'OL',
                'code'  => self::$NUTC_STATUS_OL,
                'info'  => $this->Translate('on line')
            ],
            [
                'tag'   => 'OB',
                'code'  => self::$NUTC_STATUS_OB,
                'info'  => $this->Translate('on battery')
            ],
            [
                'tag'   => 'LB',
                'code'  => self::$NUTC_STATUS_LB,
                'info'  => $this->Translate('low battery')
            ],
            [
                'tag'   => 'HB',
                'code'  => self::$NUTC_STATUS_HB,
                'info'  => $this->Translate('high battery')
            ],
            [
                'tag'   => 'RB',
                'code'  => self::$NUTC_STATUS_RB,
                'info'  => $this->Translate('battery needs replacement')
            ],
            [
                'tag'   => 'CHRG',
                'code'  => self::$NUTC_STATUS_CHRG,
                'info'  => $this->Translate('battery is charging')
            ],
            [
                'tag'   => 'DISCHRG',
                'code'  => self::$NUTC_STATUS_DISCHRG,
                'info'  => $this->Translate('battery is discharging')
            ],
            [
                'tag'   => 'BYPASS',
                'code'  => self::$NUTC_STATUS_BYPASS,
                'info'  => $this->Translate('bypass circuit activated')
            ],
            [
                'tag'   => 'CAL',
                'code'  => self::$NUTC_STATUS_CAL,
                'info'  => $this->Translate('UPS is calibrating')
            ],
            [
                'tag'   => 'OFF',
                'code'  => self::$NUTC_STATUS_OFF,
                'info'  => $this->Translate('UPS is offline')
            ],
            [
                'tag'   => 'OVER',
                'code'  => self::$NUTC_STATUS_OVER,
                'info'  => $this->Translate('UPS is overloaded')
            ],
            [
                'tag'   => 'TRIM',
                'code'  => self::$NUTC_STATUS_TRIM,
                'info'  => $this->Translate('trimming incoming voltage')
            ],
            [
                'tag'   => 'BOOST',
                'code'  => self::$NUTC_STATUS_BOOST,
                'info'  => $this->Translate('boosting incoming voltage')
            ],
            [
                'tag'   => 'FSD',
                'code'  => self::$NUTC_STATUS_FSD,
                'info'  => $this->Translate('forced shutdown')
            ],
        ];

        $code = self::$NUTC_STATUS_OFF;
        $info = '';

        $tagV = explode(' ', $tags);
        $infoV = [];
        foreach ($maps as $map) {
            if (in_array($map['tag'], $tagV)) {
                $code = $map['code'];
                break;
            }
        }
        foreach ($maps as $map) {
            if ($map['code'] == $code) {
                continue;
            }
            if (in_array($map['tag'], $tagV)) {
                $infoV[] = $map['info'];
            }
        }
        foreach ($tagV as $tag) {
            $found = false;
            foreach ($maps as $map) {
                if ($map['tag'] == $tag) {
                    $found = true;
                    break;
                }
            }
            if ($found == false) {
                $infoV[] = $tag;
            }
        }
        $info = implode(', ', $infoV);

        $this->SendDebug(__FUNCTION__, 'tags=' . $tags . ' => code=' . $code . ', info=' . $info, 0);
    }

    private function getFieldMap()
    {
        $map = [
            [
                'ident'  => 'ups.status',
                'desc'   => 'Status',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'NUTC.Status',
            ],
            [
                'ident'  => 'ups.alarm',
                'desc'   => 'Alarm',
                'type'   => VARIABLETYPE_STRING,
            ],
            [
                'ident'  => 'ups.mfr',
                'desc'   => 'Manufacturer',
                'type'   => VARIABLETYPE_STRING,
            ],
            [
                'ident'  => 'ups.model',
                'desc'   => 'Model',
                'type'   => VARIABLETYPE_STRING,
            ],
            [
                'ident'  => 'ups.serial',
                'desc'   => 'Serialnumber',
                'type'   => VARIABLETYPE_STRING,
            ],
            [
                'ident'  => 'ups.load',
                'desc'   => 'Load',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'NUTC.Percent',
            ],
            [
                'ident'  => 'ups.realpower.nominal',
                'desc'   => 'Nominal value of real power',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'NUTC.Power',
            ],

            [
                'ident'  => 'battery.charge',
                'desc'   => 'Battery charge',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'NUTC.Percent',
            ],
            [
                'ident'  => 'battery.voltage',
                'desc'   => 'Battery voltage',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'NUTC.Voltage',
            ],
            [
                'ident'  => 'battery.current',
                'desc'   => 'Battery current',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'NUTC.Current',
            ],
            [
                'ident'  => 'battery.capacity',
                'desc'   => 'Battery capacity',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'NUTC.Capacity',
            ],
            [
                'ident'  => 'battery.temperature',
                'desc'   => 'Battery temperature',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'NUTC.Temperature',
            ],
            [
                'ident'  => 'battery.runtime',
                'desc'   => 'Battery runtime',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'NUTC.sec',
            ],

            [
                'ident'  => 'input.voltage',
                'desc'   => 'Input voltage',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'NUTC.Voltage',
            ],
            [
                'ident'  => 'input.current',
                'desc'   => 'Input current',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'NUTC.Current',
            ],
            [
                'ident'  => 'input.realpower',
                'desc'   => 'Input real power',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'NUTC.Power',
            ],
            [
                'ident'  => 'input.frequency',
                'desc'   => 'Input frequency',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'NUTC.Frequency',
            ],

            [
                'ident'  => 'output.voltage',
                'desc'   => 'Output voltage',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'NUTC.Voltage',
            ],
            [
                'ident'  => 'output.current',
                'desc'   => 'Output current',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'NUTC.Current',
            ],
            [
                'ident'  => 'output.frequency',
                'desc'   => 'Output frequency',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'NUTC.Frequency',
            ],
        ];

        return $map;
    }
}
