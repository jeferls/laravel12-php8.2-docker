<?php

namespace Domain\Shared\Helpers;

class RequestID
{
    public static function getID(): string
    {
        if (! self::$ID) {
            self::setID(self::generateID());
        }

        return self::$ID;
    }

    public static function setID(string $ID): void
    {
        self::$ID = $ID;
    }

    public static function generateID(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);
        $id = str_split(bin2hex($bytes), 4);

        return "{$id[0]}{$id[1]}-{$id[2]}-{$id[3]}-{$id[4]}-{$id[5]}{$id[6]}{$id[7]}";
    }

    private static ?string $ID = null;
}
