<?PHP
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class SinSesion extends CI_Controller {

    public function __construct() {        
        parent::__construct();
        date_default_timezone_set('America/Lima');
        
        $this->load->model('accesos_model');
        $this->load->model('carros_model');
        $this->load->model('variables_diversas_model');
        $this->load->library('pdf');        
        $this->load->helper('ayuda');
    }
    
    public function index(){        
        $this->load->view('SinSesion/index');
    }
    
    public function modal_operacion(){
        $this->load->view('carros/modal_operacion');
    }
    
    
}