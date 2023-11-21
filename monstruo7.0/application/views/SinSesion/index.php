<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/TR/REC-html40" lang="en">
    <head>
        <title>Sistema</title>
        
        <!--<meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">--> 
        
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">      
        <link rel="shortcut icon" type="image/x-icon" href="<?PHP echo base_url();?>images/siti01.ico" />       
        <link rel="stylesheet" href="<?PHP echo base_url();?>assets/bootstrap/css/bootstrap.min.css">
        <link rel="stylesheet" href="<?PHP echo base_url()?>assets/plugins/chosen/chosen.css">
        <link rel="stylesheet" href="<?PHP echo base_url();?>assets/css/themes-smoothness-jquery-ui.css">         
        <link rel="stylesheet" href="<?PHP echo base_url();?>assets/css/jquery.toast.min.css">         
        <link rel="stylesheet" href="<?PHP echo base_url();?>assets/css/jquery-confirm.min.css">         
        <link rel="stylesheet" href="<?PHP echo base_url();?>assets/css/style_hector.css">  
        <!-- custom css -->
        <link rel="stylesheet" href="<?PHP echo base_url();?>assets/css/custom.css">        
          
        <script src="<?PHP echo base_url(); ?>assets/js/jquery-2.2.4.min.js"></script> 
        <script src="<?PHP echo base_url()?>assets/plugins/chosen/chosen.jquery.js"></script>
        <script src="<?PHP echo base_url(); ?>assets/js/jquery-ui-1.11.0.js"></script>        
        <script src="<?PHP echo base_url(); ?>assets/js/jquery.toast.min.js"></script>        
        <script src="<?PHP echo base_url(); ?>assets/js/jquery-confirm.min.js"></script>        
        <script src="<?PHP echo base_url(); ?>assets/js/function_dashboard.js"></script>
        <script src="<?PHP echo base_url(); ?>assets/js/chart.min.js"></script>
        <script src="<?PHP echo base_url();?>assets/js/monstruo/config.js"></script>

        <style type="text/css" >
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { 
          -webkit-appearance: none; 
          margin: 0; 
        }
        input[type=number] { -moz-appearance:textfield; }
        </style>                                                                                       
    </head>
    <body>
        <div class="row">                                
            <div class="col-sm-1">
                <select class="form-control form-control-sm" id="partida_departamento_id" name="partida_departamento_id" required="">
                </select>                                     
            </div>
            <div class="col-sm-1">
                <select class="form-control form-control-sm" id="partida_provincia_id" name="partida_provincia_id" required="">                                        
                </select>
            </div>
            <div class="col-sm-1">
                <select class="form-control form-control-sm" id="partida_distrito_id" name="partida_distrito_id" required="">
                </select>
            </div>
            <div class="col-sm-12">
                <input type="text" placeholder="Dirección Punto Partida" name="partida_direccion" id="partida_direccion" class="form-control" required="">
            </div> 
        </div> 
        
        
        
        <script type="text/javascript">
            var cadena_departamento;    

            $.getJSON(base_url + 'index.php/WS_guia_transportistas/cargaDepartamentos')
                .done(function (data) {
                    cadena_departamento = "<option value=''>Seleccionar</option>";
                    (data.departamentos).forEach(function (repo) {
                        cadena_departamento += "<option value='" + repo.id + "'>" + repo.departamento + "</option>";
                    });
                    $('#partida_departamento_id').html(cadena_departamento);
            });
            
            var cadena_ubigeo;
            $("#partida_departamento_id").on("change", function(){
                $('#partida_provincia_id option').remove();
                var departamento_id = $("#partida_departamento_id").val();
                var url_provincias = base_url + 'index.php/WS_guia_transportistas/cargaProvincias/'+departamento_id;
                
                $("#partida_direccion").val('url_provincias:' + url_provincias);
                
                $.getJSON(url_provincias)
                .done(function (data) {
                    cadena_ubigeo = "<option value=''>Seleccionar</option>";
                    (data.provincias).forEach(function (repo) {
                        cadena_ubigeo += "<option value='" + repo.id + "'>" + repo.provincia + "</option>";
                    });
                    $('#partida_provincia_id').html(cadena_ubigeo);
                });
            });

            $("#partida_provincia_id").on("change", function(){
                $('#partida_distrito_id option').remove();
                var provincia_id = $("#partida_provincia_id").val();
                var url_distrito = base_url + 'index.php/WS_guia_transportistas/cargaDistritos/'+provincia_id;
                $.getJSON(url_distrito)
                .done(function (data) {
                    cadena_ubigeo = "<option value=''>Seleccionar</option>";
                    (data.distritos).forEach(function (repo) {
                        cadena_ubigeo += "<option value='" + repo.id + "'>" + repo.distrito + "</option>";
                    });
                    $('#partida_distrito_id').html(cadena_ubigeo);
                });        
            });
        </script>




        <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
        
        <!-- Include all compiled plugins (below), or include individual files as needed -->        
        <script src="<?PHP echo base_url();?>assets/bootstrap/js/bootstrap.min.js"></script> 
        <div class="modal fade" id="myModal" tabindex="-1" role="dialog">
        </div>	
    </body>
</html>