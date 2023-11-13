<?php 

require_once('ICal/Event.php');
require_once('ICal/ICal.php');

class CacheableIcal {

    private const CACHE_FILE_SUFFIX = ".icalCache";
    private const MAX_CACHE_SECONDS = 60;

    private string $url;
    private string $cacheSubfolder;
    private ICal $ical;
    
     function __construct(string $url, string $cacheSubfolder = __DIR__."/cache") {
        $this->url = $url;
        $this->cacheSubfolder = $cacheSubfolder;
        $this->ical = $this->createIcal();
    }

    public function getIcal() {
        return $this->ical;
    }

    private function createIcal(): ICal {
        $currentFilePath = $this->getCurrentFilePath();
        try {
            $ical = new ICal($currentFilePath, array(
            //$ical = new ICal('Arbeit.ics', array(
                'defaultSpan'                 => 2,     // Default value
                'defaultTimeZone'             => 'Europe/Berlin',//'UTC',
                'defaultWeekStart'            => 'MO',  // Default value
                'disableCharacterReplacement' => false, // Default value
                'filterDaysAfter'             => null,  // Default value
                'filterDaysBefore'            => null,  // Default value
                'httpUserAgent'               => null,  // Default value
                'skipRecurrence'              => false, // Default value
            ));
            // $ical->initFile('ICal.ics');
            // $ical->initUrl('https://raw.githubusercontent.com/u01jmg3/ics-parser/master/examples/ICal.ics', $username = null, $password = null, $userAgent = null);
            //$ical->initUrl('https://airbnb.de/calendar/ical/48209756?s=', $username = null, $password = null, $userAgent = 'outlook.de');
            return $ical;
        } catch (\Exception $e) {
            die($e);
        }
    }

    private function getCurrentFilePath(): string {
        return $this->getOrCreateCurrentFile();
    }

    private function getOrCreateCurrentFile(): string {
        $this->prepareCacheSubfolder();
        $foundCache = $this->checkCache();
        if ($foundCache !== null) {
            return $foundCache;
        }
        $filename = $this->createNewFileName();
        $result = $this->curlGet($this->url);
        $myfile = fopen($filename, "w") or die("Unable to open file: " . $filename);
        fwrite($myfile, $result);
        return $filename;
    }

    private function prepareCacheSubfolder() {
        $dirname = $this->cacheSubfolder;
        if (!is_dir($dirname)) {
           mkdir($dirname, 0755, true);
        }
    }

    // sucht nach einer passenden Datei im Cache und leert diesen
    private function checkCache(): ?string {
        $files = array_diff(scandir($this->cacheSubfolder), array('.', '..'));
        $foundCache = null;
        if (sizeof($files) > 0) {
            sort($files, SORT_NUMERIC);
            $lastFile = array_pop($files);
            $lastFileTime = str_replace(self::CACHE_FILE_SUFFIX, '', $lastFile);
            $lastFileTime = str_replace(md5($this->url) . "-", '', $lastFileTime);
            if ((time() - $lastFileTime) < self::MAX_CACHE_SECONDS) {
                $foundCache = $this->cacheSubfolder . "/" . $lastFile;
            } else {
                array_push($files, $lastFile); 
            }
        }
        $this->deleteFiles($files);
        return $foundCache;
    }

    private function deleteFiles($files) {
        foreach ($files as $file) {
            unlink($this->cacheSubfolder . "/" . $file);
        }
    }

    private function createNewFileName(): string {
        return $this->cacheSubfolder . "/" . md5($this->url) . "-" . time() . self::CACHE_FILE_SUFFIX;
    }

    private function curlGet($url): string {
        $cURLConnection = curl_init();

        curl_setopt($cURLConnection, CURLOPT_URL, $url);
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);

        $text = curl_exec($cURLConnection);
        curl_close($cURLConnection);
        return $text;
    }
}
?>