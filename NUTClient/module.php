<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';  // globale Funktionen

class NUTClient extends IPSModule
{
    use NUTClientCommon;

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyString('host', 'homeserver.damsky.home');
        $this->RegisterPropertyInteger('port', 3493);

        $this->RegisterPropertyString('user', '');
        $this->RegisterPropertyString('password', '');

        $this->RegisterPropertyString('name', 'ups');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $status = IS_ACTIVE;

        $this->SetStatus($status);
    }

    public function Query(string $cmd, string $args)
    {
        $this->SendDebug(__FUNCTION__, 'cmd=' . $cmd . ', args=' . $args, 0);

        $host = $this->ReadPropertyString('host');
        $port = $this->ReadPropertyInteger('port');

        $fp = fsockopen($host, $port, $errno, $errstr, 5);
        if (!$fp) {
            $this->SendDebug(__FUNCTION__, 'fsockopen(' . $host . ',' . $port . ') failed: error=' . $errstr . '(' . $errno . ')', 0);
            return false;
        }
        stream_set_timeout($fp, 2);

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
                $timed_out = $true;
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
            $timed_out = $true;
        }
        fclose($fp);
        if ($timed_out) {
            $this->SendDebug(__FUNCTION__, 'socket: timeout', 0);
            return false;
        }
        if ($lines == []) {
            $this->SendDebug(__FUNCTION__, 'got no lines', 0);
            return false;
        }

        $this->SendDebug(__FUNCTION__, 'got ' . count($lines) . ' lines', 0);
        foreach ($lines as $no => $line) {
            $this->SendDebug(__FUNCTION__, 'line ' . $no . ': ' . $line, 0);
        }
        return $lines;
    }

    public function executeList(string $subcmd, string $varname)
    {
        $this->SendDebug(__FUNCTION__, 'subcmd=' . $subcmd . ', varname=' . $varname, 0);
        switch ($subcmd) {
            case 'VAR':
            case 'CMD':
            case 'RW':
            case 'CLIENT':
                $name = $this->ReadPropertyString('name');
                if ($name == '') {
                    $this->SendDebug(__FUNCTION__, 'missing name for subcmd ' . $subcmd, 0);
                    return false;
                }
                $query = $subcmd . ' ' . $name;
                break;
            case 'ENUM':
            case 'RANGE':
                $name = $this->ReadPropertyString('name');
                if ($name == '') {
                    $this->SendDebug(__FUNCTION__, 'missing name for subcmd ' . $subcmd, 0);
                    return false;
                }
                $query = $subcmd . ' ' . $name . ' ' . $varname;
                break;
            default:
                $query = $subcmd;
                break;
        }

        $this->Query('LIST', $query);
        return true;
    }

    public function executeGet(string $subcmd, string $varname)
    {
        $this->SendDebug(__FUNCTION__, 'subcmd=' . $subcmd . ', varname=' . $varname, 0);
        switch ($subcmd) {
            case 'UPSDESC':
            case 'NUMLOGINS':
                $name = $this->ReadPropertyString('name');
                if ($name == '') {
                    $this->SendDebug(__FUNCTION__, 'missing name for subcmd ' . $subcmd, 0);
                    return false;
                }
                $query = $subcmd . ' ' . $name;
                break;
            case 'VAR':
            case 'TYPE':
            case 'DESC':
            case 'CMDDESC':
                $name = $this->ReadPropertyString('name');
                if ($name == '') {
                    $this->SendDebug(__FUNCTION__, 'missing name for subcmd ' . $subcmd, 0);
                    return false;
                }
                $query = $subcmd . ' ' . $name . ' ' . $varname;
                break;
            default:
                $query = $subcmd . ' ' . $varname;
                break;
        }

        $this->Query('GET', $query);
        return true;
    }

    public function executeSet(string $varname, string $value)
    {
        $this->SendDebug(__FUNCTION__, 'varname=' . $varname . ', value=' . $value, 0);
        $name = $this->ReadPropertyString('name');
        if ($name == '') {
            $this->SendDebug(__FUNCTION__, 'missing name', 0);
            return false;
        }
        $query = 'VAR ' . $name . ' ' . $varname;

        $this->Query('SET', $query);
        return true;
    }

    public function executeCmd(string $cmdname)
    {
        $this->SendDebug(__FUNCTION__, 'cmdname=' . $cmdname, 0);

        // ^ERR
        $this->Query('USERNAME', 'admin');
        $this->Query('PASSWORD', 'MrProper34');

        $name = $this->ReadPropertyString('name');
        if ($name == '') {
            $this->SendDebug(__FUNCTION__, 'missing name', 0);
            return false;
        }
        $query = 'INSTCMD ' . $name . ' ' . $cmdname;

        $this->Query('INSTCMD', $query);
        return true;
    }

    public function executeHelp()
    {
        $this->SendDebug(__FUNCTION__, '', 0);
        $this->Query('HELP', '');
    }

    public function executeVersion()
    {
        $this->SendDebug(__FUNCTION__, '', 0);
        $this->Query('VER', '');
    }
}
