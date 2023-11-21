<?php
header('Access-Control-Allow-Origin: *');
require 'libraries/efactura.php';

$empresa        = $_POST['empresa'];
$guia           = $_POST['guia'];
$detalle        = $_POST['items'];
$token_access   = $_POST['token'];

$nombre_archivo = $empresa['ruc'].'-09-'.$guia['serie'].'-'.$guia['numero'];
//$nombre_archivo = '10481211641-09-TV50-2';

//echo $nombre_archivo;exit;
$path = "files/guia_electronica/";

crear_files($empresa, $guia, $detalle, $nombre_archivo, $path);
$respuesta = envio_xml($path.'FIRMA/', $nombre_archivo, $token_access);        
$numero_ticket = $respuesta->numTicket;

$jsondata = array(
    'numero_ticket' =>  $numero_ticket
);
echo json_encode($jsondata, JSON_UNESCAPED_UNICODE);

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
    var_dump($response2);exit;
    
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

?>