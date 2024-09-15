<?php
require_once 'HTTPRequestFactory.class.php';
/**
 * Class accessor is to make the calls to the API
 */
class accessor {

    /**
     * @var array $TOKEN_ID the company id, the user name and the password.
     */
    private const TOKEN_ID = array(
        "company" => "",
        "user" => "",
        "password" => ""
    );

    /**
     * @var string $BASE_URL the base url of the API.
     */
    private const BASE_URL = "";

    /**
     * @var string $token the token returned by the API. It is used to authenticate the user.
     */
    private string $token;

    /**
     * accessor destructor.
     */
    public function __destruct() {
        unset($this->token);
    }

    /**
     * this method is used to retrieve the token from the API.
     * @throws \Exception if a error occurs in the HTTPRequestFactory::retrieveToken method. 
     */
    public function createToken(): void {
        $endpoint = self::BASE_URL . "login";
        $body = self::TOKEN_ID;

        try {
            $token = HTTPRequestFactory::retrieveToken($endpoint, $body);
        }
        catch(Exception $e) {
            throw new Exception($e->getMessage());
        }

        $this->token = $token;
    }

    /**
     * Fetches all unread data of a form with the given form ID.
     *
     * @param string $formId The unique identifier of the form.
     *
     * @return array $rawData["data"] An array containing all unread data of the form.
     *
     * @throws \Exception If an error occurs in the HTTPRequestFactory::GetRequest method.
     * @throws \InvalidArgumentException If the API returns an error with a status or a message.
     *
     * The API is expected to return a JSON object with the following structure:
     *
     * {
     *   "status": "ok",
     *   "message": "",
     *   "data": [
     *     {
     *       formdata...
     *     }
     *   ]
     * }
     */
    public function getNewData(string $formId): array {
        $endpoint = self::BASE_URL . "forms/" . $formId . "/data/readnew";
        $token = $this->token;

        try {
            $rawData = HTTPRequestFactory::GetRequest($endpoint, $token);
        } 
        catch (Exception $e) {
            throw new Exception("Accessor->NewData : " . $e->getMessage());
        }

        if ($rawData["status"] == "error") {
            throw new InvalidArgumentException("Accessor->getNewData() : API returned an error.
            Error code: " . $rawData["error_code"] . " Error message: " . $rawData["error_message"]);
        } 
        elseif ($rawData["message"] != "") {
            throw new InvalidArgumentException("Accessor->NewData() : API returned an error : "
                . $rawData["message"]);
        }

        return $rawData["data"];
    }

    /**
     * $rawData example:
     * {
     *  "status": "ok",
     *  "forms": [
     *      {
     *          "id": "353517",
     *          "name": "ARC (signature client)",
     *          "update_time": "2021-03-11 10:13:43",
     *          "create_time": "2018-04-27 09:07:02",
     *          "options": {
     *              "checkboxOutputFalseValue": "Non",
     *              "checkboxOutputTrueValue": "Oui",
     *              "allUsersSeeHisto": "N",
     *              "allUsersUpdateHisto": "N"
     *          },
     *          "class": ""
     *      },
     *      {...}
     *  ]
     * }
     * @return array $rawData["forms"] all forms model of the company.
     * @throws \Exception if a error occurs in the HTTPRequestFactory::GetRequest method.
     */
    public function getAllForms(): array {
        $endpoint = self::BASE_URL . "forms";
        $token = $this->token;
        
        try {
            $rawData = HTTPRequestFactory::GetRequest($endpoint, $token);
        }
        catch(Exception $e) {
            throw new Exception("Accessor->getAllForms() : ".$e->getMessage());
        }

        if($rawData["status"] != "ok") {
            throw new InvalidArgumentException("Accessor->getAllForms() : API returned an error.
            Error code: " . $rawData["error_code"] . " Error message: " . $rawData["error_message"]);
        }

        return $rawData["forms"];
    }

    /**
     * $rawData example:
     * {
     *  "status": "ok",
     *  "message": "",
     *  "form": {
     *    "id": "353517",
     *    "name": "ARC (signature client)",
     *    "fields": {
     *      "signature1": {
     *        "caption": "Signature",
     *        "type": "signature",
     *        "required": false,
     *        "rgpd_personal_data": false,
     *        "word_del_line_if_empty": false,
     *        "visible_formula": "",
     *        "visible_formula_json": "",
     *        "same_line": false,
     *        "weight": "1",
     *       "get_geolocation_special_fields": false,
     *        "read_only_for_modification": false,
     *        "help": "",
     *        "icon": "",
     *        "color": ""
     *      },
     *     "...": {...},
     */
    public function getFormDetails(string $formId): array {
        $endpoint = self::BASE_URL . "forms/".$formId;
        $token = $this->token;

        try {
            $rawData = HTTPRequestFactory::GetRequest($endpoint, $token);
        }
        catch(Exception $e) {
            throw new Exception( "Accessor->getFormDetails : " . $e->getMessage());
        }

        return $rawData;
    }

    /**
     * Get data for a specific form.
     *
     * @param string $formId The ID of the form to retrieve data from.
     * @param string $dataId The ID of the specific data to be retrieved.
     * @throws \Exception if any error occurs during the HTTP request.
     * @return array The form data as an associative array.
     */
    public function getOneFormData(string $formId, string $dataId): array {
        $endpoint = self::BASE_URL . "forms/" . $formId . "/data/" . $dataId;
        $token = $this->token;

        try {
            $rawData = HTTPRequestFactory::GetRequest($endpoint, $token);
        }
        catch(Exception $e) {
            throw new Exception("Accessor->getOneFormData : " . $e->getMessage());
        }

        return $rawData["data"];
    }

    /**
     * Mark specific data as read for a given form.
     *
     * @param string $formId The ID of the form for which to mark data as read.
     * @param array $dataIds An array of data IDs to be marked as read.
     * @throws \Exception if any error occurs during the HTTP request.
     * @return array The response from the HTTP request.
     */
    public function MarkFormDataAsRead(string $formId, array $dataIds): array {
        $endpoint = self::BASE_URL . "forms/" . $formId . "/markasread";
        $token = $this->token;
        try {
            $dataIds = $this->stringsToIntsWithErrorHandling($dataIds);
            $body = array("data_ids" => $dataIds);
            $rawData = HTTPRequestFactory::PostRequest($endpoint, $token, $body);
        }
        catch(Exception $e) {
            throw new Exception("Accessor->MarkFormDataAsRead : " . $e->getMessage());
        }

        return $rawData;
    }

    /**
     * Mark specific data as unread for a given form.
     *
     * @param string $formId The ID of the form for which to mark data as unread.
     * @param array $dataIds An array of data IDs to be marked as unread.
     * @throws \Exception if any error occurs during the HTTP request.
     * @return array The response from the HTTP request.
     */
    public function MarkFormDataAsUnRead(string $formId, array $dataIds): array {
        $endpoint = self::BASE_URL . "forms/" . $formId . "/markasunread";
        $token = $this->token;

        try {
            $dataIds = $this->stringsToIntsWithErrorHandling($dataIds);
            $body = array("data_ids" => $dataIds);
            $rawData = HTTPRequestFactory::PostRequest($endpoint, $token, $body);
        }
        catch(Exception $e) {
            throw new Exception("Accessor->MarkFormDataAsRead : " . $e->getMessage());
        }

        return $rawData;
    }

    /**
     * Convert an array of strings to an array of integers with error handling.
     *
     * This function processes an array of strings and converts each string to its corresponding
     * integer value. If any of the strings cannot be converted to an integer, the function
     * throws an InvalidArgumentException with an error message.
     *
     * @param array $strings An array of strings to be converted to integers.
     *
     * @return array An array of integers converted from the input strings.
     *
     * @throws InvalidArgumentException If any string in the input array cannot be converted to an integer.
     *
     * @example
     *  $strings = ["123", "456", "789"];
     *  $integers = stringsToIntsWithErrorHandling($strings); // Returns: [123, 456, 789]
     *  $strings = ["123", "456", "abc"];
     *  $integers = stringsToIntsWithErrorHandling($strings); // Throws InvalidArgumentException
     */
    private function stringsToIntsWithErrorHandling(array $strings): array {
        $integers = [];

        foreach ($strings as $string) {
            $integer = filter_var($string, FILTER_VALIDATE_INT);

            if ($integer === false) {
                var_dump($string);
                throw new \InvalidArgumentException('Accessor->stringsToInts : Invalid integer value: ' . $string);
            }

            $integers[] = $integer;
        }

        return $integers;
    }
}
?>