<?php
/*
json: stdClass Object
(
    [results] => Array(
        [0] => stdClass Object(
            [geometry] => stdClass Object(
                [location] => stdClass Object(
                    [lat] => 38.8976633
                    [lng] => -77.0365739
                )
            )
        )
    )
)
*/

class GenerateKML {

    private static $_debug = 1;
    private static $_test = 0;
    private $apiKey;

    /**
     * run
     */
    public function run() {
        // load api key
        $this->apiKey = file_get_contents('_apiKey.txt');
        $this->log(sprintf("this->apiKey: %s", $this->apiKey));

        $inputFile = sprintf("%s/data/2017-2018.csv", getcwd());
        $addresses = $this->readInputData($inputFile, $limit = 2);

        $this->log(sprintf("addresses: %s", count($addresses)));


        if (self::$_test) {
            $coords = array();
            $coords[] = "-33.89721, 151.24733";
            $coords[] = "-33.89812, 151.25018";
        }
        else {
            $coords = generateAllCoords($addresses);
        }

        $this->createKML($coords);
    }

    /**
     */
    public function createKML($coords) {
        $filePath = "placemark.tpl";
        $template = file_get_contents($filePath);
        $placemarks = "";
        $cnt = 0;
        foreach($coords as $coord) {
            $name = sprintf("%d", $cnt++);
            $placemark = sprintf(
                "%s<Placemark><name>%s</name><description></description><styleUrl>#sale</styleUrl><Point><coordinates>%s</coordinates></Point></Placemark>",
                str_repeat(" ", 6),
                $name,
                $coord
            );
            $placemarks .= $placemark . "\n";
        }
        $placemarks = substr($placemarks, 0, -1); // remove last char
        $template = preg_replace("/%PLACEMARKS%/", $placemarks, $template);
        $filename = sprintf("%s/%s_placemarks.kml", dirname(__FILE__), date('Ymd-his'));
        file_put_contents($filename, $template);
    }

    /**
     * readInputData
     * "entity_id","increment_id","street","city","postcode","region","country_id"
     */
    public function readInputData($inputFile, $limit = 0) {
        $addresses = array();
        $fh = fopen($inputFile,"r");
        $cnt = 0;
        while(!feof($fh)) {
            if ($limit != 0 && $cnt++ > $limit) {
                //echo sprintf("limit %d reached!\n", $limit);
                break;
            }
            $record = fgetcsv($fh);
            //$this->log(sprintf("record: %s", print_r($record, true)) );
            $street = $record[2];
            $street = preg_replace("/\\\\n/", ", ", $record[2]);
            //$this->log(sprintf("street: %s", $street));
            $address = sprintf("%s, %s, %s, %s", $street, $record[3], $record[4], $record[5]);
            //$this->log(sprintf("address: %s", $address));
            $addresses[] = $address;
        }
        fclose($fh);
        return $addresses;
    }

    /**
     * generateAllCoords
     */
    public function generateAllCoords($addresses) {
        $coords = array();
        foreach($addresses as $address) {
            $coords = $this->generateCoords($address);
        }
        return $coords;
    }

    /**
     * generateCoords
     */
    public function generateCoords($address) {
        //$region = "USA";
        //$address = "1600 Pennsylvania Ave NW Washington DC 20500";

        $region = "AU";
        $address = "Shop 3036, Level 3, Westfield Bondi Junction, 500 Oxford St";
        $address = str_replace(" ", "+", $address);

        $auth = sprintf("&key=%s", self::API_KEY);
        $requestUrl = sprintf(
            "https://maps.google.com/maps/api/geocode/json?address=%s&sensor=false&region=%s%s",
            $address,
            $region,
            $auth
        );
        echo sprintf("get: %s\n", $requestUrl);
        $json = file_get_contents($requestUrl);
        $json = json_decode($json);

        //echo sprintf("json: %s\n", print_r($json, true));

        $lng = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lng'};
        $lat = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lat'};
        //echo sprintf("%f,%f\n", $lng, $lat);

        return [$lng, $lat];
    }

    /**
     * log
     */
    private function log($msg) {
        if (self::$_debug) {
            echo sprintf("%s\n", $msg);
        }
    }
}


$kml = new GenerateKML();
$kml->run();
?>