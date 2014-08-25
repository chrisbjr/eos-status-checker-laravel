<?php

use Illuminate\Console\Command;
use PHPHtmlParser\Dom;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class EosStatusCheck extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'EosStatusCheck';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks and stores the availability of Elder Scrolls Online (EOS) servers';

    private $unixTimestamp = 0;
    private $statusArray = [
        'na'  => true,
        'eu'  => true,
        'pts' => true,
    ];

    private $offlineStrings = [
        'offline',
        'unavailable'
    ];

    private $onlineStrings = [
        'online',
        'available'
    ];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $this->info("Checking the status of Elder Scrolls Online servers..");

        $dom = new Dom;
        $dom->loadFromUrl('https://help.elderscrollsonline.com/app/answers/detail2/a_id/4320');
        $contents = $dom->getElementsByClass("answer-box");

        if (empty($contents[0])) {
            $this->error("Couldn't retrieve EOS status page contents.");
            return;
        }

        $messages = $contents[0]->find("p");

        $isFirstDateFound = false;
        foreach ($messages as $m) {
            $currentUnixTimestamp = $this->unixTimestamp;
            if ($this->parseDate($m)) {
                if ($isFirstDateFound) {
                    $this->unixTimestamp = $currentUnixTimestamp;
                    $this->info("Done processing entry");
                    break;
                }
                $isFirstDateFound = true;
            }
            if ($isFirstDateFound) {
                // parse for status
                $this->parseStatus($m);
            }
        }

        // Query the db....?
        $status = Status::getLatest();

        if (empty($status) || $this->unixTimestamp > strtotime($status->created_at)) {

            if (empty($status) || $status->north_america != $this->statusArray['na'] || $status->europe != $this->statusArray['eu']) {
                // then its time to save...
                $newStatus                = new Status;
                $newStatus->north_america = ($this->statusArray['na']) ? 1 : 0;
                $newStatus->europe        = ($this->statusArray['eu']) ? 1 : 0;
                $newStatus->pts           = ($this->statusArray['pts']) ? 1 : 0;
                $newStatus->created_at    = date('Y-m-d H:i:s', $this->unixTimestamp);
                $newStatus->save();
            }


        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array();
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array();
    }

    private function parseDate($string)
    {
        $string = str_replace('<p>', '', $string);
        $string = str_replace('</p>', '', $string);

        $parenPos = strpos($string, '(');
        if ($parenPos !== false) {
            $string = trim(substr($string, 0, $parenPos));
            $parse  = explode(' - ', $string);
            $date   = trim($parse[0]);
            $time   = trim($parse[1]);

            $date = str_replace('.', '-', $date);

            $fullDate = $date . ' ' . $time;

            $this->info("Date found: " . $fullDate);

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
                $this->info("North American server is OFFLINE");
            } else {
                // online?
                $this->statusArray['na'] = true;
                $this->info("North American server is ONLINE");
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
                $this->info("Europe server is OFFLINE");
            } else {
                $this->statusArray['eu'] = true;
                $this->info("Europe server is ONLINE");
            }
        }
        $ptsPos = strpos($string, 'pts');
        if ($ptsPos !== false) {
            // we got north american status

            $ptsStrings = explode(' ', $m);

            $intersect = array_intersect($this->offlineStrings, $ptsStrings);
            if (count($intersect) > 0) {
                // offline!
                $this->statusArray['pts'] = false;
                $this->info("Public test server is OFFLINE");
            } else {
                $this->statusArray['pts'] = true;
                $this->info("Public test server is ONLINE");
            }
        }
    }

}
