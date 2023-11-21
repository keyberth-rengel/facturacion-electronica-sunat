<?PHP
if(!defined('BASEPATH')) exit ('No direct script access allowed');

class Choferes_model extends CI_Model{

    public $tabla = 'choferes';

    public function __construct() {
        parent::__construct();            
    }

    public function select($modo, $select = array(), $condicion = array(), $order = '') {

        if ($select == '')
            $select = array();
        if ($condicion == '')
            $condicion = array();

        $where = '';
        foreach ($condicion as $key => $value) {
            if ($value == 'IS NULL') {
                $where .= " AND $key " . $value;
            } else {
                $where .= " AND $key = '" . $value . "' ";
            }
        }

        $campos = ($select == array()) ? '*' : implode(", ", $select);
        $sql = "SELECT " . $campos . " FROM $this->tabla WHERE 1 = 1 " . $where . " " . $order;
        $query = $this->db->query($sql);

        switch ($modo) {
            case '1':
                $resultado = '';
                if ($query->num_rows() > 0) {
                    $row = $query->row_array();
                    $resultado = $row[$campos];
                }
                return $resultado;

            case '2':
                $row = array();
                if ($query->num_rows() > 0) {
                    $row = $query->row_array();
                }
                return $row;

            case '3':
                $rows = array();
                foreach ($query->result_array() as $row) {
                    $rows[] = $row;
                }
                return $rows;
        }
    }
    
    public function select2($modo, $select = array(), $condicion = array(), $order = '') {

        if ($select == '')
            $select = array();
        if ($condicion == '')
            $condicion = array();

        $where = '';
        foreach ($condicion as $key => $value) {
            $where .= " AND $key $value ";
        }

        $campos = ($select == array()) ? '*' : implode(", ", $select);
        $sql = "SELECT " . $campos . " FROM $this->tabla WHERE 1 = 1 " . $where . " " . $order;
        $query = $this->db->query($sql);

        switch ($modo) {
            case '1':
                $resultado = '';
                if ($query->num_rows() > 0) {
                    $row = $query->row_array();
                    $resultado = $row[$campos];
                }
                return $resultado;

            case '2':
                $row = array();
                if ($query->num_rows() > 0) {
                    $row = $query->row_array();
                }
                return $row;

            case '3':
                $rows = array();
                foreach ($query->result_array() as $row) {
                    $rows[] = $row;
                }
                return $rows;
        }
    }

    public function insertar($data, $mensaje = '') {
        $this->db->insert($this->tabla, $data);   
    }           

    public function modificar($id, $data) {
        $this->db->where('id', $id);
        $this->db->update($this->tabla, $data);
    }
    
    public function ws_buscador($buscar) {
        $sql = "SELECT id chofer_id, nombres, apellidos "
                . "FROM $this->tabla "
                . "WHERE (nombres LIKE '%$buscar%') OR (apellidos LIKE '%$buscar%')"
                . "ORDER BY apellidos";

        $query = $this->db->query($sql);

        $rows = array();        
        foreach ($query->result_array() as $tsArray){
            $rows[] = array(
                "value" => $tsArray['nombres'] . " " . $tsArray['apellidos'],
                "id" => $tsArray['chofer_id']
            );
        }
        return $rows;
    }
    
    public function select_choferes($pagina, $filas_por_pagina, $condicion = array(), $order = '') {
        if ($condicion == '')
        $condicion = array();

        $where = '';
        foreach ($condicion as $key => $value) {            
            $where .= " AND $key $value ";
        }
        if($order == '')
        $order = ' ORDER BY id desc ';
        $inicio = ($pagina - 1)*$filas_por_pagina;
        $sql = "SELECT 
        id chofer_id, nombres, apellidos, numero_documento, licencia
        FROM $this->tabla ent        
        WHERE 1 = 1 AND fecha_delete IS NULL " . $where . " " . $order . "
        LIMIT $inicio, $filas_por_pagina";

        $query = $this->db->query($sql);
        $rows = array();
        foreach ($query->result_array() as $row) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function total_filas($condicion) {
        if ($condicion == '')
        $condicion = array();

        $where = '';
        foreach ($condicion as $key => $value) {            
            $where .= " AND $key $value ";
        }        
        $sql = "select count(id) total_filas from $this->tabla
        WHERE 1 = 1 AND fecha_delete IS NULL " . $where;
        $query = $this->db->query($sql);

        $resultado = 0;
        if ($query->num_rows() > 0) {
            $row = $query->row_array();
            $resultado = $row['total_filas'];
        }
        return $resultado;
    }    

    public function ws_select($pagina, $filas_por_pagina, $condicion = array(), $order = ''){
        $data = $this->select_choferes($pagina, $filas_por_pagina, $condicion, $order);
        $total_filas = $this->total_filas($condicion);

        $datos = array();
        foreach ($data as $value){
            $datos[] = array(
                'chofer_id'         =>  (int)$value['chofer_id'],
                'nombres'           =>  $value['nombres'],
                'apellidos'         =>  $value['apellidos'],
                'numero_documento'  =>  $value['numero_documento'],
                'licencia'          =>  $value['licencia'],
            );
        }

        $salida = array(
            'ws_select_choferes'  => $datos,
            'total_filas'       => $total_filas
        );
        return $salida;
    }
    
    public function ws_item($chofer_id){
        $data = $this->select(2, '', array('id' => $chofer_id));
        $datos[] = array(
            'chofer_id'         =>  $data['id'],
            'nombres'           =>  $data['nombres'],
            'apellidos'         =>  $data['apellidos'],
            'numero_documento'  =>  $data['numero_documento'],
            'tipo_entidad_id'   =>  $data['tipo_entidad_id'],
            'licencia'          =>  $data['licencia'],
        );
        return $datos;        
    }
    
    public function ws_select_all(){
        $data = $this->select(3, array('id', 'nombres'), array('fecha_delete' => '0'));

        $datos = array();
        foreach ($data as $value){
            $datos[] = array(
                'id'    => (int)$value['id'],
                'nombres' =>$value['nombres'],
            );
        }
        return $datos;
    }
    
    public function format($data){
        $datos = array();
        foreach ($data as $value){
            $datos[$value['nombres']] = $value['id'];
        }
        return $datos;
    }
    
    public function select_max_id(){
        $sql = "SELECT MAX(id) maximo_id FROM $this->tabla";
        $query = $this->db->query($sql);
        $resultado = 0;
        if ($query->num_rows() > 0) {
            $row = $query->row_array();
            $resultado = $row['maximo_id'];
        }
        return $resultado;
    }

}