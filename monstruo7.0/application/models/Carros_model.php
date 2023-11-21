<?PHP
if(!defined('BASEPATH')) exit ('No direct script access allowed');

class Carros_model extends CI_Model{

    public $tabla = 'carros';

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
    
    public function select_3($modo, $guia_transportista_id, $order = '') {

        $sql = "SELECT 
        car.id carro_id, car.placa vehiculo_placa, car.numero_mtc vehiculo_mtc, car.modelo vehiculo_modelo, car.marca vehiculo_marca
        FROM `guia_transportista_carros` gca
        JOIN carros car ON car.`id` = gca.`carro_id` WHERE guia_transportista_id = " . $guia_transportista_id . " ORDER BY gca.id ASC";
        
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
        $sql = "SELECT id carro_id, marca, modelo, placa, numero_mtc "
                . "FROM $this->tabla "
                . "WHERE (placa LIKE '%$buscar%') OR (marca LIKE '%$buscar%') OR (modelo LIKE '%$buscar%')"
                . "ORDER BY placa";

        $query = $this->db->query($sql);

        $rows = array();        
        foreach ($query->result_array() as $tsArray){
            $rows[] = array(
                "value" => $tsArray['placa'].' '.$tsArray['marca'] . '-' .$tsArray['modelo'],
                "id" => $tsArray['carro_id']                
            );
        }
        return $rows;
    }
    
    public function select_carros($pagina, $filas_por_pagina, $condicion = array(), $order = '') {
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
        id carro_id, marca, modelo, placa, numero_mtc
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
        $data = $this->select_carros($pagina, $filas_por_pagina, $condicion, $order);
        $total_filas = $this->total_filas($condicion);

        $datos = array();
        foreach ($data as $value){
            $datos[] = array(
                'carro_id'      =>  (int)$value['carro_id'],
                'marca'         =>  $value['marca'],
                'modelo'        =>  $value['modelo'],
                'placa'         =>  $value['placa'],
                'numero_mtc'    =>  $value['numero_mtc'],
            );
        }

        $salida = array(
            'ws_select_carros'  => $datos,
            'total_filas'       => $total_filas
        );
        return $salida;
    }
    
    public function ws_item($carro_id){
        $data = $this->select(2, '', array('id' => $carro_id));
        $datos[] = array(
            'carro_id'      =>  $data['id'],
            'marca'         =>  $data['marca'],
            'modelo'        =>  $data['modelo'],
            'placa'         =>  $data['placa'],
            'numero_mtc'    =>  $data['numero_mtc'],
        );
        return $datos;        
    }
    
    public function ws_select_all(){
        $data = $this->select(3, array('id', 'placa', 'numero_mtc'), array('fecha_delete' => 'IS '.'NULL'));

        $datos = array();
        foreach ($data as $value){
            $datos[] = array(
                'id'            => $value['id'],
                'marca'         => $value['marca'],
                'modelo'        => $value['modelo'],
                'placa'         => $value['placa'],
                'numero_mtc'    => $value['numero_mtc']
            );
        }
        return $datos;
    }
    
    public function format($data){
        $datos = array();
        foreach ($data as $value){
            $datos[$value['placa']] = $value['id'];
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