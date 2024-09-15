<?php 
function writeDataToJson(array $data, string $folderName, string $fileName): void {
    $folderPath = "Data/" . $folderName;

    if (!file_exists($folderPath)) {
        mkdir($folderPath);
    }

    $json = json_encode($data);

    $fileName = $fileName . '.json';
    $filePath = $folderPath . '/' . $fileName;

    $file = fopen($filePath, 'w');
    $result = fwrite($file, $json);
    fclose($file);
}

const Name = 'intervention';

$jsonString = file_get_contents('Data/Raw/' . Name . '.json');
$forms = json_decode($jsonString, true);
$form = $forms[2];
echo '<pre>' , var_dump($form) , '</pre>';
WriteDataToJson($form, 'test', Name);

$field = array_pop($form);

$form = array_keys($form);
echo '<pre>' , var_dump($form) , '</pre>';

$field = array_keys($field);
echo '<pre>' , var_dump($field) , '</pre>';

$modele = file_get_contents('2_ProcessForm/Modele/'. Name .'.json');
$modele = json_decode($modele, true);
$modele = array_keys($modele);
echo '<pre>' , var_dump($modele) , '</pre>';

$missing = array_diff($modele, $form, $field);
echo '<pre>' , var_dump($missing) , '</pre>';

WriteDataToJson($missing, 'missing', Name);

?>