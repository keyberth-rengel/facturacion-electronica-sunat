<?PHP
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Choferes extends CI_Controller {

    public function __construct() {        
        parent::__construct();
        date_default_timezone_set('America/Lima');
        
        $this->load->model('accesos_model');
        $this->load->model('choferes_model');
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
        $this->load->view('choferes/index');
        $this->load->view('templates/footer');
    }
    
    public function modal_operacion(){
        $this->load->view('choferes/modal_operacion');
    }
    
    public function operaciones(){
        $data = array(
            'nombres'           =>  $_GET['nombres'],
            'apellidos'         =>  $_GET['apellidos'],
            'numero_documento'  =>  $_GET['numero_documento'],
            'tipo_entidad_id'   =>  $_GET['tipo_entidad_id'],
            'licencia'          =>  $_GET['licencia'],
            'fecha_insert'      =>  date("Y-m-d H:i:s"),
        );

        if(isset($_GET['chofer_id']) && ($_GET['chofer_id'] != '')){
            $this->choferes_model->modificar($_GET['chofer_id'], $data);
        }else{
            $this->choferes_model->insertar($data);
        }

        $jsondata = array(
            'success'   =>  true,
            'message'   =>  'Operación correcta'
        );
        echo json_encode($jsondata);
    }
    
    public function insert_max_id(){
        $data = array(
            'nombres'           =>  $_GET['nombres'],
            'apellidos'         =>  $_GET['apellidos'],
            'numero_documento'  =>  $_GET['numero_documento'],
            'tipo_entidad_id'   =>  $_GET['tipo_entidad_id'],
            'licencia'          =>  $_GET['licencia'],
            'fecha_insert'      =>  date("Y-m-d H:i:s"),
        );

        $this->choferes_model->insertar($data);
        
        $maximo_id = $this->choferes_model->select_max_id();
        echo json_encode(array($maximo_id));
    }

    public function select_max_id(){
        $maximo_id = $this->choferes_model->select_max_id();
        echo json_encode(array($maximo_id));
    }
}