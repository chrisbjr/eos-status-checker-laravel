<?php

use PHPHtmlParser\Dom;

class HomeController extends BaseController
{
    private $unixTimestamp = 0;
    private $statusArray = [
        'na' => true,
        'eu' => true
    ];

    private $offlineStrings = [
        'offline',
        'unavailable'
    ];

    private $onlineStrings = [
        'online',
        'available'
    ];

    public function status()
    {



    }

    public function parseDate($string)
    {
        $parenPos = strpos($string, '(');
        if ($parenPos !== false) {
            $string = trim(substr($string, 0, $parenPos));
            $parse  = explode(' - ', $string);
            $date   = trim($parse[0]);
            $time   = trim($parse[1]);

            $date = str_replace('.', '-', $date);

            $fullDate = $date . ' ' . $time;

            $this->unixTimestamp = strtotime($fullDate);

            return true;
        }

        return false;
    }

    private function parseStatus($m)
    {
        $string = strtolower($m);

        $northAmericanPos = strpos($string, 'north');
        if ($northAmericanPos !== false) {
            // we got north american status

            $northAmericanStrings = explode(' ', $m);

            // Check for offline status
            $intersect = array_intersect($this->offlineStrings, $northAmericanStrings);
            if (count($intersect) > 0) {
                // offline!
                $this->statusArray['na'] = false;
            } else {
                // online?
                $this->statusArray['na'] = true;
            }
        }
        $europePos = strpos($string, 'europe');
        if ($europePos !== false) {
            // we got north american status

            $europeStrings = explode(' ', $m);

            $intersect = array_intersect($this->offlineStrings, $europeStrings);
            if (count($intersect) > 0) {
                // offline!
                $this->statusArray['eu'] = false;
            } else {
                $this->statusArray['eu'] = true;
            }
        }
    }

}
