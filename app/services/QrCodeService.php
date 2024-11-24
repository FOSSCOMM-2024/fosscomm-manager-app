<?php

namespace app\services;

class QrCodeService
{
    private $server;
    private $generationAPI;

    public function __construct()
    {
        $this->server = $this->figureCurrentServerURL();
        $this->generationAPI = "https://api.qrserver.com/v1/create-qr-code/?data=";
    }

    public function generateFromAPI($data, $size = ["width" => 150, "height" => 150])
    {
        // Generate the URL
        $url = $this->generationAPI . urlencode($data);

        // Add the size
        $url .= "&size=" . $size['width'] . "x" . $size['height'];

        return $url;
    }

    public function printInfo()
    {
        echo "Server: " . $this->server . "<br>";
        echo "API: " . $this->generationAPI . "<br>";
    }

    public function showTestQR($data)
    {
        $url = $this->generateFromAPI($data);
        echo "<img src='$url'>";
    }

    public function makeUserDetailsQr($attendeeID)
    {
        $qrData = $this->figureCurrentServerURL() . "/app/tools/user_details.php?id=" . $attendeeID;
        return $this->generateFromAPI($qrData);
    }

    private function figureCurrentServerURL()
    {
        $appConfig = include __DIR__ . '/../config/app_config.php';

        if ($appConfig['development_options']['is_development']) {
            return $appConfig['development_options']['dev_server_url'];
        }
        else {
            return $appConfig['prod_server'];
        }
    }
}