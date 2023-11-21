<?php
header('Access-Control-Allow-Origin: *');

$empresa['guias_client_id']                       = $_POST['guias_client_id'];
$empresa['guias_client_secret']                   = $_POST['guias_client_secret'];
$empresa['ruc']                                   = $_POST['ruc'];
$empresa['usu_secundario_produccion_user']        = $_POST['usu_secundario_produccion_user'];
$empresa['usu_secundario_produccion_password']    = $_POST['usu_secundario_produccion_password'];

$token_access = token($empresa['guias_client_id'], $empresa['guias_client_secret'], $empresa['ruc'].$empresa['usu_secundario_produccion_user'], $empresa['usu_secundario_produccion_password']);
echo $token_access;

function token($client_id, $client_secret, $usuario_secundario, $usuario_password){
    $url = "https://api-seguridad.sunat.gob.pe/v1/clientessol/".$client_id."/oauth2/token/";
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_POST, true);

    $datos = array(
            'grant_type'    =>  'password',     
            'scope'         =>  'https://api-cpe.sunat.gob.pe',
            'client_id'     =>  $client_id,
            'client_secret' =>  $client_secret,
            'username'      =>  $usuario_secundario,
            'password'      =>  $usuario_password
    );
    //var_dump($datos);exit;
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($datos));
    curl_setopt($curl, CURLOPT_COOKIEJAR, __DIR__.'/cookies.txt');

    $headers = array('Content-Type' => 'Application/json');
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($curl);
    curl_close($curl);

    $response = json_decode($result);
    return $response->access_token;  
}
