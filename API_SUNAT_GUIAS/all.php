<?php
header('Access-Control-Allow-Origin: *');
require 'libraries/efactura.php';

$datos = file_get_contents("php://input");
$obj = json_decode($datos, true);

$empresa        = $obj['empresa'];
$guia           = $obj['guia'];
$detalle        = $obj['items'];

$nombre_archivo = $empresa['ruc'].'-09-'.$guia['serie'].'-'.$guia['numero'];
$path = "files/guia_electronica/";

$token_access = token($empresa['guias_client_id'], $empresa['guias_client_secret'], $empresa['ruc'].$empresa['usu_secundario_produccion_user'], $empresa['usu_secundario_produccion_password']);

crear_files($empresa, $guia, $detalle, $nombre_archivo, $path);
$respuesta = envio_xml($path.'FIRMA/', $nombre_archivo, $token_access);        
$numero_ticket = $respuesta->numTicket;

sleep(2);//damos tiempo para que SUNAT procese y responda.
$respuesta_ticket = envio_ticket($path.'CDR/', $numero_ticket, $token_access, $empresa['ruc'], $nombre_archivo);
var_dump($respuesta_ticket);

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

function envio_xml($path, $nombre_file, $token_access){
    $curl = curl_init();
    $data = array(
                'nomArchivo'  =>  $nombre_file.".zip",
                'arcGreZip'   =>  base64_encode(file_get_contents($path.$nombre_file.'.zip')),
                'hashZip'     =>  hash_file("sha256", $path.$nombre_file.'.zip')
            );

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api-cpe.sunat.gob.pe/v1/contribuyente/gem/comprobantes/".$nombre_file,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>json_encode(array('archivo' => $data)),
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer '. $token_access,
            'Content-Type: application/json'
        ),
    ));

    $response2 = curl_exec($curl);
    curl_close($curl);
    
    return json_decode($response2);
}

function crear_files($empresa, $guia, $detalle, $nombre_archivo, $path){
    $xml = desarrollo_xml($empresa, $guia, $detalle);
    
    $archivo = fopen($path."XML/".$nombre_archivo.".xml", "w+");
    fwrite($archivo, $xml);
    fclose($archivo);
    
    firmar_xml($nombre_archivo.".xml", "1");        

    $zip = new ZipArchive();
    if($zip->open($path."FIRMA/".$nombre_archivo.".zip", ZipArchive::CREATE) === true){
        $zip->addFile($path."FIRMA/".$nombre_archivo.".xml", $nombre_archivo.".xml");
    }
    return $nombre_archivo;
}

function desarrollo_xml($empresa, $guia, $detalles){        
    $guia['fecha_emision_sf']   = $guia['fecha_emision'];
    $guia['fecha_traslado_sf']  = $guia['fecha_traslado'];

    $empresa['empresa']         = $empresa['razon_social'];
    $guia['numero_documento']   = $guia['destinatario_numero_documento'];
    $guia['entidad']            = $guia['destinatario_nombres_razon'];
    
    $xml =  '<?xml version="1.0" encoding="UTF-8"?>
        <DespatchAdvice xmlns="urn:oasis:names:specification:ubl:schema:xsd:DespatchAdvice-2" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2">                    
                <ext:UBLExtensions>
                    <ext:UBLExtension>
                        <ext:ExtensionContent></ext:ExtensionContent>
                    </ext:UBLExtension>
                </ext:UBLExtensions>
                <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
                <cbc:CustomizationID>2.0</cbc:CustomizationID>
                <cbc:ID>'.$guia['serie'].'-'.$guia['numero'].'</cbc:ID>
                <cbc:IssueDate>'.$guia['fecha_emision_sf'].'</cbc:IssueDate>
                <cbc:IssueTime>'.date("H:i:s").'</cbc:IssueTime>
                <cbc:DespatchAdviceTypeCode>09</cbc:DespatchAdviceTypeCode>
                <cac:Signature>
                  <cbc:ID>'.$empresa['ruc'].'</cbc:ID>
                  <cac:SignatoryParty>
                    <cac:PartyIdentification>
                      <cbc:ID>'.$empresa['ruc'].'</cbc:ID>
                    </cac:PartyIdentification>
                  </cac:SignatoryParty>
                  <cac:DigitalSignatureAttachment>
                    <cac:ExternalReference>
                      <cbc:URI>'.$empresa['ruc'].'</cbc:URI>
                    </cac:ExternalReference>
                  </cac:DigitalSignatureAttachment>
                </cac:Signature>';
        $xml .= '<cac:DespatchSupplierParty>
                    <cac:Party>
                        <cac:PartyIdentification>
                            <cbc:ID schemeID="6" schemeName="Documento de Identidad" schemeAgencyName="PE:SUNAT" schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">'.$empresa['ruc'].'</cbc:ID>
                        </cac:PartyIdentification>
                        <cac:PartyName>
                            <cbc:Name><![CDATA['.$empresa['empresa'].']]></cbc:Name>
                        </cac:PartyName>
                        <cac:PartyLegalEntity>
                            <cbc:RegistrationName><![CDATA['.$empresa['empresa'].']]></cbc:RegistrationName>
                        </cac:PartyLegalEntity>
                    </cac:Party>
                </cac:DespatchSupplierParty>';

     $xml .=    '<cac:DeliveryCustomerParty>
                    <cac:Party>
                        <cac:PartyIdentification>
                            <cbc:ID schemeID="6" schemeName="Documento de Identidad" schemeAgencyName="PE:SUNAT" schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">'.$guia['numero_documento'].'</cbc:ID>
                        </cac:PartyIdentification>
                        <cac:PartyLegalEntity>
                            <cbc:RegistrationName><![CDATA['.$guia['entidad'].']]></cbc:RegistrationName>
                        </cac:PartyLegalEntity>
                    </cac:Party>
                </cac:DeliveryCustomerParty>';

        $xml .= '<cac:Shipment>
                    <cbc:ID>SUNAT_Envio</cbc:ID>
                    <cbc:HandlingCode listAgencyName="PE:SUNAT" listName="Motivo de traslado" listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo20">01</cbc:HandlingCode>
                    <cbc:GrossWeightMeasure unitCode="KGM">'.$guia['peso_total'].'</cbc:GrossWeightMeasure>';

                    if($guia['guia_motivo_traslado_id'] == 7){//importaciones
            $xml .= '<cbc:TotalTransportHandlingUnitQuantity>'.$guia['numero_bultos'].'</cbc:TotalTransportHandlingUnitQuantity>';
                    }

            $xml .= '<cac:ShipmentStage>
                        <cbc:ID>1</cbc:ID>
                        <cbc:TransportModeCode listAgencyName="PE:SUNAT" listName="Modalidad de traslado" listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo18">0'.$guia['guia_modalidad_traslado_id'].'</cbc:TransportModeCode>
                        <cac:TransitPeriod>
                            <cbc:StartDate>'.$guia['fecha_traslado_sf'].'</cbc:StartDate>
                        </cac:TransitPeriod>';

            if($guia['guia_modalidad_traslado_id'] == '1'){
            $xml .= '<cac:CarrierParty>
                            <cac:PartyIdentification>
                                <cbc:ID schemeID="6" schemeName="Documento de Identidad" schemeAgencyName="PE:SUNAT" schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">'.$guia['numero_documento_transporte'].'</cbc:ID>
                            </cac:PartyIdentification>
                            <cac:PartyLegalEntity>
                                <cbc:RegistrationName><![CDATA['.$guia['entidad_transporte'].']]></cbc:RegistrationName>';
                                if($guia['numero_mtc_transporte'] != ''){
            $xml .=                 '<cbc:CompanyID>'.$guia['numero_mtc_transporte'].'</cbc:CompanyID>';
                                }
            $xml .=         '</cac:PartyLegalEntity>
                        </cac:CarrierParty>';
            }
            if($guia['guia_modalidad_traslado_id'] == '2'){
            $xml .= '<cac:DriverPerson>
                            <cbc:ID schemeID="1" schemeName="Documento de Identidad" schemeAgencyName="PE:SUNAT" schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">'.$guia['conductor_dni'].'</cbc:ID>
                            <cbc:FirstName>'.$guia['conductor_nombres'].'</cbc:FirstName>
                            <cbc:FamilyName>'.$guia['conductor_apellidos'].'</cbc:FamilyName>
                            <cbc:JobTitle>Principal</cbc:JobTitle>
                            <cac:IdentityDocumentReference>
                                <cbc:ID>'.$guia['conductor_licencia'].'</cbc:ID>
                            </cac:IdentityDocumentReference>
                        </cac:DriverPerson>';                                                                        
            }

            $xml .= '</cac:ShipmentStage>
                    <cac:Delivery>
                        <cac:DeliveryAddress>
                            <cbc:ID schemeName="Ubigeos" schemeAgencyName="PE:INEI">'.$guia['llegada_ubigeo'].'</cbc:ID>
                            <cac:AddressLine>
                                <cbc:Line>'.$guia['llegada_direccion'].'</cbc:Line>
                            </cac:AddressLine>
                        </cac:DeliveryAddress>
                        <cac:Despatch>
                            <cac:DespatchAddress>
                                <cbc:ID schemeName="Ubigeos" schemeAgencyName="PE:INEI">'.$guia['partida_ubigeo'].'</cbc:ID>
                                <cac:AddressLine>
                                    <cbc:Line>'.$guia['partida_direccion'].'</cbc:Line>
                                </cac:AddressLine>
                            </cac:DespatchAddress>
                        </cac:Despatch>
                    </cac:Delivery>';

                    if($guia['guia_modalidad_traslado_id'] == '2'){
            $xml .= '<cac:TransportHandlingUnit>
                        <cac:TransportEquipment>
                            <cbc:ID>'.$guia['vehiculo_placa'].'</cbc:ID>
                        </cac:TransportEquipment>
                    </cac:TransportHandlingUnit>';
                    }
            $xml .= '</cac:Shipment>';        

                $i = 1;                        
                foreach($detalles as $values){
                    $values['producto']           = $values['descripcion'];
                    $values['producto_codigo']    = $values['codigo']; 
                    
                    
                $xml .=  '<cac:DespatchLine>
                    <cbc:ID>'.$i.'</cbc:ID>
                    <cbc:DeliveredQuantity unitCode="'.$values['codigo_unidad'].'">'.$values['cantidad'].'</cbc:DeliveredQuantity>
                    <cac:OrderLineReference>
                        <cbc:LineID>1</cbc:LineID>
                    </cac:OrderLineReference>
                    <cac:Item>
                        <cbc:Description>'.$values['producto'].'</cbc:Description>
                        <cac:SellersItemIdentification>
                        <cbc:ID>'.$values['producto_codigo'].'</cbc:ID>
                        </cac:SellersItemIdentification>
                    </cac:Item>
                </cac:DespatchLine>';                        
                $i++;                    
                }
        $xml.=  '</DespatchAdvice>';
    return $xml;
}
    
function firmar_xml($name_file, $entorno, $baja = ''){        
    $xmlstr = file_get_contents("files/guia_electronica/XML/".$name_file);

    $domDocument = new \DOMDocument();
    $domDocument->loadXML($xmlstr);
    $factura  = new Factura();
    $xml = $factura->firmar($domDocument, '', $entorno);
    $content = $xml->saveXML();
    file_put_contents("files/guia_electronica/FIRMA/".$name_file, $content);
}

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