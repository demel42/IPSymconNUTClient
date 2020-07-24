<?php

declare(strict_types=1);

trait NUTClientLocalLib
{
    public static $IS_NOSERVICE = IS_EBASE + 1;
    public static $IS_UPSIDMISSING = IS_EBASE + 2;
    public static $IS_UPSIDUNKNOWN = IS_EBASE + 3;

    private function GetFormStatus()
    {
        $formStatus = [];

        $formStatus[] = ['code' => IS_CREATING, 'icon' => 'inactive', 'caption' => 'Instance getting created'];
        $formStatus[] = ['code' => IS_ACTIVE, 'icon' => 'active', 'caption' => 'Instance is active'];
        $formStatus[] = ['code' => IS_DELETING, 'icon' => 'inactive', 'caption' => 'Instance is deleted'];
        $formStatus[] = ['code' => IS_INACTIVE, 'icon' => 'inactive', 'caption' => 'Instance is inactive'];
        $formStatus[] = ['code' => IS_NOTCREATED, 'icon' => 'inactive', 'caption' => 'Instance is not created'];

        $formStatus[] = ['code' => self::$IS_NOSERVICE, 'icon' => 'error', 'caption' => 'Instance is inactive (no service)'];
        $formStatus[] = ['code' => self::$IS_UPSIDMISSING, 'icon' => 'error', 'caption' => 'Instance is inactive (ups-id missing)'];
        $formStatus[] = ['code' => self::$IS_UPSIDUNKNOWN, 'icon' => 'error', 'caption' => 'Instance is inactive (ups-id unknown)'];

        return $formStatus;
    }
}
