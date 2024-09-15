<?php
    /**
     * Class HTTPRequestFactory is a utility class for making HTTP requests to an API.
     * It provides methods for sending GET and POST requests, as well as retrieving an authentication token.
     *
     * The class includes the following methods:
     * - retrieveToken(): Retrieves an identification token from an API endpoint.
     * - GetRequest(): Sends a GET request to an API endpoint and returns the response as an associative array.
     * - PostRequest(): Sends a POST request to an API endpoint with the provided request body and returns the response as an associative array.
     */
    class HTTPRequestFactory {
        /**
         * The retrieveToken() function sends a POST request to a given API endpoint
         * and retrieves an identification token from the JSON response.
         *
         * The expected response format from the API is:
         * {
         *     "status": "ok",
         *     "message": "",
         *     "data": {
         *         "token": "TOKEN"
         *     }
         * }
         *
         * @param string $endpoint The API endpoint URL, composed of the base URL and the request endpoint.
         * @param array $body The parameters of the request as an associative array.
         * @return string The retrieved identification token.
         *
         * @throws \Exception If a cURL error occurs during the request.
         * @throws \Exception If the JSON response cannot be converted to an array.
         * @throws \Exception If the response 'status' is not 'ok'.
         * @throws \Exception If the 'token' is missing from the response 'data'.
         */
        public static function retrieveToken(string $endpoint, array $body): string {
            $curl = curl_init();
            curl_setopt_array($curl,
            [
                CURLOPT_URL             => $endpoint,
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
                CURLOPT_ENCODING        => "",
                CURLOPT_MAXREDIRS       => 50,
                CURLOPT_TIMEOUT         => 500,
                CURLOPT_HTTPHEADER      => array('Content-Type: application/json'),
                CURLOPT_POST            => true,
                CURLOPT_POSTFIELDS      => json_encode($body),
            ]);
            $respond = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if($err) {
                throw new \Exception("HTTPRequestFactory::retrieveToken() : cURL Error #:" . $err);
            }

            $respond = json_decode($respond, true);
            if($respond == null || $respond == false) {
                throw new \Exception("HTTPRequestFactory::retrieveToken() : Failed to convert the JSON to an array");
            }

            if($respond["status"] != "ok") {
                throw new \InvalidArgumentException("HTTPRequestFactory::retrieveToken() : Failed to retrieve the token");
            }

            if(!isset($respond['data']['token'])) {
                throw new \Exception("HTTPRequestFactory::retrieveToken() : The token is missing from the response");
            }

            return $respond['data']['token'];
        }

        /**
         * The GetRequest() function sends a GET request to a given API endpoint and returns
         * the response as an associative array.
         *
         * @param string $endpoint The API endpoint URL, composed of the base URL and the request endpoint.
         * @param string $auth The authentication token for the user.
         * @param array $params Optional query parameters to add to the endpoint as an associative array.
         * @return array The response of the request as an associative array.
         *
         * @throws \Exception If a cURL error occurs during the request.
         * @throws \Exception If the JSON response cannot be converted to an array.
         */
        public static function GetRequest(string $endpoint, string $auth, array $params = array()): array {
            $curl = curl_init();
            $headers = array(
                'Authorization: ' . $auth,
                'Content-Type: application/json',
            );
            curl_setopt_array($curl,
            [
                CURLOPT_URL             => $endpoint.'?'.http_build_query($params),
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
                CURLOPT_ENCODING        => "",
                CURLOPT_MAXREDIRS       => 10,
                CURLOPT_TIMEOUT         => 20,
                CURLOPT_HTTPHEADER      => $headers,
            ]);
            $respond = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if($err) {
                throw new \Exception("HTTPRequestFactory::GetRequest : cURL Error #:" . $err);
            }

            $respond = json_decode($respond, true);
            if($respond == null || $respond == false) {
                throw new \Exception("HTTPRequestFactory::GetRequest : Failed to convert the JSON to an array");
            }

            return $respond;
        }

        /**
         * The PostRequest() function sends a POST request to a given API endpoint with the provided
         * request body and returns the response as an associative array.
         *
         * @param string $endpoint The API endpoint URL, composed of the base URL and the request endpoint.
         * @param string $auth The authentication token for the user.
         * @param array $body Optional request body parameters as an associative array.
         * @param array $params Optional query parameters to add to the endpoint as an associative array.
         * @return array The response of the request as an associative array.
         *
         * @throws \Exception If a cURL error occurs during the request.
         * @throws \Exception If the JSON response cannot be converted to an array.
         */
        public static function PostRequest(string $endpoint, string $auth, array $body = array(), array $params = array()): array {
            $curl = curl_init();
            $headers = array(
                'Authorization: ' . $auth,
                'Content-Type: application/json',
            );
            curl_setopt_array($curl,
            [
                CURLOPT_URL             => $endpoint.'?'.http_build_query($params),
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
                CURLOPT_ENCODING        => "",
                CURLOPT_MAXREDIRS       => 10,
                CURLOPT_TIMEOUT         => 20,
                CURLOPT_HTTPHEADER      => $headers,
                CURLOPT_POST            => true,
                CURLOPT_POSTFIELDS      => json_encode($body),
            ]);
            $respond = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if($err) {
                throw new \Exception("HTTPRequestFactory::PostRequest : cURL Error #:" . $err);
            }

            $respond = json_decode($respond, true);
            if($respond == null || $respond == false) {
                throw new \Exception("HTTPRequestFactory::PostRequest : Failed to convert the JSON to an array");
            }

            return $respond;
        }
    }
?>