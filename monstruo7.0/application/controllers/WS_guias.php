<?PHP
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class WS_guias extends CI_Controller {
    
    public function __construct() {        
        parent::__construct();
        $this->load->model('guias_model');
        $this->load->model('guia_detalles_model');
        $this->load->model('variables_diversas_model');
        $this->load->model('ubigeo_departamentos_model');
        $this->load->helper('ayuda');
    }
    
    public function ws_select(){
        $pagina = $this->uri->segment(3);
        $filas_por_pagina = $this->uri->segment(4);
        $entidad_id = $this->uri->segment(5);
        $guia_serie = $this->uri->segment(6);
        $guia_numero = $this->uri->segment(7);
        $fecha_emision_inicio = $this->uri->segment(8);
        $fecha_emision_final = $this->uri->segment(9);
        
        $condicion = array();
        $condicion = ($entidad_id != $this->variables_diversas_model->param_stand_url) ? array_merge($condicion, array('ent.id' => '='.$entidad_id)) : $condicion;
        $condicion = ($guia_serie != $this->variables_diversas_model->param_stand_url) ? array_merge($condicion, array('gui.serie' => '='."'".$guia_serie."'")) : $condicion;
        $condicion = ($guia_numero != $this->variables_diversas_model->param_stand_url) ? array_merge($condicion, array('gui.numero' => '='.$guia_numero)) : $condicion;
        $condicion = (($fecha_emision_inicio != $this->variables_diversas_model->param_stand_url) && ($fecha_emision_final == $this->variables_diversas_model->param_stand_url)) ? array_merge($condicion, array('gui.fecha_emision' => '>='."'".format_fecha_0000_00_00($fecha_emision_inicio)."'")) : $condicion;
        $condicion = (($fecha_emision_inicio == $this->variables_diversas_model->param_stand_url) && ($fecha_emision_final != $this->variables_diversas_model->param_stand_url)) ? array_merge($condicion, array('gui.fecha_emision' => '<='."'".format_fecha_0000_00_00($fecha_emision_final)."'")) : $condicion;
        $condicion = (($fecha_emision_inicio != $this->variables_diversas_model->param_stand_url) && ($fecha_emision_final != $this->variables_diversas_model->param_stand_url)) ? array_merge($condicion, array('gui.fecha_emision' => 'BETWEEN '."'".format_fecha_0000_00_00($fecha_emision_inicio)."' AND "."'".format_fecha_0000_00_00($fecha_emision_final)."'")) : $condicion;        

        $data = $this->guias_model->ws_select($pagina, $filas_por_pagina, $condicion, $order = '');        
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    
    public function select_entidad(){
        $entidad_id = $this->uri->segment(3);
        $data = $this->guias_model->select(3, '', array('destinatario_id' => $entidad_id), ' ORDER BY id DESC');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    
    public function cargaDepartamentos(){        
        $data['departamentos'] = $this->ubigeo_departamentos_model->select(3, '', '', ' ORDER BY id ASC');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);        
    }
    
    public function cargaProvincias(){        
        $data['provincias'] = $this->ubigeo_departamentos_model->select_pronvincias($this->uri->segment(3));
        echo json_encode($data, JSON_UNESCAPED_UNICODE);        
    }
    
    public function cargaDistritos(){        
        $data['distritos'] = $this->ubigeo_departamentos_model->select_distritos($this->uri->segment(3));
        echo json_encode($data, JSON_UNESCAPED_UNICODE);        
    }
        
    public function ws_cabecera(){
        $guia_id = $this->uri->segment(3);
        $data = $this->guias_model->query_cabecera($guia_id);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }    
    
    public function api_guias(){
        $guia_id = $this->uri->segment(3);
        
        $datos_empresa = $this->empresas_model->query_api();
        $empresa['ruc']                                 = $datos_empresa['ruc'];
        $empresa['razon_social']                        = $datos_empresa['razon_social'];
        $empresa['nombre_comercial']                    = $datos_empresa['nombre_comercial'];        
        $empresa['domicilio_fiscal']                    = $datos_empresa['domicilio_fiscal'];
        $empresa['ubigeo']                              = $datos_empresa['ubigeo'];
        $empresa['urbanizacion']                        = $datos_empresa['urbanizacion'];
        $empresa['distrito']                            = $datos_empresa['distrito'];
        $empresa['provincia']                           = $datos_empresa['provincia'];
        $empresa['departamento']                        = $datos_empresa['departamento'];                
        $empresa['modo']                                = $datos_empresa['modo'];        //1 beta, 2 produccion
        $empresa['usu_secundario_produccion_user']      = $datos_empresa['usu_secundario_produccion_user'];
        $empresa['usu_secundario_produccion_password']  = $datos_empresa['usu_secundario_produccion_password'];
        $empresa['guias_client_id']                     = $datos_empresa['guias_client_id'];
        $empresa['guias_client_secret']                 = $datos_empresa['guias_client_secret'];
        
        //var_dump($empresa);exit;
        
        $datos_comprobante = $this->guias_model->query_standar_cabecera($guia_id);
        
        $guia['serie']                      = $datos_comprobante['serie'];
        $guia['numero']                     = $datos_comprobante['numero'];
        $guia['fecha_emision']              = $datos_comprobante['fecha_emision'];
        $guia['fecha_traslado']             = $datos_comprobante['fecha_traslado'];
        $guia['guia_motivo_traslado_id']    = $datos_comprobante['guia_motivo_traslado_id'];
        $guia['guia_modalidad_traslado_id'] = $datos_comprobante['guia_modalidad_traslado_id'];
        $guia['entidad_id_transporte']      = $datos_comprobante['entidad_id_transporte'];
        $guia['numero_mtc_transporte']      = $datos_comprobante['numero_mtc_transporte'];
        
        $guia['conductor_dni']              = $datos_comprobante['conductor_dni'];
        $guia['conductor_nombres']          = $datos_comprobante['conductor_nombres'];
        $guia['conductor_apellidos']        = $datos_comprobante['conductor_apellidos'];
        $guia['conductor_licencia']         = $datos_comprobante['conductor_licencia'];
        $guia['vehiculo_placa']             = $datos_comprobante['vehiculo_placa'];
        
        $guia['destinatario_tipo']          = $this->variables_diversas_model->tipo_documento($datos_comprobante['tipo_entidad_id']);
        $guia['destinatario_numero_documento']  = $datos_comprobante['numero_documento'];
        $guia['destinatario_nombres_razon']     = $datos_comprobante['entidad'];
        
        $guia['partida_ubigeo']                 = $datos_comprobante['partida_ubigeo'];
        $guia['partida_direccion']              = $datos_comprobante['partida_direccion'];
        
        $guia['llegada_ubigeo']                 = $datos_comprobante['llegada_ubigeo'];
        $guia['llegada_direccion']              = $datos_comprobante['llegada_direccion'];
        
        $guia['peso_total']                     = $datos_comprobante['peso_total'];
        $guia['numero_bultos']                  = $datos_comprobante['numero_bultos'];
        $guia['notas']                          = $datos_comprobante['notas'];
        
        $detalle = $this->guia_detalles_model->query_api($guia_id);        
        $indice = 0;
        foreach ($detalle as $value_detalle){
            $items[$indice]['cantidad']     = $value_detalle['cantidad'];
            $items[$indice]['descripcion']  = $value_detalle['producto'];
            $items[$indice]['codigo']       = $value_detalle['producto_codigo'];            
            $indice ++;
        }        

        $jsondata = array(
            'empresa'   =>  $empresa,
            'guia'      =>  $guia,
            'items'     =>  $items
        );
        echo json_encode($jsondata, JSON_UNESCAPED_UNICODE);
    }
    
    public function solicito_token(){                
        $datos_empresa = $this->empresas_model->select(2, array('guias_client_id', 'guias_client_secret', 'ruc', 'usu_secundario_produccion_user', 'usu_secundario_produccion_password'));
                
        $post = [
            'guias_client_id'                       => $datos_empresa['guias_client_id'],
            'guias_client_secret'                   => $datos_empresa['guias_client_secret'],
            'ruc'                                   => $datos_empresa['ruc'],
            'usu_secundario_produccion_user'        => $datos_empresa['usu_secundario_produccion_user'],
            'usu_secundario_produccion_password'    => $datos_empresa['usu_secundario_produccion_password']
        ];
        //var_dump($post);exit;

        $ch = curl_init('https://facturacionintegral.com/aplicaciones_sistemas/API_SUNAT_GUIAS/1_solicito_token.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

        $response = curl_exec($ch);
        curl_close($ch);
        echo ($response);
    }
    
    public function envio_xml_recibe_ticket(){
        //$guia_id = $this->uri->segment(3);
        $guia_id = 1;
        
        $datos_empresa = $this->empresas_model->query_api();
        $empresa['ruc']                                 = $datos_empresa['ruc'];
        $empresa['razon_social']                        = $datos_empresa['razon_social'];
        $empresa['nombre_comercial']                    = $datos_empresa['nombre_comercial'];        
        $empresa['domicilio_fiscal']                    = $datos_empresa['domicilio_fiscal'];
        $empresa['ubigeo']                              = $datos_empresa['ubigeo'];
        $empresa['urbanizacion']                        = $datos_empresa['urbanizacion'];
        $empresa['distrito']                            = $datos_empresa['distrito'];
        $empresa['provincia']                           = $datos_empresa['provincia'];
        $empresa['departamento']                        = $datos_empresa['departamento'];                
        $empresa['modo']                                = 1;        //1 beta, 2 produccion
        $empresa['usu_secundario_produccion_user']      = $datos_empresa['usu_secundario_produccion_user'];
        $empresa['usu_secundario_produccion_password']  = $datos_empresa['usu_secundario_produccion_password'];
        $empresa['guias_client_id']                     = $datos_empresa['guias_client_id'];
        $empresa['guias_client_secret']                 = $datos_empresa['guias_client_secret'];        
        //var_dump($empresa);exit;
        
        $datos_comprobante = $this->guias_model->query_standar_cabecera(2, '', array('gui.id' => $guia_id));
        //var_dump($datos_comprobante);exit;
        
        $guia['serie']                      = $datos_comprobante['serie'];
        $guia['numero']                     = $datos_comprobante['numero'];
        $guia['fecha_emision']              = $datos_comprobante['fecha_emision_sf'];
        $guia['fecha_traslado']             = $datos_comprobante['fecha_traslado_sf'];
        $guia['guia_motivo_traslado_id']    = $datos_comprobante['guia_motivo_traslado_id'];
        $guia['guia_modalidad_traslado_id'] = $datos_comprobante['guia_modalidad_traslado_id'];
        $guia['entidad_id_transporte']      = $datos_comprobante['entidad_id_transporte'];
        $guia['numero_mtc_transporte']      = $datos_comprobante['numero_mtc_transporte'];
        
        $guia['conductor_dni']              = $datos_comprobante['conductor_dni'];
        $guia['conductor_nombres']          = $datos_comprobante['conductor_nombres'];
        $guia['conductor_apellidos']        = $datos_comprobante['conductor_apellidos'];
        $guia['conductor_licencia']         = $datos_comprobante['conductor_licencia'];
        $guia['vehiculo_placa']             = $datos_comprobante['vehiculo_placa'];
        
        $guia['destinatario_tipo']          = $this->variables_diversas_model->tipo_documento($datos_comprobante['tipo_entidad_id']);
        $guia['destinatario_numero_documento']  = $datos_comprobante['numero_documento'];
        $guia['destinatario_nombres_razon']     = $datos_comprobante['entidad'];
        
        $guia['partida_ubigeo']                 = $datos_comprobante['partida_ubigeo'];
        $guia['partida_direccion']              = $datos_comprobante['partida_direccion'];
        
        $guia['llegada_ubigeo']                 = $datos_comprobante['llegada_ubigeo'];
        $guia['llegada_direccion']              = $datos_comprobante['llegada_direccion'];
        
        $guia['peso_total']                     = $datos_comprobante['peso_total'];
        $guia['numero_bultos']                  = $datos_comprobante['numero_bultos'];
        $guia['notas']                          = $datos_comprobante['notas'];
        
        
        
        $detalle = $this->guia_detalles_model->query_api($guia_id);
        $indice = 0;
        $items = array();
        foreach ($detalle as $value_detalle){
            $items[$indice]['cantidad']         = $value_detalle['cantidad'];
            $items[$indice]['descripcion']      = $value_detalle['producto'];
            $items[$indice]['codigo']           = $value_detalle['producto_codigo'];
            $items[$indice]['codigo_unidad']    = $value_detalle['codigo_unidad'];
            $indice ++;
        }

        $data = array(
            'empresa'   =>  $empresa,
            'guia'      =>  $guia,
            'items'     =>  $items,
            'token'     =>  'eyJraWQiOiJhcGkuc3VuYXQuZ29iLnBlLmtpZDAwMSIsInR5cCI6IkpXVCIsImFsZyI6IlJTMjU2In0.eyJzdWIiOiIxMDQ4MTIxMTY0MSIsImF1ZCI6Ilt7XCJhcGlcIjpcImh0dHBzOlwvXC9hcGktY3BlLnN1bmF0LmdvYi5wZVwiLFwicmVjdXJzb1wiOlt7XCJpZFwiOlwiXC92MVwvY29udHJpYnV5ZW50ZVwvZ2VtXCIsXCJpbmRpY2Fkb3JcIjpcIjFcIixcImd0XCI6XCIxMDAwMDBcIn1dfV0iLCJ1c2VyZGF0YSI6eyJudW1SVUMiOiIxMDQ4MTIxMTY0MSIsInRpY2tldCI6IjExMzUyMTA5NDY1MzkiLCJucm9SZWdpc3RybyI6IiIsImFwZU1hdGVybm8iOiIiLCJsb2dpbiI6IjEwNDgxMjExNjQxTVVOTzIwMTUiLCJub21icmVDb21wbGV0byI6IlNBTkNIRVogTVXDkU9aIEFOR0VMIEpFU1VTIiwibm9tYnJlcyI6IlNBTkNIRVogTVXDkU9aIEFOR0VMIEpFU1VTIiwiY29kRGVwZW5kIjoiMDAyMyIsImNvZFRPcGVDb21lciI6IiIsImNvZENhdGUiOiIiLCJuaXZlbFVPIjowLCJjb2RVTyI6IiIsImNvcnJlbyI6IiIsInVzdWFyaW9TT0wiOiJNVU5PMjAxNSIsImlkIjoiIiwiZGVzVU8iOiIiLCJkZXNDYXRlIjoiIiwiYXBlUGF0ZXJubyI6IiIsImlkQ2VsdWxhciI6bnVsbCwibWFwIjp7ImlzQ2xvbiI6ZmFsc2UsImRkcERhdGEiOnsiZGRwX251bXJ1YyI6IjEwNDgxMjExNjQxIiwiZGRwX251bXJlZyI6IjAwMjMiLCJkZHBfZXN0YWRvIjoiMDAiLCJkZHBfZmxhZzIyIjoiMDAiLCJkZHBfdWJpZ2VvIjoiMTUwMTE5IiwiZGRwX3RhbWFubyI6IjAzIiwiZGRwX3Rwb2VtcCI6IjAyIiwiZGRwX2NpaXUiOiIzNjEwNCJ9LCJpZE1lbnUiOiIxMTM1MjEwOTQ2NTM5Iiwiam5kaVBvb2wiOiJwMDAyMyIsInRpcFVzdWFyaW8iOiIxIiwidGlwT3JpZ2VuIjoiSVQiLCJwcmltZXJBY2Nlc28iOnRydWV9fSwibmJmIjoxNjg0MjAyOTg2LCJjbGllbnRJZCI6IjI1Y2UzNDQxLWYzMGQtNGQwYS1iMTgxLTEwMDczNWVkZjI4MCIsImlzcyI6Imh0dHBzOlwvXC9hcGktc2VndXJpZGFkLnN1bmF0LmdvYi5wZVwvdjFcL2NsaWVudGVzc29sXC8yNWNlMzQ0MS1mMzBkLTRkMGEtYjE4MS0xMDA3MzVlZGYyODBcL29hdXRoMlwvdG9rZW5cLyIsImV4cCI6MTY4NDIwNjU4NiwiZ3JhbnRUeXBlIjoicGFzc3dvcmQiLCJpYXQiOjE2ODQyMDI5ODZ9.XGqh4w8A-yWD_tEiYlP5OEak3Sa_o2mxGIXq155slXIq9xLhE2ZcpzdSwuTqIzO7wa3e4QknX-Waa-e7MXPTrYVvRbxpmUAknkA7Dfp656JvnZz2kOWNMY0k5-DUGe8jbh9cTsN2MlMiBorN9J2gXicMBp0FHbEnslSU4X4vSHz6WlEZhdmcchT7FCufMiX6bJc86YIdqnBK5ymKJJNGA24UNhzUs2y_H3Nj3UHWkEi87_EPqgSXCvxBik55yCLN8e3mwwx5jkLkURnCPF7pynXFo5VMQPgxC1nTwa6NifIsakzWYn11uF0jxAGNiqA69anA4VDkwWm_mEWYKewjbA'
        );        
        //var_dump($data);exit;

        $ch = curl_init('https://facturacionintegral.com/aplicaciones_sistemas/API_SUNAT_GUIAS/2_envio_xml_recibe_ticket.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        curl_close($ch);
        
        var_dump($response);exit;
    }
    
    public function envio_ticket() {        
        $post = [
            'ticket'        =>  '646f77cc-10b6-4f08-9011-dec48d1ac415',
            'token_access'  =>  'eyJraWQiOiJhcGkuc3VuYXQuZ29iLnBlLmtpZDAwMSIsInR5cCI6IkpXVCIsImFsZyI6IlJTMjU2In0.eyJzdWIiOiIxMDQ4MTIxMTY0MSIsImF1ZCI6Ilt7XCJhcGlcIjpcImh0dHBzOlwvXC9hcGktY3BlLnN1bmF0LmdvYi5wZVwiLFwicmVjdXJzb1wiOlt7XCJpZFwiOlwiXC92MVwvY29udHJpYnV5ZW50ZVwvZ2VtXCIsXCJpbmRpY2Fkb3JcIjpcIjFcIixcImd0XCI6XCIxMDAwMDBcIn1dfV0iLCJ1c2VyZGF0YSI6eyJudW1SVUMiOiIxMDQ4MTIxMTY0MSIsInRpY2tldCI6IjE3ODIxMjgxMDkzNSIsIm5yb1JlZ2lzdHJvIjoiIiwiYXBlTWF0ZXJubyI6IiIsImxvZ2luIjoiMTA0ODEyMTE2NDFNVU5PMjAxNSIsIm5vbWJyZUNvbXBsZXRvIjoiU0FOQ0hFWiBNVcORT1ogQU5HRUwgSkVTVVMiLCJub21icmVzIjoiU0FOQ0hFWiBNVcORT1ogQU5HRUwgSkVTVVMiLCJjb2REZXBlbmQiOiIwMDIzIiwiY29kVE9wZUNvbWVyIjoiIiwiY29kQ2F0ZSI6IiIsIm5pdmVsVU8iOjAsImNvZFVPIjoiIiwiY29ycmVvIjoiIiwidXN1YXJpb1NPTCI6Ik1VTk8yMDE1IiwiaWQiOiIiLCJkZXNVTyI6IiIsImRlc0NhdGUiOiIiLCJhcGVQYXRlcm5vIjoiIiwiaWRDZWx1bGFyIjpudWxsLCJtYXAiOnsiaXNDbG9uIjpmYWxzZSwiZGRwRGF0YSI6eyJkZHBfbnVtcnVjIjoiMTA0ODEyMTE2NDEiLCJkZHBfbnVtcmVnIjoiMDAyMyIsImRkcF9lc3RhZG8iOiIwMCIsImRkcF9mbGFnMjIiOiIwMCIsImRkcF91YmlnZW8iOiIxNTAxMTkiLCJkZHBfdGFtYW5vIjoiMDMiLCJkZHBfdHBvZW1wIjoiMDIiLCJkZHBfY2lpdSI6IjUwNTA2In0sImlkTWVudSI6IjE3ODIxMjgxMDkzNSIsImpuZGlQb29sIjoicDAwMjMiLCJ0aXBVc3VhcmlvIjoiMSIsInRpcE9yaWdlbiI6IklUIiwicHJpbWVyQWNjZXNvIjp0cnVlfX0sIm5iZiI6MTY3OTI3OTI5MCwiY2xpZW50SWQiOiIyNWNlMzQ0MS1mMzBkLTRkMGEtYjE4MS0xMDA3MzVlZGYyODAiLCJpc3MiOiJodHRwczpcL1wvYXBpLXNlZ3VyaWRhZC5zdW5hdC5nb2IucGVcL3YxXC9jbGllbnRlc3NvbFwvMjVjZTM0NDEtZjMwZC00ZDBhLWIxODEtMTAwNzM1ZWRmMjgwXC9vYXV0aDJcL3Rva2VuXC8iLCJleHAiOjE2NzkyODI4OTAsImdyYW50VHlwZSI6InBhc3N3b3JkIiwiaWF0IjoxNjc5Mjc5MjkwfQ.iHYMPs2yKJjH9YV6Px9ACST-W4WZlPtfdeUy1GEwRlNwWTO19ZTBqUJfdmrF8lg_opv0zRlL9iCAzyKf2Iy79r6810TiY6k5kSmRNUef-BXtBHo0ZgkL2xUAKjalZwgU1Hg5Dp5AqhK4IWtFRbPH6Qcu7nnXCfTU8yC4js28_8DHvMiOxNaqj07CrCMTenE34wGEDj9K9Stj9BCHirZEhF8tBGg1TDo-s6N_BO2EjC_6IP1rRn-2HHHHDM4ir4ez8eaKmdX4LPF5E8_spn3krUdViPLOqowB5YJ4GCGT3l-SRazHSei26p_oJRpjuP8qp_MPkU5M-lianIn0h8eeXg',
            'ruc'           =>  '10481211641',
            'serie'         =>  'TV50',
            'numero'        =>  '1',
        ];
        var_dump($post);exit;

        $ch = curl_init('https://facturacionintegral.com/aplicaciones_sistemas/API_SUNAT_GUIAS/3_envio_ticket.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $response = curl_exec($ch);

        curl_close($ch);
        echo ($response);                
    }
    
}