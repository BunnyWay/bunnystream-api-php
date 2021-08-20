<?php
class BunnyCDNStream
{

    // Base URL for the API
    const baseUrl = "http://video.bunnycdn.com/library/";

    // Stream Library ID
    public $streamLibraryId = "";

    // API key
    private $apiAccessKey = "";

    public function __construct($streamLibraryId, $apiAccessKey)
    {
        $this->streamLibraryId = $streamLibraryId;
        $this->apiAccessKey = $apiAccessKey;
    }

    /**
     * Generate base URL containing the library ID.
     * 
     * @param string $endpoint The endpoint to append to the URL.
     * @return string Request URL
     */
    private function generateBaseUrl($endpoint)
    {
        return BunnyCDNStream::baseUrl . $this->streamLibraryId . $endpoint;
    }

    /**
     * Send cURL request to the API
     * 
     * @param string $url Endpoint to send request to
     * @param string $reqType The request type (GET, POST, PUT, DELETE)
     * @param string $contentType The content type of the request (application/json, application/x-www-form-urlencoded, multipart/form-data, etc.)
     * @param array $payload The payload to send (request body)
     * @param array $curl_opts Additional cURL options (array of curl_setopt() options as keys and the value as the value)
     * @return string The response body
     * @throws Exception Thrown if the request fails. The exception will either contain the cURL error code or the API error message.
     */
    private function sendRequest($url, $reqType, $contentType, $payload = null, $curl_options = null)
    {

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $reqType,
            CURLOPT_HTTPHEADER => [
                "AccessKey: " . $this->apiAccessKey,
                "Content-Type: " . $contentType
            ],
        ]);

        if ($curl_options) {
            foreach ($curl_options as $curl_option => $curl_value) {
                curl_setopt($curl, $curl_option, $curl_value);
            }
        }

        if ($payload) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
        }

        $response = curl_exec($curl);
        $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            throw new Exception("cURL exception: " . $err);
        } else {
            switch ($responseCode) {
                case 401:
                    throw new Exception("Unauthorized; check API key.");
                case 200:
                    return $response;
                    break;
                case 404:
                    throw new Exception("Not found.");
                default:
                    throw new Exception("An unknown error occured. Status code: " . $responseCode);
            }
        }
    }

    /**
     * Get video object from bunny
     * 
     * @param string $videoId Video ID
     * @return array Response
     * @throws Exception Thrown if the video could not be retrieved. Contains the API error message.
     */
    public function getVideo($videoId)
    {
        $url = $this->generateBaseUrl("/videos/" . $videoId);
        try {
            return json_decode($this->sendRequest($url, "GET", "application/json"), TRUE);
        } catch (Exception $e) {
            throw new Exception("Could not retrieve video. Error: " . $e->getMessage());
        }
    }

    /**
     * List videos from library
     * 
     * @param int $page Page number
     * @param int $perPage Number of videos per page
     * @param string $sortBy Sort by field
     * @param string $search Search by (optional)
     * @param string $collection Collection (optional)
     * @return array Response
     * @throws Exception Thrown if the list of videos could not be retrieved. Contains the API error message.
     */
    public function listVideos($page = 1, $perPage = 10, $sortBy = "date", $search = null, $collection = null)
    {
        $url = $this->generateBaseUrl("/videos");
        $url .= "?" . http_build_query([
            "page" => $page,
            "per_page" => $perPage,
            "sort_by" => $sortBy
        ]);
        if ($search)
            $url .= "&search=" . $search;

        if ($collection)
            $url .= "&collection=" . $collection;

        try {
            return json_decode($this->sendRequest($url, "GET", "application/json"), TRUE);
        } catch (Exception $e) {
            throw new Exception("Could not retrieve list of videos. Error: " . $e->getMessage());
        }
    }

    /**
     * Update existing video
     * 
     * @param string $videoId Video ID
     * @param string $title Title
     * @param string $collectionId Collection ID
     * @return array Response
     * @throws Exception Thrown if the video could not be updated. Contains the API error message.
     */
    public function updateVideo($videoId, $title, $collectionId)
    {
        $url = $this->generateBaseUrl("/videos/" . $videoId);
        $payload = [
            "title" => $title,
            "collectionId" => $collectionId
        ];
        try {
            return json_decode($this->sendRequest($url, "POST", "application/json", json_encode($payload)), TRUE);
        } catch (Exception $e) {
            throw new Exception("Error updating video: " . $e->getMessage());
        }
    }

    /**
     * Delete existing video
     * 
     * @param string $videoId Video ID
     * @return array Response
     * @throws Exception Thrown if the video could not be deleted. The exception will contain the API error message.
     */
    public function deleteVideo($videoId)
    {
        $url = $this->generateBaseUrl("/videos/" . $videoId);
        try {
            return json_decode($this->sendRequest($url, "DELETE", "application/json"), TRUE);
        } catch (Exception $e) {
            throw new Exception("Could not delete video: " . $e->getMessage());
        }
    }

    /**
     * Create new video
     * 
     * @param string $title Title
     * @param string $collectionId Collection ID (optional)
     * @return array Response
     * @throws Exception Thrown if the video could not be created. The exception will contain the API error message.
     */
    public function createVideo($title, $collectionId = null)
    {
        $url = $this->generateBaseUrl("/videos");
        $payload = [
            "title" => $title,
        ];
        if ($collectionId) {
            $payload["collectionId"] = $collectionId;
        }
        try {
            return json_decode($this->sendRequest($url, "POST", "application/json", json_encode($payload)), TRUE);
        } catch (Exception $e) {
            throw new Exception("Could not create video. Error: " . $e->getMessage());
        }
    }

    /**
     * Upload video if the video ID is provided
     * 
     * @param string $videoId Video ID
     * @param string $filePath File path
     * @return array Response
     */
    public function uploadVideoWithVideoId($videoId, $filePath)
    {
        $url = $this->generateBaseUrl("/videos/" . $videoId);

        if (!file_exists($filePath)) {
            throw new Exception("File does not exist.");
        }

        try {
            return json_decode($this->sendRequest($url, "PUT", "application/json", null, [
                CURLOPT_PUT => 1,
                CURLOPT_INFILE => fopen($filePath, "r"),
                CURLOPT_INFILESIZE => filesize($filePath)
            ]), TRUE);
        } catch (Exception $e) {
            throw new Exception("Upload failed. Error: " . $e->getMessage());
        }
    }

    /**
     * Create video object and upload video
     * 
     * @param string $title Title
     * @param string $filePath File path
     * @param string $collectionId Collection ID (optional)
     * @return array Response
     * @throws Exception Rethrows exceptions from createVideo/uploadVideoWithVideoId as required.
     */
    public function uploadVideo($title, $filePath, $collectionId = null)
    {
        $videoObject = "";
        try {
            $videoObject = $this->createVideo($title, $collectionId);
            return $this->uploadVideoWithVideoId($videoObject["guid"], $filePath);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Set video thumbnail
     * 
     * @param string $videoId Video ID
     * @param string $thumbnailUrl Thumbnail URL
     * @return array Response
     * @throws Exception Thrown if the thumbnail could not be set. The exception will contain the API error message.
     */
    public function setVideoThumbnail($videoId, $thumbnailUrl)
    {
        $url = $this->generateBaseUrl("/videos/" . $videoId . "/thumbnail");
        $payload = [
            "thumbnailUrl" => $thumbnailUrl
        ];
        try {
            return json_decode($this->sendRequest($url, "POST", "application/json", json_encode($payload)), TRUE);
        } catch (Exception $e) {
            throw new Exception("Could not set video thumbnail. Error: " . $e->getMessage());
        }
    }

    /**
     * Fetch video from external source
     * 
     * @param string $videoId Video ID
     * @param string $source Video URL
     * @param array $headers HTTP headers to send while fetching video (optional)
     * @return array Response
     * @throws Exception Thrown if the video could not be fetched. The exception will contain the API error message.
     */
    public function fetchVideo($videoId, $source, $headers = null)
    {
        $url = $this->generateBaseUrl("/videos/" . $videoId . "/fetch");
        $payload = [
            "url" => $source,
        ];
        if ($headers) {
            $payload["headers"] = $headers;
        }
        try {
            return json_decode($this->sendRequest($url, "POST", "application/json", json_encode($payload)), TRUE);
        } catch (Exception $e) {
            throw new Exception("Could not fetch video. Error: " . $e->getMessage());
        }
    }

    /**
     * Add video captions
     * 
     * @param string $videoId Video ID
     * @param string $language Unique srclang shortcode
     * @param string $captions Captions file
     * @param string $label The text description label for the caption (optional)
     * @return array Response
     * @throws Exception Thrown if the captions could not be added. The exception will contain the API error message.
     */
    public function addVideoCaptions($videoId, $language, $content, $label = null)
    {
        $url = $this->generateBaseUrl("/videos/" . $videoId . "/captions/" . $language);
        $payload = [
            "captionsFile" => base64_encode(file_get_contents($content)),
            "srclang" => $language,
        ];
        if ($label) {
            $payload["label"] = $label;
        }
        try {
            return json_decode($this->sendRequest($url, "POST", "application/json", json_encode($payload)), TRUE);
        } catch (Exception $e) {
            throw new Exception("Could not add captions. Error: " . $e->getMessage());
        }
    }

    /**
     * Delete caption
     * 
     * @param string $videoId Video ID
     * @param string $language Unique srclang shortcode
     * @return array Response
     * @throws Exception Thrown if the caption could not be deleted. The exception will contain the API error message.
     */
    public function deleteVideoCaptions($videoId, $language)
    {
        $url = $this->generateBaseUrl("/videos/" . $videoId . "/captions/" . $language);
        try {
            return json_decode($this->sendRequest($url, "DELETE", "application/json"), TRUE);
        } catch (Exception $e) {
            throw new Exception("Could not delete captions. Error: " . $e->getMessage());
        }
    }
}

?>
