<?PHP
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Guias_transportistas extends CI_Controller {

    public function __construct() {        
        parent::__construct();
        date_default_timezone_set('America/Lima');

        $this->load->model('guia_transportistas_model');
        $this->load->model('guia_transportista_detalles_model');
        $this->load->model('accesos_model');
        
        $this->load->model('variables_diversas_model');
        $this->load->model('ubigeo_departamentos_model');
        $this->load->model('guia_transportista_carros_model');
        $this->load->model('series_model');
        $this->load->model('carros_model');
        //$this->load->model('venta_guia_transportistas_model');
        $this->load->model('entidades_model');
        $this->load->model('empresas_model');
        $this->load->model('productos_model');
        
        $this->load->library('pdf');
        $this->load->helper('ayuda');
                
        require_once (APPPATH .'libraries/Numletras.php');
        require_once (APPPATH .'libraries/efactura.php');
        require_once (APPPATH .'libraries/qr/phpqrcode/qrlib.php');

        $empleado_id = $this->session->userdata('empleado_id');
        if (empty($empleado_id)) {
            $this->session->set_flashdata('mensaje', 'No existe sesion activa');
            redirect(base_url());
        }
    }
    
    public function index(){
        $this->accesos_model->menuGeneral();
        $this->load->view('guia_transportistas/index.html');
        $this->load->view('templates/footer');
    }
    
    public function operacion(){
        $this->accesos_model->menuGeneral();
        $this->load->view('guia_transportistas/operacion.html');
        $this->load->view('templates/footer');
    }
    
    public function operaciones(){ 
        
        $data = array(                        
            'fecha_emision'         =>  format_fecha_0000_00_00($_GET['fecha_emision']),
            'hora_emision'          =>  date("H:i:s"),
            'fecha_traslado'        =>  format_fecha_0000_00_00($_GET['fecha_traslado']),
            'numero_mtc'            =>  $_GET['numero_mtc'],
            
            'partida_ubigeo'        =>  $_GET['partida_ubigeo'],
            'partida'               =>  $_GET['partida_direccion'],
            'llegada_ubigeo'        =>  $_GET['llegada_ubigeo'],
            'llegada'               =>  $_GET['llegada_direccion'],
            
            'remitente_id'          =>  $_GET['remitente_id'],
            'destinatario_id'       =>  $_GET['destinatario_id'],
                        
            'chofer_id'             =>  $_GET['chofer_id'],                        
            
            'peso_total'            =>  $_GET['peso_total'],
            'observaciones'         =>  $_GET['notas'],
            
            'fecha_insert'          =>  date("Y-m-d H:i:s")
        );
        
        
        $data_carros[0] = $_GET['carro_id'];
        if(isset($_GET['carro_id_secundario'])){
            $data_carros[1] = $_GET['carro_id_secundario'];
        }
        
        if(isset($_GET['sub_contratista_id'])){
            $data = array_merge($data, array('sub_contratista_id' => $_GET['sub_contratista_id']));
        }
        
        if(isset($_GET['pagador_flete_id'])){
            $data = array_merge($data, array('pagador_flete_id' => $_GET['pagador_flete_id']));
        }
        
        //la serie y numero solo se coloca en el insert
        $serie = 'V001';
        if(!isset($_GET['guia_id'])){
            //$serie = $this->series_model->select(1, array('serie'), array('tipo_documento_id' => 9));        
            $data_identificacion = array(
                'serie'     => $serie,
                'numero'    => ($this->guia_transportistas_model->ultimoNumeroDeSerie($serie) + 1)
            );
            $data = array_merge($data, $data_identificacion);
        }

        if(isset($_GET['guia_id'])){//EDITAR            
            $guia_id = $_GET['guia_id'];
            $data = array_merge($data, array('respuesta_sunat_codigo' => null));
            $this->guia_transportistas_model->modificar($_GET['guia_id'], $data);
            
            $numero_filas = $this->guia_transportistas_model->count_transportista_id($guia_id);
            if( $numero_filas > 0){
                $this->guia_transportista_detalles_model->delete_guia_id($_GET['guia_id']);
            }
            
            $numero_adjuntos = $this->guia_transportistas_model->count_adjuntos_id($guia_id);
            if($numero_adjuntos > 0){
                $this->db->delete('guia_transportista_adjuntos', array('guia_transportista_id' => $guia_id));
            }
            
            $this->guia_transportista_carros_model->delete_guia_id($guia_id);
        }  else {//GUARDAR
            $this->guia_transportistas_model->insertar($data);
            $guia_id = $this->guia_transportistas_model->select_max_id();            
        }
        
        
        ///////////////////
        $guia_transportista_carros = array(
            'guia_transportista_id' =>  $guia_id,
            'carro_id'              =>  $data_carros[0],
        );
        $this->guia_transportista_carros_model->insertar($guia_transportista_carros);
        if(isset($_GET['carro_id_secundario'])){
            $guia_transportista_carros_secundario = array(
                'guia_transportista_id' =>  $guia_id,
                'carro_id'              =>  $data_carros[1],
            );
            $this->guia_transportista_carros_model->insertar($guia_transportista_carros_secundario);
        }
        //exit;
        ///////////////////

        //////////ADJUNTO
        if(($_GET['adjunto_serie'] != '') && ($_GET['adjunto_numero'] != '')){
            $data_adjunto = array(
                'guia_transportista_id' =>  $guia_id,
                'tipo_documento_id'     =>  $_GET['tipo_documento_id'],
                'serie'                 =>  $_GET['adjunto_serie'],
                'numero'                =>  $_GET['adjunto_numero'],
            );
            $this->db->insert('guia_transportista_adjuntos', $data_adjunto);
        }

        //////////DETALLE
        if(isset($_GET['producto_id']) && count($_GET['producto_id']) > 0){            
            for($i = 0; $i < count($_GET['producto_id']); $i++){
                //si no existe producto lo creo.
                $producto_id = $_GET['producto_id'][$i];                    

                if(($producto_id == '') || ($producto_id == 0)){
                    $data_producto = array(
                        'codigo_sunat'      =>  '-',
                        'codigo'            =>  '-',
                        'producto'          =>  $_GET['producto'][$i],
                        'stock_inicial'     =>  1,
                        'stock_actual'      =>  1,
                        'categoria_id'      =>  1,
                        'unidad_id'         =>  $_GET['unidad'][$i],
                        'fecha_insert'      =>  date("Y-m-d H:i:s"),
                        'empleado_insert'   =>  $this->session->userdata('empleado_id')                            
                    );
                    $this->productos_model->insertar($data_producto);                
                    $producto_id = $this->productos_model->max_producto_id();
                }

                $data_detalle = array(
                    'guia_transportista_id' => $guia_id,
                    'producto_id'           => $producto_id,
                    'cantidad'              => $_GET['cantidad'][$i],
                );
                $this->db->insert('guia_transportista_detalles', $data_detalle);
            }            
        }        
        
        $jsondata = array(
            'success'       =>  true,
            'message'       =>  'Operación correcta'
        );
        echo json_encode($jsondata, JSON_UNESCAPED_UNICODE);
    }

    public function modal_nuevo_producto(){
        $this->load->view('guia_transportistas/modal_nuevo_producto');
    }
    
    public function modal_nuevo_carro(){
        $this->load->view('guia_transportistas/modal_nuevo_carro');
    }
    
    public function modal_nuevo_conductor(){
        $this->load->view('guia_transportistas/modal_nuevo_conductor');
    }
    
    public function modal_detalle(){
        $this->load->view('guia_transportistas/modal_detalle');
    }
    
    public function pdf_a4($param_guia_id = '', $guardar_pdf = ''){
        $guia_id        = ($param_guia_id != '') ? $param_guia_id : $this->uri->segment(3);
        $guardar_pdf    = ($guardar_pdf != '') ? $guardar_pdf : $this->uri->segment(4);
        
        $data['empresa']        = $this->empresas_model->select(2);
        $data['cabecera']       = $this->guia_transportistas_model->query_standar_cabecera_ubigeos(2, '', array('gui.id' => $guia_id));
        $data['carros']         = $this->carros_model->select_3(3, $guia_id);
        $data['detalle']        = $this->guia_transportista_detalles_model->query_standar($guia_id);
        
        $chofer_entidad = $this->variables_diversas_model->tipo_entidades($data['cabecera']['chofer_tipo_entidad_id']);
        $data['cabecera']['chofer_tipo_entidad_id'] = $chofer_entidad[1];        
        
        $data['rutaqr'] = $this->GetImgQr($data['cabecera'], $data['empresa']);
        
        $html = $this->load->view("guia_transportistas/pdf_a4.php",$data,true);
        $this->pdf->loadHtml($html);
        $this->pdf->setPaper('A4', 'portrait');
        $this->pdf->render();
                
        $nombre_documento = $data['empresa']['ruc'].'-09-'.$data['cabecera']['serie'].'-'.$data['cabecera']['numero'];
        
        if($guardar_pdf == 1){
            $output = $this->pdf->output();
            file_put_contents('files/pdf/guia_transportistas/'.$nombre_documento.'.pdf', $output);
        }else{            
            $this->pdf->stream("$nombre_documento.pdf",
                array("Attachment"=>0)
            );
        }
        //////////////////////////////////////////
    }
    
    public function enviarSunat(){        
        $guia_id    = $this->uri->segment(3);        
        $guia       = $this->guia_transportistas_model->query_standar_cabecera(2, '', array('gui.id' => $guia_id));
        $detalle    = $this->guia_transportista_detalles_model->query_standar($guia_id);
        $carros     = $this->carros_model->select_3(3, $guia_id);
        $empresa    = $this->empresas_model->select(2, '', array('id' => 1));
        
        var_dump($guia);exit;
        
        //$venta_guias = $this->venta_guias_model->select_ventas($guia_id);
        $venta_guias = '';                        
        
        $nombre_archivo = $empresa['ruc'].'-31-'.$guia['serie'].'-'.$guia['numero'];
        $path = FCPATH."files/guia_electronica_transportista/";                
        
        $token_access = $this->token($empresa['guias_client_id'], $empresa['guias_client_secret'], $empresa['ruc'].$empresa['usu_secundario_produccion_user'], $empresa['usu_secundario_produccion_password']);        
        $numero_ticket = $guia['ticket_guia'];        
        
        if($guia['respuesta_sunat_codigo'] == null){
            $this->crear_files($empresa, $guia, $detalle, $carros, $venta_guias, $nombre_archivo, $path);
            $respuesta = $this->envio_xml($path.'FIRMA/', $nombre_archivo, $token_access);        
            $this->guia_transportistas_model->modificar($guia_id, array('ticket_guia' => $respuesta->numTicket));
            $numero_ticket = $respuesta->numTicket;
        }
        
        $respuesta_ticket = $this->envio_ticket($path.'CDR/', $numero_ticket, $token_access, $empresa['ruc'], $nombre_archivo);        
        $estado_operacion = ($respuesta_ticket['cdr_ResponseCode'] == '0') ? 1 : 0;        
        $data_modificar = array(
            'estado_operacion' => $estado_operacion, 
            'respuesta_sunat_codigo' => $respuesta_ticket['cdr_ResponseCode'],
            'respuesta_sunat_descripcion' => $respuesta_ticket['cdr_msj_sunat']
        );
        $this->guia_transportistas_model->modificar($guia_id, $data_modificar);
        
        $jsondata = array(
            'success'       =>  true,
            'message'       =>  $respuesta_ticket['cdr_msj_sunat'],
            'codigo'        =>  $respuesta_ticket['cdr_ResponseCode'],
            'error_existe'  =>  $respuesta_ticket['numerror']            
        );
        echo json_encode($jsondata, JSON_UNESCAPED_UNICODE);
    }
    
    function desarrollo_xml($empresa, $guia, $detalles, $carros, $venta_guia_transportistas){
            $xml =  '<?xml version="1.0" encoding="ISO-8859-1" standalone="no"?>
            <DespatchAdvice xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:qdt="urn:oasis:names:specification:ubl:schema:xsd:QualifiedDatatypes-2" xmlns:sac="urn:sunat:names:specification:ubl:peru:schema:xsd:SunatAggregateComponents-1" xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2" xmlns:udt="urn:un:unece:uncefact:data:specification:UnqualifiedDataTypesSchemaModule:2" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:ccts="urn:un:unece:uncefact:documentation:2" xmlns="urn:oasis:names:specification:ubl:schema:xsd:DespatchAdvice-2">
                    <ext:UBLExtensions>
                        <ext:UBLExtension>
                            <ext:ExtensionContent></ext:ExtensionContent>
                        </ext:UBLExtension>
                    </ext:UBLExtensions>';
                
            $xml .= '<cbc:UBLVersionID>2.1</cbc:UBLVersionID>
                    <cbc:CustomizationID>2.0</cbc:CustomizationID>
                    <cbc:ID>'.$guia['serie'].'-'.$guia['numero'].'</cbc:ID>
                    <cbc:IssueDate>'.$guia['fecha_emision'].'</cbc:IssueDate>
                    <cbc:IssueTime>'.date("H:i:s").'</cbc:IssueTime>
                    <cbc:DespatchAdviceTypeCode>31</cbc:DespatchAdviceTypeCode>
                    <cbc:Note><![CDATA['.$guia['observaciones'].']]></cbc:Note>';
            
            $xml .= '<!--  DOCUMENTOS ADICIONALES (Catalogo D41) -->
                    <cac:AdditionalDocumentReference>
                        <cbc:ID>'.$guia['adjunto_serie'].'-'.$guia['adjunto_numero'].'</cbc:ID>
                        <cbc:DocumentTypeCode listAgencyName="PE:SUNAT" listName="Documento relacionado al transporte" listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo61">'.$guia['adjunto_documento_codigo'].'</cbc:DocumentTypeCode>
                        <cbc:DocumentType>'.$guia['adjunto_tipo_documento'].'</cbc:DocumentType>
                        <cac:IssuerParty>
                            <cac:PartyIdentification>
                                <cbc:ID schemeID="'.$guia['remitente_codigo_tipo_entidad'].'" schemeAgencyName="PE:SUNAT" schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">'.$guia['remitente_numero_documento'].'</cbc:ID>
                            </cac:PartyIdentification>
                        </cac:IssuerParty>
                    </cac:AdditionalDocumentReference>';
            
            $xml .= '<cac:Signature>
                        <cbc:ID>'.$empresa['ruc'].'</cbc:ID>
                        <cac:SignatoryParty>
                            <cac:PartyIdentification>
                                <cbc:ID>'.$empresa['ruc'].'</cbc:ID>
                            </cac:PartyIdentification>
                            <cac:PartyName>
                                <cbc:Name><![CDATA['. utf8_decode($empresa['empresa']).']]></cbc:Name>
                            </cac:PartyName>
                        </cac:SignatoryParty>
                        <cac:DigitalSignatureAttachment>
                            <cac:ExternalReference>
                                <cbc:URI>'.$empresa['ruc'].'</cbc:URI>
                            </cac:ExternalReference>
                        </cac:DigitalSignatureAttachment>
                    </cac:Signature>';
                
            $xml .= '<cac:DespatchSupplierParty>
                        <cbc:CustomerAssignedAccountID schemeID="6">'.$empresa['ruc'].'</cbc:CustomerAssignedAccountID>
                        <cac:Party>
                            <cac:PartyIdentification>
                                <cbc:ID schemeID="6" schemeName="Documento de Identidad" schemeAgencyName="PE:SUNAT" schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">'.$empresa['ruc'].'</cbc:ID>
                            </cac:PartyIdentification>
                            <cac:PartyLegalEntity>
                                <cbc:RegistrationName><![CDATA['. utf8_decode($empresa['empresa']).']]></cbc:RegistrationName>
                            </cac:PartyLegalEntity>
                        </cac:Party>
                    </cac:DespatchSupplierParty>';
            
            //destinatario
            $xml .= '<cac:DeliveryCustomerParty>
                        <cac:Party>
                            <cac:PartyIdentification>
                                <cbc:ID schemeID="'.$guia['destinatario_codigo_tipo_entidad'].'" schemeName="Documento de Identidad" schemeAgencyName="PE:SUNAT" schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">'.$guia['destinatario_numero_documento'].'</cbc:ID>
                            </cac:PartyIdentification>
                            <cac:PartyLegalEntity>
                                <cbc:RegistrationName><![CDATA['.$guia['destinatario_entidad'].']]></cbc:RegistrationName>
                            </cac:PartyLegalEntity>
                        </cac:Party>
                    </cac:DeliveryCustomerParty>';
            
            $xml .= '<cac:Shipment>
                        <cbc:ID>SUNAT_Envio</cbc:ID>
                        <cbc:GrossWeightMeasure unitCode="KGM">'.$guia['peso_total'].'</cbc:GrossWeightMeasure>
                        <cac:ShipmentStage>
                            <cac:TransitPeriod>
                                <cbc:StartDate>'.$guia['fecha_traslado'].'</cbc:StartDate>
                            </cac:TransitPeriod>

                            <cac:CarrierParty>
                                <cac:PartyLegalEntity>
                                    <cbc:CompanyID>'.$guia['numero_mtc'].'</cbc:CompanyID>
                                </cac:PartyLegalEntity>
                            </cac:CarrierParty>

                            <cac:DriverPerson>
                                <cbc:ID schemeID="' . $guia['chofer_tipo_entidad_id'] . '">'.$guia['conductor_dni'].'</cbc:ID>
                                <cbc:FirstName><![CDATA['. utf8_decode($guia['conductor_nombres']).']]></cbc:FirstName>
                                <cbc:FamilyName><![CDATA['.$guia['conductor_apellidos'].']]></cbc:FamilyName>
                                <cbc:JobTitle>Principal</cbc:JobTitle>
                                <cac:IdentityDocumentReference>
                                    <cbc:ID>'.$guia['conductor_licencia'].'</cbc:ID>
                                </cac:IdentityDocumentReference>
                            </cac:DriverPerson>
                        </cac:ShipmentStage>
                        
                        <cac:Delivery>
                            <cac:DeliveryAddress>
                                <cbc:ID schemeName="Ubigeos" schemeAgencyName="PE:INEI">'.$guia['llegada_ubigeo'].'</cbc:ID>
                                <cac:AddressLine>
                                    <cbc:Line><![CDATA['.$guia['llegada'].']]></cbc:Line>
                                </cac:AddressLine>
                            </cac:DeliveryAddress>
                            <cac:Despatch>
                                <cac:DespatchAddress>
                                    <cbc:ID schemeName="Ubigeos" schemeAgencyName="PE:INEI">'.$guia['partida_ubigeo'].'</cbc:ID>
                                    <cac:AddressLine>
                                        <cbc:Line><![CDATA['.$guia['partida'].']]></cbc:Line>
                                    </cac:AddressLine>
                                </cac:DespatchAddress>                                                                

                                <cac:DespatchParty>
                                    <cac:PartyIdentification>
                                        <cbc:ID schemeID="'.$guia['remitente_codigo_tipo_entidad'].'" schemeName="Documento de Identidad" schemeAgencyName="PE:SUNAT" schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">'.$guia['remitente_numero_documento'].'</cbc:ID>
                                    </cac:PartyIdentification>
                                    <cac:PartyLegalEntity>
                                        <cbc:RegistrationName><![CDATA['.$guia['remitente_entidad'].']]></cbc:RegistrationName>
                                    </cac:PartyLegalEntity>
                                </cac:DespatchParty>
                            </cac:Despatch>
                        </cac:Delivery>
                        <cac:TransportHandlingUnit>
                            <cac:TransportEquipment>
                                <cbc:ID>' . $carros[0]['vehiculo_placa'] . '</cbc:ID>';
                                if(count($carros) > 1){
                            $xml .= '<cac:AttachedTransportEquipment>
                                        <cbc:ID>' . $carros[1]['vehiculo_placa'] . '</cbc:ID>
                                    </cac:AttachedTransportEquipment>';
                                }
                    $xml .= '</cac:TransportEquipment>
                        </cac:TransportHandlingUnit>
                    </cac:Shipment>';

                    $i = 1;
                    foreach($detalles as $values){
                    $xml .= '<cac:DespatchLine>
                            <cbc:ID>'.$i.'</cbc:ID>
                            <cbc:DeliveredQuantity unitCode="'.$values['codigo_unidad'].'" unitCodeListID="UN/ECE rec 20" unitCodeListAgencyName="United Nations Economic Commission for Europe">'.$values['cantidad'].'</cbc:DeliveredQuantity>
                            <cac:OrderLineReference>
                                <cbc:LineID>'.($i + 1).'</cbc:LineID>
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
    
    public function  firmar_xml($name_file, $entorno, $baja = ''){        
        $carpeta_baja = ($baja != '') ? 'BAJA/':'';
        $carpeta = "files/guia_electronica_transportista/$carpeta_baja";
        $dir = base_url().$carpeta."XML/".$name_file;
        $xmlstr = file_get_contents($dir);

        $domDocument = new \DOMDocument();
        $domDocument->loadXML($xmlstr);
        $factura  = new Factura();
        $xml = $factura->firmar($domDocument, '', $entorno);
        $content = $xml->saveXML();
        file_put_contents($carpeta."FIRMA/".$name_file, $content);
    }
    
    public function leerRespuestaSunat($nombre_archivo){
        $nombre = FCPATH."ws_sunat_guia\R-".$nombre_archivo;

        $resultado['respuesta_sunat_codigo'] = null;
        $resultado['respuesta_sunat_descripcion'] = null;
        if(file_exists($nombre)){
            $library = new SimpleXMLElement($nombre, null, true);
            
            $ns = $library->getDocNamespaces();
            $ext1 = $library->children($ns['cac']);
            $ext2 = $ext1->DocumentResponse;
            $ext3 = $ext2->children($ns['cac']);            
            $ext4 = $ext3->children($ns['cbc']);

            $resultado = array(
                'respuesta_sunat_codigo' => trim($ext4->ResponseCode),
                'respuesta_sunat_descripcion' => trim($ext4->Description)
            );
        }
        return $resultado;        
    }
    
    public function GetImgQr($cabecera, $empresa){        
        $textoQR = '';
        $textoQR .= $empresa['ruc']."|";//RUC EMPRESA
        
        $textoQR .= "Guía de Remisión Remitente|";//TIPO DE DOCUMENTO 
        $textoQR .= $cabecera['serie']."|";//SERIE
        $textoQR .= $cabecera['numero']."|";//NUMERO
        $textoQR .= "---|";//MTO TOTAL IGV
        $textoQR .= "---|";//MTO TOTAL DEL COMPROBANTE
        //$fechaEmision = (new DateTime($rsComprobante->fecha_de_emision))->format('d-m-Y');
        $textoQR .= $cabecera['fecha_emision']."|";//FECHA DE EMISION 
        //tipo de cliente
     
        $textoQR .= "6|";//TIPO DE DOCUMENTO ADQUIRENTE 
        $textoQR .= $cabecera['numero_documento']."|";//NUMERO DE DOCUMENTO ADQUIRENTE         

        $nombreQR = '31-'.$cabecera['serie'].'-'.$cabecera['numero'];
        QRcode::png($textoQR, FCPATH."images/qr/guias/".$nombreQR.".png", QR_ECLEVEL_L, 10, 2);
        
        return FCPATH."images/qr/guias/{$nombreQR}.png";
    }

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
    
                //extraemos archivo zip a xml
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

    function crear_files($empresa, $guia, $detalle, $carros, $venta_guias, $nombre_archivo, $path){        
        $xml = $this->desarrollo_xml($empresa, $guia, $detalle, $carros, $venta_guias);        
        $archivo = fopen($path."XML/".$nombre_archivo.".xml", "w+");
        fwrite($archivo, $xml);
        fclose($archivo);
        
        $this->firmar_xml($nombre_archivo.".xml", $empresa['modo']);                
        $zip = new ZipArchive();
        if($zip->open($path."FIRMA/".$nombre_archivo.".zip", ZipArchive::CREATE) === true){
            $zip->addFile($path."FIRMA/".$nombre_archivo.".xml", $nombre_archivo.".xml");
        }
        return $nombre_archivo;
    }   
}