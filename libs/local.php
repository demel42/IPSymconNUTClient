<?php

declare(strict_types=1);

trait NUTClientLocalLib
{
    public static $IS_NOSERVICE = IS_EBASE + 10;
    public static $IS_UPSIDMISSING = IS_EBASE + 11;
    public static $IS_UPSIDUNKNOWN = IS_EBASE + 12;

    private function GetFormStatus()
    {
        $formStatus = $this->GetCommonFormStatus();

        $formStatus[] = ['code' => self::$IS_NOSERVICE, 'icon' => 'error', 'caption' => 'Instance is inactive (no service)'];
        $formStatus[] = ['code' => self::$IS_UPSIDMISSING, 'icon' => 'error', 'caption' => 'Instance is inactive (ups-id missing)'];
        $formStatus[] = ['code' => self::$IS_UPSIDUNKNOWN, 'icon' => 'error', 'caption' => 'Instance is inactive (ups-id unknown)'];

        return $formStatus;
    }

    public static $STATUS_INVALID = 0;
    public static $STATUS_VALID = 1;
    public static $STATUS_RETRYABLE = 2;

    private function CheckStatus()
    {
        switch ($this->GetStatus()) {
            case IS_ACTIVE:
                $class = self::$STATUS_VALID;
                break;
            default:
                $class = self::$STATUS_INVALID;
                break;
        }

        return $class;
    }

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

    public function InstallVarProfiles(bool $reInstall = false)
    {
        if ($reInstall) {
            $this->SendDebug(__FUNCTION__, 'reInstall=' . $this->bool2str($reInstall), 0);
        }

        $this->CreateVarProfile('NUTC.sec', VARIABLETYPE_INTEGER, ' s', 0, 0, 0, 0, 'Clock', [], $reInstall);
        $this->CreateVarProfile('NUTC.Percent', VARIABLETYPE_FLOAT, ' %', 0, 0, 0, 0, '', [], $reInstall);

        $associations = [
            ['Wert' => self::$NUTC_STATUS_OL,      'Name' => $this->Translate('on line'), 'Farbe' => 0x008000],
            ['Wert' => self::$NUTC_STATUS_OB,      'Name' => $this->Translate('on battery'), 'Farbe' => 0xFFA500],
            ['Wert' => self::$NUTC_STATUS_LB,      'Name' => $this->Translate('low battery'), 'Farbe' => 0xEE0000],
            ['Wert' => self::$NUTC_STATUS_HB,      'Name' => $this->Translate('high battery'), 'Farbe' => -1],
            ['Wert' => self::$NUTC_STATUS_RB,      'Name' => $this->Translate('battery needs replacement'), 'Farbe' => 0xFFFF00],
            ['Wert' => self::$NUTC_STATUS_CHRG,    'Name' => $this->Translate('battery is charging'), 'Farbe' => -1],
            ['Wert' => self::$NUTC_STATUS_DISCHRG, 'Name' => $this->Translate('battery is discharging'), 'Farbe' => -1],
            ['Wert' => self::$NUTC_STATUS_BYPASS,  'Name' => $this->Translate('bypass circuit activated'), 'Farbe' => -1],
            ['Wert' => self::$NUTC_STATUS_CAL,     'Name' => $this->Translate('is calibrating'), 'Farbe' => -1],
            ['Wert' => self::$NUTC_STATUS_OFF,     'Name' => $this->Translate('offline'), 'Farbe' => 0xFF00FF],
            ['Wert' => self::$NUTC_STATUS_OVER,    'Name' => $this->Translate('overloaded'), 'Farbe' => 0xFF00FF],
            ['Wert' => self::$NUTC_STATUS_TRIM,    'Name' => $this->Translate('trimming incoming voltage'), 'Farbe' => -1],
            ['Wert' => self::$NUTC_STATUS_BOOST,   'Name' => $this->Translate('boosting incoming voltage'), 'Farbe' => -1],
            ['Wert' => self::$NUTC_STATUS_FSD,     'Name' => $this->Translate('forced shutdown'), 'Farbe' => 0xFFA500],
            ['Wert' => self::$NUTC_STATUS_UNKNOWN, 'Name' => $this->Translate('unknown state'), 'Farbe' => -1],
        ];
        $this->CreateVarProfile('NUTC.Status', VARIABLETYPE_INTEGER, '', 0, 0, 0, 1, '', $associations, $reInstall);

        $this->CreateVarProfile('NUTC.sec', VARIABLETYPE_INTEGER, ' s', 0, 0, 0, 0, 'Clock', [], $reInstall);
        $this->CreateVarProfile('NUTC.Frequency', VARIABLETYPE_INTEGER, ' Hz', 0, 0, 0, 0, '', [], $reInstall);

        $this->CreateVarProfile('NUTC.Temperature', VARIABLETYPE_FLOAT, ' °C', 0, 0, 0, 1, 'Temperature', [], $reInstall);
        $this->CreateVarProfile('NUTC.Voltage', VARIABLETYPE_FLOAT, ' V', 0, 0, 0, 1, '', [], $reInstall);
        $this->CreateVarProfile('NUTC.Current', VARIABLETYPE_FLOAT, ' A', 0, 0, 0, 0, '', [], $reInstall);
        $this->CreateVarProfile('NUTC.Capacity', VARIABLETYPE_FLOAT, ' Ah', 0, 0, 0, 1, '', [], $reInstall);
        $this->CreateVarProfile('NUTC.Power', VARIABLETYPE_FLOAT, ' W', 0, 0, 0, 0, '', [], $reInstall);
    }
}
