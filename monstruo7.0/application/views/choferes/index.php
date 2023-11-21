<style>    
    .derecha_text { 
        text-align: right; 
    }
    .centro_text { 
        text-align: center; 
    }
    .tamanio_pequenio{
        font-size: 15px;
    }
    .text_capital { 
        text-transform:capitalize; 
    } 
</style>
<h2 align="center">Choferes</h2>
<br>
<div class="container">
    <div class="row">
        <div class="col-md-4">
            <button id="btn_nuevo_chofer" class="btn btn-success btn-sm" data-toggle="modal" data-target="#myModal" data-backdrop="static" data-keyboard="false">Nuevo Chofer</button>
        </div>

        <div class="col-md-1" >
        </div> 
        <div class="col-md-2" >        
        </div> 
        <div class="col-md-4">
            <input type="text" class="form-control form-control-sm" id="chofer" placeholder="Buscar Chofer">
            <input type="hidden" id="chofer_id" />
        </div>   

        <div class="col-md-1" >
            <button class="btn btn-default" type="button" id="btn_buscar_chofer" name="btn_buscar_chofer"><span class="glyphicon glyphicon-search"></span></button>   
        </div>                   
    </div>
    <br>
    <div class="row-fluid">
        <table role="grid" style="height: auto;" id="tabla_chofer_id" class="table table-bordered table-responsive table-hover">
            <thead>
                <tr>
                    <th>N.</th>
                    <th>Nombres</th>
                    <th>Apellidos</th>
                    <th>N. documento</th>
                    <th>Licencia</th>
                    <th class="centro_text"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span></th>
                    <th class="centro_text"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></th>                    
                </tr>
            </thead>
            <tbody role="rowgroup">
                
            </tbody>
        </table>
    </div>    
    
    <div id='div_contenedor'>
        <ul id="lista_id_pagination" class="pagination lista_paginacion">
        </ul>
    </div>
</div>

<script src="<?PHP echo base_url(); ?>assets/js/monstruo/help.js"></script>
<script type="text/javascript">
    var base_url = '<?php echo base_url();?>';
    var total_filas = 0;
    var filas_por_pagina = 10;
    var pagina_inicial = 1;
    let datos_configuracion = JSON.parse(localStorage.getItem("datos_configuracion"));    
    var param_stand_url = datos_configuracion.param_stand_url;
    var modal_chofer_id;
    var chofer_id = '';
    
    $("#tipo_entidad_id").empty();
    
    $(document).ready(function(){
        //BUSCAR filtros
        $('#btn_buscar_chofer').on('click', function(){
            pagina = 1; //
            param_chofer_id = ($('#chofer_id').val() == '') ? param_stand_url :  $('#chofer_id').val();
            $("#tabla_chofer_id > tbody").remove();

            var ruta_url = base_url + 'index.php/WS_choferes/ws_select/' + pagina + '/' + filas_por_pagina + '/' + param_chofer_id;
            console.log(ruta_url);
            $.getJSON(ruta_url)
                .done(function (data) {
                    console.log(data);
                    carga = 1;//se usa para activar la pagina N. 1
                    total_filas = data.total_filas;
                    $("#lista_id_pagination > li").remove();
                    construir_paginacion(total_filas, filas_por_pagina, carga)
                    
                    var numero_orden = filas_por_pagina*(pagina-1)+1;
                    (data.ws_select_choferes).forEach(function (repo) {
                        agregarFila(numero_orden, repo.nombres, repo.apellidos, repo.chofer_id, repo.numero_documento, repo.licencia);
                        numero_orden ++;
                    });
            });
        });
        
        //PAGINACION
        $('#div_contenedor').on('click', '.pajaro', function(){
            param_chofer_id = ($('#chofer_id').val() == '') ? param_stand_url :  $('#chofer_id').val();
            
            $('li').removeClass('active');
            $(this).parent().addClass('active');
                        
            pagina = $(this).text();
            $("#tabla_chofer_id > tbody").remove();

            var url_l = base_url + 'index.php/WS_choferes/ws_select/' + pagina + '/' + filas_por_pagina + '/' + param_chofer_id;
            $.getJSON(url_l)
                .done(function (data) {

                    total_filas = data.total_filas; 
                    var numero_orden = filas_por_pagina*(pagina-1)+1;
                    (data.ws_select_choferes).forEach(function (repo) {
                        agregarFila(numero_orden, repo.nombres, repo.apellidos, repo.chofer_id, repo.numero_documento, repo.licencia);
                        numero_orden ++;
                    });
            });            
        });                                                                
        
        //EDITAR
        $("#tabla_chofer_id").on('click', '.btn_modificar_chofer', function(){
            chofer_id = $(this).attr('id');
            ruta_url = base_url + 'index.php/choferes/modal_operacion/';
            $("#myModal").load(ruta_url);
        });
        
        $("#tabla_chofer_id").on('click', '.btn_eliminar_chofer', function(){            
            var chofer_id = $(this).attr('id');            
            var x = confirm("Desea eliminar este chofer:");
            if (x){ 
                ruta_url_item = base_url + 'index.php/WS_choferes/delete_item/' + chofer_id;
                $.getJSON(ruta_url_item)
                .done(function (data){
                    console.log('elimiación correcta');
            
                    $("#tabla_chofer_id > tbody").remove();
                    $("#lista_id_pagination > li").remove();
                    carga_inicial();
                });                                                        
            }
        });
        
        //subir imagen
        $("#tabla_chofer_id").on('click', '.btn_imagen', function(){
            modal_chofer_id = $(this).attr('id');
            $("#myModal").load(base_url + 'index.php/choferes/modal_imagen');
        });
    });
    
    $("#btn_nuevo_chofer").click(function(){
        chofer_id = '';
        $("#myModal").load('<?php echo base_url()?>index.php/choferes/modal_operacion');
    }); 
    
    $('#chofer').autocomplete({
        source: base_url + 'index.php/WS_choferes/buscador_chofer',
        minLength: 2,
        select: function (event, ui) {
            $('#chofer_id').val(ui.item.id);
        }
    });
    
    carga_inicial();
    
    function carga_inicial(){
        //CARGA INICIAL
        var url_l = base_url + 'index.php/WS_choferes/ws_select/' + pagina_inicial + '/' + filas_por_pagina + '/' + param_stand_url ;
        console.log(url_l);
        $.getJSON(url_l)
            .done(function (data) {
                carga = 1;//solo se usa al cargar la página, para activar la pagina N. 1
                total_filas = data.total_filas;
                construir_paginacion(total_filas, filas_por_pagina, carga);

                var numero_orden = 1;
                (data.ws_select_choferes).forEach(function (repo) {
                    agregarFila(numero_orden, repo.nombres, repo.apellidos, repo.chofer_id, repo.numero_documento, repo.licencia);
                    numero_orden ++;
                });
        });
    }    
   
    function agregarFila(numero_orden, nombres, apellidos, chofer_id, numero_documento, licencia){
        var fila = '<tr class="seleccionado tabla_fila">';
        fila += '<td align="center">'+numero_orden+'</td>';
        fila += '<td>'+nombres+'</td>';
        fila += '<td>'+apellidos+'</td>';
        fila += '<td>'+numero_documento+'</td>';
        fila += '<td>'+licencia+'</td>';
        fila += '<td align="center"><a id="'+chofer_id+'" class="btn btn-default btn-xs btn_modificar_chofer" data-toggle="modal" data-target="#myModal"><i class="glyphicon glyphicon-pencil"></i></a></td>';
        fila += '<td align="center"><a id="'+chofer_id+'" class="btn btn-danger btn-xs btn_eliminar_chofer"><i class="glyphicon glyphicon-remove"></i></a></td>';
        fila += '</tr>';
        $("#tabla_chofer_id").append(fila);
    }
    
    function construir_paginacion(total_filas, filas_por_pagina, carga){
        paginas = Math.trunc(total_filas / filas_por_pagina);
        paginas = (total_filas % filas_por_pagina > 0) ? (paginas + 1): paginas;
        var j = 1;
        for(i = 0; i < paginas; i++){
            var activer = ((i == 0) && (carga == 1)) ? 'active' : '';
            $('.lista_paginacion').append('<li class="page-item ' + activer + '"><a class="pajaro">'+(i+1)+'</a></li>');
        }
    }
</script>