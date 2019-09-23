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
    private $region = "AU";

    /**
     * run
     */
    public function run() {
        // load api key
        $this->apiKey = file_get_contents('_apiKey.txt');
        $this->log(sprintf("this->apiKey: %s", $this->apiKey));

        $addresses = array();

        if (self::$_test) {
            //$this->region = "USA";
            //$addresses[] = "1600 Pennsylvania Ave NW Washington DC 20500";
            $this->region = "AU";
            $addresses[] = "Shop 3036, Level 3, Westfield Bondi Junction, 500 Oxford St";
        }
        else {
            $inputFile = sprintf("%s/data/2018-2019.csv", getcwd());
            $addresses = $this->readInputData($inputFile, $limit = 0);
        }

        $this->log(sprintf("addresses: %s", count($addresses)));


        if (self::$_test) {
            // lat, lon
            $coords = array();
            $coords[] = [
                'address' => 'test address 1',
                'coords' => [151.24733,-33.89721]
            ];
            $coords[] = [
                'address' => 'test address 2',
                'coords' => [151.25018,-33.89812]
            ];
        }
        else {
            $coords = $this->generateAllCoords($addresses);
            //$this->log(sprintf("coords: %s", print_r($coords, true)));
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
            $name = sprintf("%d - %s", $cnt++, $coord['address']);
            //$this->log(sprintf("coord: %s", print_r($coord, true)));
            $placemark = sprintf(
                "%s<Placemark><name>%s</name><description></description><styleUrl>#sale</styleUrl><Point><coordinates>%f,%f</coordinates></Point></Placemark>",
                str_repeat(" ", 6),
                $name,
                $coord['coords'][0],
                $coord['coords'][1]
            );
            $placemarks .= $placemark . "\n";
        }
        $placemarks = substr($placemarks, 0, -1); // remove last char
        $template = preg_replace("/%PLACEMARKS%/", $placemarks, $template);
        $filename = sprintf("%s/output/%s_placemarks.kml", dirname(__FILE__), date('Ymd-his'));
        file_put_contents($filename, $template);
    }

    /**
     * readInputData
     * "entity_id","increment_id","street","city","postcode","region","country_id"
     */
    public function readInputData($inputFile, $limit = 0) {
        $addresses = array();
        $fh = fopen($inputFile,"r");
        $header;
        $cnt = 0;
        while(!feof($fh)) {
            $record = fgetcsv($fh);
            // skip header
            if ($cnt == 0) {
                $header = $record;
                $cnt++;
                continue;
            }
            if ($limit != 0 && $cnt++ > $limit) {
                //$this->log(sprintf("limit %d reached!", $limit));
                break;
            }
            //$this->log(sprintf("record: %s", print_r($record, true)) );
            $street = $record[2];
            $street = preg_replace("/\\\\n/", ", ", $record[2]);
            //$this->log(sprintf("street: %s", $street));
            $address = sprintf("%s,%s,%s,%s", $street, $record[3], $record[4], $record[5]);
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
            try {
                $coord = $this->generateCoords($address);
                $coords[] = array(
                    'address' => $address,
                    'coords' => $coord
                );
            }
            catch(Exception $ex) {
                $this->log(sprintf("Error: %s", $ex->getMessage()));
                $this->log(sprintf("%s", $address));
            }
        }
        return $coords;
    }

    /**
     * generateCoords
     */
    public function generateCoords($address) {
        $address = str_replace(" ", "+", $address);
        $auth = sprintf("&key=%s", $this->apiKey);
        $requestUrl = sprintf(
            "https://maps.google.com/maps/api/geocode/json?address=%s&sensor=false&region=%s%s",
            $address,
            $this->region,
            $auth
        );
        //$this->log(sprintf("get: %s", $requestUrl));
        $json = file_get_contents($requestUrl);
        $geocode = json_decode($json);
        if ($geocode === null) {
            throw new Exception(sprintf("cannot geocode address - %s", $json));
        }
        else if (
            !isset($geocode->{'results'}[0]->{'geometry'}->{'location'}->{'lng'})
            ||
            !isset($geocode->{'results'}[0]->{'geometry'}->{'location'}->{'lat'})
        ) {
            throw new Exception(sprintf("cannot geocode address - %s", print_r($geocode, true)) );
        }
        else {
            //$this->log(sprintf("json: %s", print_r($geocode, true)));
            $lng = $geocode->{'results'}[0]->{'geometry'}->{'location'}->{'lng'};
            $lat = $geocode->{'results'}[0]->{'geometry'}->{'location'}->{'lat'};
            //$this->log(sprintf("%s: %f,%f", __METHOD__, $lng, $lat));
        }
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