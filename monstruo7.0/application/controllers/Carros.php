<?PHP
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Carros extends CI_Controller {

    public function __construct() {        
        parent::__construct();
        date_default_timezone_set('America/Lima');
        
        $this->load->model('accesos_model');
        $this->load->model('carros_model');
        $this->load->model('variables_diversas_model');
        $this->load->library('pdf');        
        $this->load->helper('ayuda');

        $empleado_id = $this->session->userdata('empleado_id');
        if (empty($empleado_id)) {
            $this->session->set_flashdata('mensaje', 'No existe sesion activa');
            redirect(base_url());
        }
    }
    
    public function index(){
        $this->accesos_model->menuGeneral();
        $this->load->view('carros/index');
        $this->load->view('templates/footer');
    }
    
    public function modal_operacion(){
        $this->load->view('carros/modal_operacion');
    }
    
    public function operaciones(){
        $data = array(
            'marca'         =>  $_GET['marca'],
            'modelo'        =>  $_GET['modelo'],
            'placa'         =>  $_GET['placa'],
            'numero_mtc'    =>  $_GET['numero_mtc'],
            'fecha_insert'  =>  date("Y-m-d H:i:s"),
        );
        
        if(isset($_GET['carro_id']) && ($_GET['carro_id'] != '')){
            $this->carros_model->modificar($_GET['carro_id'], $data);
        }else{
            $this->carros_model->insertar($data);
        }

        $jsondata = array(
            'success'   =>  true,
            'message'   =>  'Operación correcta'
        );
        echo json_encode($jsondata);
    }
    
    public function insert_max_id(){
        $data = array(
            'marca'         =>  $_GET['marca'],
            'modelo'        =>  $_GET['modelo'],
            'placa'         =>  $_GET['placa'],
            'numero_mtc'    =>  $_GET['numero_mtc'],
            'fecha_insert'  =>  date("Y-m-d H:i:s"),
        );
        $this->carros_model->insertar($data);

        $maximo_id = $this->carros_model->select_max_id();
        echo json_encode(array($maximo_id));
    }
}