<?PHP
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class WS_choferes extends CI_Controller {
    
    public function __construct() {        
        parent::__construct();
        $this->load->model('variables_diversas_model');
        $this->load->model('choferes_model');
    }
    
    public function select_item(){
        $chofer_id = $this->uri->segment(3);
        if(isset($chofer_id) && ($chofer_id != '')){
            $jsondata = $this->choferes_model->ws_item($chofer_id);
            echo json_encode($jsondata);
        }
    }
    
    public function ws_select_all(){
        $data = $this->choferes_model->select(3, array('id', 'nombres', 'apellidos'), array('fecha_delete' => 'IS '.'NULL'));
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    
    public function ws_select(){                       
        $pagina = $this->uri->segment(3);
        $filas_por_pagina = $this->uri->segment(4);        
        $chofer_id = $this->uri->segment(5);
        
        $condicion = array();
        $condicion = ($chofer_id != $this->variables_diversas_model->param_stand_url) ? array_merge($condicion, array('id' => '='.$chofer_id)) : $condicion;
                        
        $data = $this->choferes_model->ws_select($pagina, $filas_por_pagina, $condicion, ' ORDER BY id desc');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    
    public function buscador_chofer() {
        $param = $this->input->get('term');
        $data = $this->choferes_model->ws_buscador($param);
        echo json_encode($data);
    }
    
    public function delete_item(){
        $chofer_id = $this->uri->segment(3);
        if(isset($chofer_id) && ($chofer_id != '')){
            $data = array(
                'fecha_delete' => date("Y-m-d H:i:s")
            );
            $this->choferes_model->modificar($chofer_id, $data);
            $jsondata = array(
                'msg' => 'operación correcta'
            );
            echo json_encode($jsondata);
        }
    }

    public function select_registro(){
        $data = $this->choferes_model->select(2, '', array('id' => $this->uri->segment(3)));
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    
    public function ws_update_campo_item(){
        $chofer_id = $this->uri->segment(3);
        $field = $this->uri->segment(4);
        $value = $this->uri->segment(5);
        
        if(isset($chofer_id) && ($chofer_id != '')){            
            $data = array($field => $value);            
            $jsondata = $this->choferes_model->modificar($chofer_id, $data);
            echo json_encode($jsondata);
        }
    }
}