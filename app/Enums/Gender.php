<?php

namespace App\Enums;

enum Gender: string
{
    case Male = 'male';
    case Female = 'female';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'זכר',
            self::Female => 'נקבה',
        };
    }

    public function defaultSubtitle(): string
    {
        return match ($this) {
            self::Male => 'לזכרו של יקירנו',
            self::Female => 'לזכרה של יקירתנו',
        };
    }

    /** Feed / section heading: "זיכרונך חי בכל מה שאנחנו עושים" */
    public function memoriesHeading(): string
    {
        return 'זיכרונך חי בכל מה שאנחנו עושים';
    }
}
