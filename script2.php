<?php  
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 1);

require_once 'Accessor.class.php';

// Constants--------------------------------------------------------------------
const CONTROL_COMBUSTION_FORM = array(
  'formName' => '',
  'formId'   => ''
);

const PV_FORM = array(
  'formName' => '',
  'formId'   => ''
);

const INTERVENTION_FORM = array(
  'formName' => "",
  'formId'   => ''
);

const FORM_TEMPLATES = array(
  //PV_FORM,
  //INTERVENTION_FORM,
  CONTROL_COMBUSTION_FORM
);

const MODEL_DATA_PATH = 'data/model/';

const DB_SERVER = '';
const DB_NAME = '';
const DB_USERNAME_USER = '';
const DB_PASSWORD_USER = '';

// Variables---------------------------------------------------------------------
$accessor = new Accessor();
$currentDateTime = new DateTime('now');
$currentDateTime->setTimezone(new DateTimeZone('Europe/Brussels'));
$currentDateTime = $currentDateTime->format('Y-m-d H-i-s');

// Database Connection
try{
  $mysqli = new mysqli(DB_SERVER, DB_USERNAME_USER, DB_PASSWORD_USER, DB_NAME);
}
catch(Exception $e) {
  exit();
}

// Functions---------------------------------------------------------------------

/**
 * This function simplifies the structure of the input array by merging the last 
 * element of the array, which is assumed to be an array itself, with the rest of the array elements.
 * 
 * The values in the last nested array are updated to only contain the 'value' key before being merged.
 * This assumes that the last nested array consists of arrays with a 'value' key.
 *
 * @param array $form The input array that needs to be simplified. The last element of this array should be
 * an array of associative arrays, each containing a 'value' key.
 *
 * @return array The resulting array after the values in the last nested array have been updated to the
 * 'value' key, and these updated values have been merged with the top-level array.
 *
 * @throws InvalidArgumentException If the provided argument is not an array.
 */
function simplifyArray(array $form): array {
  $fields = array_pop($form);
  foreach($fields as &$field) {
    $field = $field['value'];
  }
  return array_merge($form, $fields);
}

/**
 * Formats form data based on a specified model.
 * 
 * This function reads a model from a JSON file named after the $formName parameter
 * located at MODEL_DATA_PATH. This model is an associative array where keys represent
 * the column names and values are the keys in the form array.
 *
 * The function then loops through each form in the $forms array, simplifies it using the
 * simplifyArray() function, and maps its keys to the ones defined in the model. The 
 * resulting associative array is then appended to the $result array.
 * 
 * Note: The function currently only processes forms named 'controlecombustion'.
 *
 * @param string $formName The name of the form. This is also used as the name of the 
 * JSON file containing the model.
 * @param array $forms An array containing the form data to be formatted. Each form 
 * is an associative array itself.
 *
 * @return array The formatted form data. If $formName is not 'controlecombustion', 
 * an empty array is returned.
 */
function formatData(string $formName, array $forms): array {
  $result = [];
  if ($formName == 'controlecombustion') {
    //the keys are the column names and the values are the keys of the form
    $model = json_decode(file_get_contents(MODEL_DATA_PATH . $formName . '.json'), true);
    //each line in buffer "nomClient | varchar"
    foreach($forms as &$form) {      
      $form = simplifyArray($form);
      $buffer = [];
      foreach($model as $key => $value) {
        //$key = explode('|', $key)[0];
        $buffer[$key] = $form[$value];
      }
      array_push($result, $buffer);
    }
  }

  return $result;
}

/**
 * Prepares and executes SQL INSERT statements for each form in the forms array.
 *
 * This function constructs an SQL INSERT statement for each form in the forms array,
 * using the form name and the keys of each form which contain both the column name and the type.
 * It then prepares and executes each statement on the provided MySQLi connection.
 *
 * @param string $formName The name of the form, which is also used as the table name.
 * @param array $forms An array of associative arrays, each representing a form.
 * @param mysqli $mysqli A MySQLi connection object.
 */
function prepareAndExecuteSQL(array $forms, mysqli $mysqli, string $formId, $accessor): void {

  foreach($forms as $form) {
    $columnNames = [];
    $paramTypes = '';
    $params = [];

    foreach($form as $key => $value) {
      list($columnName, $type) = explode(' | ', $key);
      $columnNames[] = $columnName;
      $params[] = $value;

      switch(strtolower($type)) {
        case 'int':
        case 'integer':
          $paramTypes .= 'i';
          break;
        case 'double':
        case 'float':
        case 'real':
          $paramTypes .= 'd';
          break;
        case 'blob':
          $paramTypes .= 'b';
          break;
        case 'string':
        case 'char':
        case 'varchar':
        case 'text':
        case 'date':
        default:
          $paramTypes .= 's';
          break;
      }
    }

    $columns = implode(", ", $columnNames);
    $placeholders = str_repeat("?, ", count($columnNames) - 1) . "?";

    $sql = "INSERT INTO `controlecombustion` ($columns) VALUES ($placeholders)";

    if($stmt = $mysqli->prepare($sql)) {
      $stmt->bind_param($paramTypes, ...$params);
      if($stmt->execute() === false) {
        echo '<pre>', var_dump($stmt->error), '</pre>';
      }
      else {
        $idOfForm = array($form['id | int']);
        $result[] = $accessor-> MarkFormDataAsRead($formId, $idOfForm);
      }
    } 
    else {
      echo '<pre>', var_dump($sql, true), '</pre>';
      var_dump("Error preparing statement: " . $mysqli->error);
    }
  }
}

// Main--------------------------------------------------------------------------
try {
  $accessor->createToken();
}
catch(Exception $e) {
  var_dump($e->getMessage());
  exit();
}

foreach(FORM_TEMPLATES as $formTemplate) {
  $formName = $formTemplate['formName'];
  $formId = $formTemplate['formId'];

  try {
    $forms = $accessor->getNewData($formId);
    $forms = formatData($formName, $forms);
    prepareAndExecuteSQL($forms, $mysqli, $formId, $accessor);
  }
  catch(Exception $e) {
    $message = $formTemplate['formName'] . ' : ' . $e->getMessage();
    exit($e->getMessage());
  }
}

try {
  $mysqli->close();
}
catch(\Exception $e) {
  throw new \Exception($e->getMessage());
}
?>

