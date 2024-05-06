<?php

namespace App\Classes\Service;

class BaseService
{
    private ?\DateTimeZone $currentTimeZone = null;

    public function formatToTimezone($dateTime, $format = 'd-M-y h:i A')
    {
        if (!($dateTime instanceof \DateTime)) {
            return $dateTime;
        }
        if (is_null($this->currentTimeZone)) {
            $timezone = 'Asia/Dubai';
            $this->currentTimeZone = new \DateTimeZone($timezone);
        }
        $dateTime->setTimezone($this->currentTimeZone);

        return $dateTime->format($format);
    }
}
