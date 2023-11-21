<?PHP
if (!defined('BASEPATH'))
    exit('No direct script access allowed');
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;

class WS_correos extends CI_Controller {
    
    public function __construct() {        
        parent::__construct();
        date_default_timezone_set('America/Lima');
        
        $this->load->model('correos_model');
        $this->load->model('ventas_model');
        $this->load->model('guias_model');
        $this->load->model('guia_detalles_model');
        $this->load->model('venta_guias_model');
        $this->load->model('empresas_model');
        $this->load->model('entidades_model');
        $this->load->model('tipo_documentos_model');
        
        require_once (APPPATH .'libraries/qr/phpqrcode/qrlib.php');
    }
    
    public function select_all(){       
        $data = $this->correos_model->select(2,'','');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    
    public function ws_select(){
        $correo_id = $this->uri->segment(3);       
        $data = $this->correos_model->select(2, '', array('id' => $correo_id));
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    
    public function Send_Mail(){
        require_once (APPPATH .'libraries/phpMailer/Exception.php');
        require_once (APPPATH .'libraries/phpMailer/PHPMailer.php');
        require_once (APPPATH .'libraries/phpMailer/SMTP.php');
                
        $documento_id = $this->uri->segment(3);
        $venta_guia = $this->uri->segment(4);//1 ventas, 2 guia_remision, 3 guia_transportista                
        
        $correo = $this->correos_model->select(2);
        $empresa = $this->empresas_model->select(2);
        
        $guardar_pdf = 1;
        switch ($venta_guia) {
            //ventas facturas-boletas-notas credito-notas debito            
            case "1":
                $documento = $this->ventas_model->select(2,'',array('id' => $documento_id));
                $tipo_documento_codigo = $this->tipo_documentos_model->select2(1, array('codigo'), array('id' => $documento['tipo_documento_id']));        
                $entidad = $this->entidades_model->select(2, '', array('id' => $documento['entidad_id']));                
                                                
                $this->ventas_model->pdf_a4($documento_id, $guardar_pdf);
                $file_pdf = "files/pdf/ventas/" .$empresa['ruc'].'-'.$tipo_documento_codigo.'-'. $documento['serie'] .'-'. $documento['numero'] . ".pdf";
                $file_xml = FCPATH."files/facturacion_electronica/XML/" .$empresa['ruc'].'-'.$tipo_documento_codigo.'-'. $documento['serie'] .'-'. $documento['numero'] . ".xml";
                $file_cdr = FCPATH."files/facturacion_electronica/FIRMA/R-" .$empresa['ruc'].'-'.$tipo_documento_codigo.'-'. $documento['serie'] .'-'. $documento['numero'] . ".zip";                
                break;
            //guias remitente
            case "2":
                $documento = $this->guias_model->select(2,'',array('id' => $documento_id));
                $tipo_documento_codigo = '09';
                $entidad = $this->entidades_model->select(2, '', array('id' => $documento['destinatario_id']));                
                                
                $this->pdf_a4($documento_id, 1);
                $file_pdf = "files/pdf/guias_remitente/" .$empresa['ruc'].'-'.$tipo_documento_codigo.'-'. $documento['serie'] .'-'. $documento['numero'] . ".pdf";
                $file_xml = FCPATH."files/guia_electronica/FIRMA/" .$empresa['ruc'].'-'.$tipo_documento_codigo.'-'. $documento['serie'] .'-'. $documento['numero'] . ".xml";
                $file_cdr = FCPATH."files/guia_electronica/CDR/R-" .$empresa['ruc'].'-'.$tipo_documento_codigo.'-'. $documento['serie'] .'-'. $documento['numero'] . ".zip";                
                break;
            //guias transportista
            case "3":
                
                break;

        }                
        
        $mail = new PHPMailer;
        $mail->isSMTP();

        $mail->Host = $correo['host'];
        $mail->Port = $correo['port'];
        $mail->SMTPSecure = $correo['correo_cifrado'];

        $mail->SMTPAuth = true;
        $mail->Username = $correo['user'];
        $mail->Password = $correo['pass'];
                
        if ($entidad['email_1']=='') {
            sendJsonData(['status'=>STATUS_FAIL,'msg'=>'El cliente no tiene correo']);
            exit();
        }

        //echo $comprobante['cli_email'];exit;
        $mail->setFrom($correo['user'], utf8_decode($empresa['empresa']."xyz"));
        $mail->AddAddress($entidad['email_1'],'Facturacion Electronica');

        //asunto
        $mail->Subject = 'Comprobante Electronico '. ' - ' . $entidad['entidad'];
        
        $body = '<h2>Comprobante de Pago Electrónico</h2>';
        $body .= 'Estimado Cliente, '. '<br>';
        $body .= 'Sr(es). '.$entidad['entidad']. '<br>';
        $body .= 'RUC '. $entidad['numero_documento']. '<br>';
        $body .= 'Adjuntamos Guia Electrónica: '.$documento['serie'].'-'.$documento['numero']. '<br><br>';
        
        if($correo['notas'] != null && $correo['notas'] != ''){
            $body .= '<br>'. $correo['notas']. '<br><br><br>';
        }
        
        $body .= 'Saluda atentamente,<br>';
        $body .= '<b>' . $empresa['empresa'] .' </b><br><br>';

        $mail->AltBody = 'ALT BODY';
        $mail->Body = $body;
        
        //echo $file_xml;
        $mail->addAttachment($file_pdf);
        $mail->addAttachment($file_xml);
        $mail->addAttachment($file_cdr);

        //send the message, check for errors
        $respuesta = (!$mail->send()) ? 'Mailer Error: '. $mail->ErrorInfo : 'Mensaje enviado--!';
        
        $jsondata = array(
            'success'       =>  $respuesta
        );
        echo json_encode($jsondata, JSON_UNESCAPED_UNICODE);
    }

    public function pdf_a4($param_guia_id = '', $guardar_pdf = ''){
        $guia_id        = ($param_guia_id != '') ? $param_guia_id : $this->uri->segment(3);
        $guardar_pdf    = ($guardar_pdf != '') ? $guardar_pdf : $this->uri->segment(4);

        $empresa        = $this->empresas_model->select(2);
        $cabecera       = $this->guias_model->query_standar_cabecera_ubigeos(2, '', array('gui.id' => $guia_id));
        $detalle        = $this->guia_detalles_model->query_standar($guia_id);
        $venta_guias    = $this->venta_guias_model->select_ventas($guia_id);
        $rutaqr         = $this->GetImgQr($cabecera, $empresa);

        $this->guias_model->pdf_a4($guardar_pdf, $empresa, $cabecera, $detalle, $venta_guias, $rutaqr);
    }

    public function GetImgQr($cabecera, $empresa)  {
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
        
        $nombreQR = '9-'.$cabecera['serie'].'-'.$cabecera['numero'];
        QRcode::png($textoQR, FCPATH."images/qr/guias/".$nombreQR.".png", QR_ECLEVEL_L, 10, 2);
        
        return FCPATH."images/qr/guias/{$nombreQR}.png";
    }

}