<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';  // globale Funktionen

class NUTClient extends IPSModule
{
    use NUTClientCommon;

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyBoolean('module_disable', false);

        $this->RegisterPropertyString('hostname', '');
        $this->RegisterPropertyInteger('port', 3493);

        $this->RegisterPropertyString('user', '');
        $this->RegisterPropertyString('password', '');

        $this->RegisterPropertyString('upsname', 'ups');
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

        if (IPS_GetKernelRunlevel() == KR_READY) {
            // $this->SetTimerInterval('UpdateData', 1000);
        }

        $this->SetStatus(IS_ACTIVE);
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

    protected function GetFormElements()
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
        $formElements[] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Basic configuration',
            'items'   => $items
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

        return $formElements;
    }

    protected function GetFormActions()
    {
        $formActions = [];

        $formActions[] = [
            'type'    => 'Button',
            'caption' => 'Test access',
            'onClick' => 'NUTC_TestAccess($id);'
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

        $cdata = '';
        $msg = '';
        $line = $this->executeVersion();

        $txt = '';
        if ($line == false) {
            $txt .= $this->translate('access failed') . PHP_EOL;
            $txt .= PHP_EOL;
            if ($msg != '') {
                $txt .= $this->translate('message') . ': ' . $msg . PHP_EOL;
            }
        } else {
            $txt = $this->translate('access succeeded') . PHP_EOL;
            $txt .= PHP_EOL;
            $txt .= $line . PHP_EOL;
            $txt .= PHP_EOL;

            $a_ups = $this->executeList('UPS', '');
            $n_ups = count($a_ups);
            if ($n_ups > 0) {
                $txt .= $n_ups . ' ' . $this->Translate('UPS found') . PHP_EOL;
                foreach ($a_ups as $ups) {
                    $this->SendDebug(__FUNCTION__, 'ups=' . print_r($ups, true), 0);
                    $txt .= ' - ' . $ups['id'] . PHP_EOL;
                }
            }
        }

        echo $txt;
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

        $fp = fsockopen($hostname, $port, $errno, $errstr, 5);
        if (!$fp) {
            $this->SendDebug(__FUNCTION__, 'fsockopen(' . $hostname . ',' . $port . ') failed: error=' . $errstr . '(' . $errno . ')', 0);
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
        foreach ($lines as $no => $line) {
            $this->SendDebug(__FUNCTION__, 'line ' . $no . ': ' . $line, 0);
        }
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

    public function executeList(string $subcmd, string $varname)
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
            $this->SendDebug(__FUNCTION__, 'line=' . $line, 0);
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
                        $arr[] = ['var' => $r[2], 'val' => rtrim($r[3])];
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
                        $arr[] = ['var' => $r[2], 'min' => rtrim($r[3]), 'max' => rtrim($r[4])];
                    }
                    break;
                case 'ENUM':
                    if (preg_match('/^[^ ]* ([^ ]*) ([^ ]*) "([^"]*)"$/', $line, $r)) {
                        $arr[] = ['var' => $r[2], 'val' => rtrim($r[3])];
                    }
                    break;
                default:
                    break;
            }
        }
        return $arr;
    }

    public function executeGet(string $subcmd, string $varname)
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
                    $elem = ['var' => $r[2], 'val' => rtrim($r[3])];
                }
                break;
            case 'TYPE':
                if (preg_match('/^[^ ]* ([^ ]*) ([^ ]*) (.*)$/', $line, $r)) {
                    $elem = ['var' => $r[2], 'type' => $r[3]];
                }
                break;
            case 'DESC':
                if (preg_match('/^[^ ]* ([^ ]*) ([^ ]*) "([^"]*)"$/', $line, $r)) {
                    $elem = ['var' => $r[2], 'desc' => rtrim($r[3])];
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

    public function executeSet(string $varname, string $value)
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

    public function executeCmd(string $cmdname)
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

    public function executeHelp()
    {
        $this->SendDebug(__FUNCTION__, '', 0);
        $lines = $this->performQuery('HELP', '');
        if ($lines == false || count($lines) == 0) {
            return false;
        }
        return $lines[0];
    }

    public function executeVersion()
    {
        $this->SendDebug(__FUNCTION__, '', 0);
        $lines = $this->performQuery('VER', '');
        if ($lines == false || count($lines) == 0) {
            return false;
        }
        return $lines[0];
    }

    public function executeLogin()
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

    public function executeLogout()
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
}
