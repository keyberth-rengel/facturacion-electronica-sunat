<?php
header('Access-Control-Allow-Origin: *');

$numero_ticket  = $_POST['ticket'];
$token_access   = $_POST['token_access'];
$ruc            = $_POST['ruc'];
$serie          = $_POST['serie'];
$numero         = $_POST['numero'];

$nombre_archivo = $ruc.'-09-'.$serie.'-'.$numero;

$path = "files/guia_electronica/";
$respuesta_ticket = envio_ticket($path.'CDR/', $numero_ticket, $token_access, $ruc, $nombre_archivo);
var_dump($respuesta_ticket);

$jsondata = array(
    'success'       =>  true,
    'message'       =>  $respuesta_ticket['cdr_msj_sunat'],
    'codigo'        =>  $respuesta_ticket['cdr_ResponseCode'],
    'error_existe'  =>  $respuesta_ticket['numerror']            
);
echo json_encode($jsondata, JSON_UNESCAPED_UNICODE);

function envio_ticket($ruta_archivo_cdr, $ticket, $token_access, $ruc, $nombre_file){
    if(($ticket == "") || ($ticket == null)){
        $mensaje['cdr_hash'] = '';
        $mensaje['cdr_msj_sunat'] = 'Ticket vacio';
        $mensaje['cdr_ResponseCode']  = null;
        $mensaje['numerror'] = null;
    }else{
    
        $mensaje['ticket'] = $ticket;
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api-cpe.sunat.gob.pe/v1/contribuyente/gem/comprobantes/envios/'.$ticket,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'numRucEnvia: '.$ruc,
                'numTicket: '.$ticket,
                'Authorization: Bearer '. $token_access,
            ),
        ));

        $response_1  = curl_exec($curl);
        $response3  = json_decode($response_1);
        $codRespuesta = $response3->codRespuesta;
        curl_close($curl);                    
        //var_dump($response3);exit;
        
        $mensaje['ticket_rpta'] = $codRespuesta;
        if($codRespuesta == '99'){
            $error = $response3->error;
            $mensaje['cdr_hash'] = '';
            $mensaje['cdr_msj_sunat'] = $error->desError;
            $mensaje['cdr_ResponseCode'] = '99';
            $mensaje['numerror'] = $error->numError;            	            
        }else if($codRespuesta == '98'){
            $mensaje['cdr_hash'] = '';
            $mensaje['cdr_msj_sunat'] = 'Envío en proceso';
            $mensaje['cdr_ResponseCode']  = '98';
            $mensaje['numerror'] = '98';                        
        }else if($codRespuesta == '0'){
            $mensaje['arcCdr'] = $response3->arcCdr;
            $mensaje['indCdrGenerado'] = $response3->indCdrGenerado;
            
            file_put_contents($ruta_archivo_cdr . 'R-' . $nombre_file . '.ZIP', base64_decode($response3->arcCdr));

            $zip = new ZipArchive;
            if ($zip->open($ruta_archivo_cdr . 'R-' . $nombre_file . '.ZIP') === TRUE) {
                $zip->extractTo($ruta_archivo_cdr);
                $zip->close();
            }
            //unlink($ruta_archivo_cdr . 'R-' . $nombre_file . '.ZIP');

         //=============hash CDR=================
            $doc_cdr = new DOMDocument();
            $doc_cdr->load($ruta_archivo_cdr . 'R-' . $nombre_file . '.xml');
            
            $mensaje['cdr_hash']            = $doc_cdr->getElementsByTagName('DigestValue')->item(0)->nodeValue;
            $mensaje['cdr_msj_sunat']       = $doc_cdr->getElementsByTagName('Description')->item(0)->nodeValue;
            $mensaje['cdr_ResponseCode']    = $doc_cdr->getElementsByTagName('ResponseCode')->item(0)->nodeValue;        
            $mensaje['numerror']            = '';
        }else{
            $mensaje['cdr_hash']            = '';
            $mensaje['cdr_msj_sunat']       = 'SUNAT FUERA DE SERVICIO';
            $mensaje['cdr_ResponseCode']    = '88';            
            $mensaje['numerror']            = '88';
        }
    }
    return $mensaje;
}
  
?>