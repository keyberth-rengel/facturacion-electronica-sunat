<?php
if(!defined('BASEPATH')) exit ('No direct script access allowed');

class Variables_diversas_model extends CI_Model {

    public $tabla                           = 'variables_diversas';
    public $detraccion_valor                = 700;
    public $porcentaje_detraccion           = 0.12; //igual al 10%  -- en 02-04-2018 se cambio al 12%
    public $porcentaje_detraccion_entero    = 12; //igual al 10%  -- en 02-04-2018 se cambio al 12%
    public $porcentaje_valor_igv            = 0.18;
    public $factura_antigua                 = "factura_antigua"; //igual al 10%
    public $boleta_antigua                  = "boleta_antigua"; //igual al 10%
    public $catidad_decimales               = 4;
    public $param_stand_url                 = 'x-m-1-23-43';
    public $tipo_documento_defecto_id       = 3;// 1 Factura, 3 boleta, 7 Nota de crédito, 8 Nota de débito.
    public $valor_para_ventas_con_anticipo  = '1001';
    public $productos_automaticos           = 0;//escribir productos directamente a la hora de vender(sin ingresar al almacen)(0 no automatico.Se debe ingresar a almacen -- 1 automatico)
    public $datos_accesorios                = 0;//datos q van en ventas como: Número de Guia // Condición de venta // Nota de venta // Número de Pedido // Orden Com

//    public $UBLVersionID = "2.1";
//    public $CustomizationID = "2.0";
    
    public function __construct() {
        parent::__construct();
    }
    
    //servira para poner rutas a los XML y CDR de las GUIAS    
    public function path_ruta_file(){
        return base_url() . "files/guia_electronica/FIRMA/";
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
    
    public function modificar($id, $data) {        
        $this->db->where('id', $id);
        $this->db->update($this->tabla, $data);
    }
    
    //entorno: 0 beta, 1 produccion
    //documento: 1 facturas, boletas, 9 guias
    public function web_service_sunat($entorno, $documento){
        if($entorno == 0){
            if($documento == 1){
                $web_service = 'https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService?wsdl';
            }elseif($documento == 9){
                $web_service = 'https://e-beta.sunat.gob.pe/ol-ti-itemision-guia-gem-beta/billService?wsdl';
            }
        }elseif($entorno = 1){
            if($documento == 1){
                $web_service = 'https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService?wsdl';
            }elseif($documento == 9){
                $web_service = 'https://e-guiaremision.sunat.gob.pe/ol-ti-itemision-guia-gem/billService?wsdl';
            }
        }        
    }

    public function UBLVersionID(){
        return "2.1";
    }
    
    public function CustomizationID(){
        return "2.0";
    }
    
    //solo en caso de grabada(1000) y gratuita(9996) el igv es 18% en inafecto, exonerada y exportacion sera 0.00
    public function percent_igv($codigo_de_tributo){
        $percent_igv = 0.00;
        if(($codigo_de_tributo == 1000) || ($codigo_de_tributo == 9996)){
            $percent_igv = $this->porcentaje_valor_igv;
        }
        return $percent_igv;
    }
    
    //precio base x IGV (seha el caso) + el impuesto a la bosa. ambos en unidad 1.
    public function priceAmount($precio_base, $codigo_de_tributo, $percent, $icbper, $descuento = 0){
        $precio_base = floatval($precio_base);
        $codigo_de_tributo = floatval($codigo_de_tributo);
        $percent = floatval($percent);
        $icbper = floatval($icbper);
        $priceAmount = '';
        if($codigo_de_tributo == 1000){
            $priceAmount = number_format((($precio_base - $descuento) * ( 1 + $percent)),2, '.', '') + $icbper;            
        }else{
            $priceAmount = $precio_base - $descuento + $icbper;
        }
        return $priceAmount;
    }
    
    // TaxAmount impuesto total por item (por todas las cantidades) sin considerar el impuesto a la bolsa
    public function taxAmount($cantidad, $precio_base, $codigo_de_tributo, $percent, $descuento = 0){
        $taxAmount = '';        
        switch ($codigo_de_tributo) {
            case 1000:
              $taxAmount = $cantidad* ($precio_base - $descuento) *$percent;
              break;
            case 9995:
              $taxAmount = 0.0;
              break;
            case 9996:
              $taxAmount = $cantidad*($precio_base/(1 + $percent))*$percent;
              break;
            case 9997:
              $taxAmount = 0.0;
              break;
            case 9998:
              $taxAmount = 0.0;
              break;
        }        
        return $taxAmount;        
    }
    
    public function operaciones($operacion_id){
        $operacion = '';        
        switch ($operacion_id) {
            case 1:
              $operacion = 'ventas';
              break;
            case 2:
              $operacion = 'Pedidos';
              break;
            case 3:
              $operacion = 'cotizaciones';
              break;
        }        
        return $operacion; 
    }
    
    public function operaciones_compras($operacion_id){
        $operacion = '';        
        switch ($operacion_id) {
            case 1:
              $operacion = 'compras';
              break;
            case 2:
              $operacion = 'Orden de compra';
              break;            
        }        
        return $operacion; 
    }

    public function impuesto_bolsa($anio){        
        $monto = 0.00;
        switch ($anio) {
            case 2019:
              $monto = 0.10;
              break;
            case 2020:
              $monto = 0.20;
              break;
            case 2021:
              $monto = 0.30;
              break;
            case 2022:
              $monto = 0.40;
              break;
            default:
              $monto = 0.50;
        }
        return $monto;
    }
    
    public function price_priceAmount($precio_base, $codigo_de_tributo){
        $price_priceAmount = ($codigo_de_tributo == 9996) ? 0.0 : $precio_base;
        return $price_priceAmount; 
    }
    
    public function tipo_de_operacion(){
        $tipo_de_operacion = array(
            array('codigo'  => '0101',
                'operacion' => 'Venta Interna'
                ),
            array('codigo'      => '0200',
                    'operacion' => 'Exportación'
                ),
            array('codigo'      => $this->valor_para_ventas_con_anticipo,
                    'operacion' => 'Ventas con anticipo'
                )
        );       
        return $tipo_de_operacion;
    }
    
    public function datos_configuracion($url_base, $anio){        
        $data = array(
            'url_base'                      =>  $url_base,
            'porcentaje_valor_igv'          =>  $this->porcentaje_valor_igv,
            'catidad_decimales'             =>  $this->catidad_decimales,
            'impuesto_bolsa'                =>  $this->impuesto_bolsa($anio),
            'param_stand_url'               =>  $this->param_stand_url,
            'tipo_documento_defecto_id'     =>  $this->tipo_documento_defecto_id,
            'codigo_ventas_con_anticipos'   =>  $this->valor_para_ventas_con_anticipo
        );
        return $data;
    }
    
    public function meses($id = ''){
        $meses = array(
            '1'     => 'enero',
            '2'     => 'febrero',
            '3'     => 'marzo',
            '4'     => 'abril',
            '5'     => 'mayo',
            '6'     => 'junio',
            '7'     => 'julio',
            '8'     => 'agosto',
            '9'     => 'septiembre',
            '10'    => 'octubre',
            '11'    => 'noviembre',
            '12'    => 'diciembre'
        );
        $salida = ($id != '') ? $meses[$id] : $meses;
        return $salida;
    }
    
    function dimension_proporcion($ruta_imagen, $tamano_x){
        $imagen = getimagesize($ruta_imagen);    //Sacamos la información
        $ancho = $imagen[0];              //Ancho
        $alto = $imagen[1];
        
        $tamano_y = ($tamano_x * $alto) / $ancho;        
        return $tamano_y;
    }
    
    public function tipo_documento($tipo_documento_id) {
        switch ($tipo_documento_id) {
            case "1":
                $codigo = 1;
                break;
            case "2":
                $codigo = 6;
                break;
            case "3":
                $codigo = 0;
                break;
            case "4":
                $codigo = 4;
                break;
            case "5":
                $codigo = 7;
                break;

            default:
                break;
        }
        return $codigo;
    }
    
    public function tipo_entidades($tipo_entidad_id) {
        $codigo = '';
        $entidad = '';
        switch ($tipo_entidad_id) {
            case "1":
                $codigo = 1;
                $entidad = 'DNI';
                break;
            case "2":
                $codigo = 6;
                $entidad = 'RUC';
                break;
            case "3":
                $codigo = 0;
                $entidad = 'Empresas Del Extranjero - No Domiciliado';
                break;
            case "4":
                $codigo = 4;
                $entidad = 'Carnet de Extranjeria';
                break;
            case "5":
                $codigo = 7;
                $entidad = 'Pasaporte';
                break;
            
            case "7":
                $codigo = 'F';
                $entidad = 'Permiso Temporal de Permanencia - PTP';
                break;

            default:
                break;
        }
        return array($codigo, $entidad);
    }
    
    public function obtener_estructura_directorios($ruta){
        // Se comprueba que realmente sea la ruta de un directorio        
        $array_ruta = array();
        if (is_dir($ruta)){            
            // Abre un gestor de directorios para la ruta indicada
            $gestor = opendir($ruta);

            // Recorre todos los elementos del directorio
            while (($archivo = readdir($gestor)) !== false)  {
                $ruta_completa = $ruta . "/" . $archivo;

                // Se muestran todos los archivos y carpetas excepto "." y ".."
                if ($archivo != "." && $archivo != "..") {                    
                    // Si es un directorio se recorre recursivamente
                    if (is_dir($ruta_completa)) {
                        //echo "<li>" . $archivo . "</li>";
                        $array_ruta = array_merge($array_ruta, array($archivo));
                        obtener_estructura_directorios($ruta_completa);
                    } else {                        
                        $array_ruta = array_merge($array_ruta, array($archivo));
                    }
                }
            }

            // Cierra el gestor de directorios
            closedir($gestor);
        }
        return $array_ruta;
    }

}