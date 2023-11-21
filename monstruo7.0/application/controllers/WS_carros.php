<?PHP
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class WS_carros extends CI_Controller {
    
    public function __construct() {        
        parent::__construct();
        $this->load->model('variables_diversas_model');
        $this->load->model('carros_model');
    }
    
    public function select_item(){
        $carro_id = $this->uri->segment(3);
        if(isset($carro_id) && ($carro_id != '')){
            $jsondata = $this->carros_model->ws_item($carro_id);
            echo json_encode($jsondata);
        }
    }
    
    public function ws_select_all(){
        $data = $this->carros_model->select(3, array('id', 'marca', 'modelo', 'placa', 'numero_mtc'), array('fecha_delete' => 'IS '.'NULL'));
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    
    public function ws_select(){                       
        $pagina = $this->uri->segment(3);
        $filas_por_pagina = $this->uri->segment(4);        
        $carro_id = $this->uri->segment(5);
        
        $condicion = array();
        $condicion = ($carro_id != $this->variables_diversas_model->param_stand_url) ? array_merge($condicion, array('id' => '='.$carro_id)) : $condicion;
                        
        $data = $this->carros_model->ws_select($pagina, $filas_por_pagina, $condicion, ' ORDER BY id desc');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    
    public function select_3(){
        $data   =   $this->carros_model->select_3(3, $this->uri->segment(3));
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function buscador_carro() {
        $param = $this->input->get('term');
        $data = $this->carros_model->ws_buscador($param);
        echo json_encode($data);
    }

    public function delete_item(){
        $carro_id = $this->uri->segment(3);
        if(isset($carro_id) && ($carro_id != '')){
            $data = array(
                'fecha_delete' => date("Y-m-d H:i:s")
            );
            $this->carros_model->modificar($carro_id, $data);
            $jsondata = array(
                'msg' => 'operación correcta'
            );
            echo json_encode($jsondata);
        }
    }

    public function select_registro(){
        $data = $this->carros_model->select(2, '', array('id' => $this->uri->segment(3)));
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    
    public function ws_update_campo_item(){
        $carro_id = $this->uri->segment(3);
        $field = $this->uri->segment(4);
        $value = $this->uri->segment(5);
        
        if(isset($carro_id) && ($carro_id != '')){            
            $data = array($field => $value);            
            $jsondata = $this->carros_model->modificar($carro_id, $data);
            echo json_encode($jsondata);
        }
    }
}