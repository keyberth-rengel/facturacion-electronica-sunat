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
<h2 align="center">Carros</h2>
<br>
<div class="container">
    <div class="row">
        <div class="col-md-4">
            <button id="btn_nuevo_carro" class="btn btn-success btn-sm" data-toggle="modal" data-target="#myModal" data-backdrop="static" data-keyboard="false">Nuevo Carro</button>
        </div>

        <div class="col-md-1" >
        </div> 
        <div class="col-md-2" >        
        </div> 
        <div class="col-md-4">
            <input type="text" class="form-control form-control-sm" id="carro" placeholder="Buscar Carro">
            <input type="hidden" id="carro_id" />
        </div>   

        <div class="col-md-1" >
            <button class="btn btn-default" type="button" id="btn_buscar_carro" name="btn_buscar_carro"><span class="glyphicon glyphicon-search"></span></button>   
        </div>                   
    </div>
    <br>
    <div class="row-fluid">
        <table role="grid" style="height: auto;" id="tabla_carro_id" class="table table-bordered table-responsive table-hover">
            <thead>
                <tr>
                    <th>N.</th>
                    <th>Marca-Modelo</th>
                    <th>Placa</th>
                    <th>Número MTC</th>
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
    var modal_carro_id;
    var carro_id = '';
    
    $(document).ready(function(){
        //BUSCAR filtros
        $('#btn_buscar_carro').on('click', function(){
            pagina = 1; //
            param_carro_id = ($('#carro_id').val() == '') ? param_stand_url :  $('#carro_id').val();
            $("#tabla_carro_id > tbody").remove();

            var ruta_url = base_url + 'index.php/WS_carros/ws_select/' + pagina + '/' + filas_por_pagina + '/' + param_carro_id;
            console.log(ruta_url);
            $.getJSON(ruta_url)
                .done(function (data) {
                    console.log(data);
                    carga = 1;//se usa para activar la pagina N. 1
                    total_filas = data.total_filas;
                    $("#lista_id_pagination > li").remove();
                    construir_paginacion(total_filas, filas_por_pagina, carga)
                    
                    var numero_orden = filas_por_pagina*(pagina-1)+1;
                    (data.ws_select_carros).forEach(function (repo) {
                        agregarFila(numero_orden, repo.marca, repo.modelo, repo.placa, repo.numero_mtc, repo.carro_id);
                        numero_orden ++;
                    });
            });
        });
        
        //PAGINACION
        $('#div_contenedor').on('click', '.pajaro', function(){
            param_carro_id = ($('#carro_id').val() == '') ? param_stand_url :  $('#carro_id').val();
            
            $('li').removeClass('active');
            $(this).parent().addClass('active');
                        
            pagina = $(this).text();
            $("#tabla_carro_id > tbody").remove();

            var url_l = base_url + 'index.php/WS_carros/ws_select/' + pagina + '/' + filas_por_pagina + '/' + param_carro_id;
            $.getJSON(url_l)
                .done(function (data) {

                    total_filas = data.total_filas; 
                    var numero_orden = filas_por_pagina*(pagina-1)+1;
                    (data.ws_select_carros).forEach(function (repo) {
                        agregarFila(numero_orden, repo.marca, repo.modelo, repo.placa, repo.numero_mtc, repo.carro_id);
                        numero_orden ++;
                    });
            });            
        });                                                                
        
        //EDITAR
        $("#tabla_carro_id").on('click', '.btn_modificar_carro', function(){
            carro_id = $(this).attr('id');
            ruta_url = base_url + 'index.php/carros/modal_operacion/';
            $("#myModal").load(ruta_url);
        });
        
        $("#tabla_carro_id").on('click', '.btn_eliminar_carro', function(){            
            var carro_id = $(this).attr('id');            
            var x = confirm("Desea eliminar esta carro:");
            if (x){ 
                ruta_url_item = base_url + 'index.php/WS_carros/delete_item/' + carro_id;
                $.getJSON(ruta_url_item)
                .done(function (data){
                    console.log('elimiación correcta');
            
                    $("#tabla_carro_id > tbody").remove();
                    $("#lista_id_pagination > li").remove();
                    carga_inicial();
                });                                                        
            }
        });
        
        //subir imagen
        $("#tabla_carro_id").on('click', '.btn_imagen', function(){
            modal_carro_id = $(this).attr('id');
            $("#myModal").load(base_url + 'index.php/carros/modal_imagen');
        });
    });
    
    $("#btn_nuevo_carro").click(function(){
        carro_id = '';
        $("#myModal").load('<?php echo base_url()?>index.php/carros/modal_operacion');
    }); 
    
    $('#carro').autocomplete({
        source: base_url + 'index.php/WS_carros/buscador_carro',
        minLength: 2,
        select: function (event, ui) {
            $('#carro_id').val(ui.item.id);
        }
    });
    
    carga_inicial();
    
    function carga_inicial(){
        //CARGA INICIAL
        var url_l = base_url + 'index.php/WS_carros/ws_select/' + pagina_inicial + '/' + filas_por_pagina + '/' + param_stand_url ;
        console.log(url_l);
        $.getJSON(url_l)
            .done(function (data) {
                carga = 1;//solo se usa al cargar la página, para activar la pagina N. 1
                total_filas = data.total_filas;
                construir_paginacion(total_filas, filas_por_pagina, carga);

                var numero_orden = 1;
                (data.ws_select_carros).forEach(function (repo) {
                    agregarFila(numero_orden, repo.marca, repo.modelo, repo.placa, repo.numero_mtc, repo.carro_id);
                    numero_orden ++;
                });
        });
    }    
   
    function agregarFila(numero_orden, marca, modelo, placa, numero_mtc, carro_id){
        var fila = '<tr class="seleccionado tabla_fila">';
        fila += '<td align="center">'+numero_orden+'</td>';
        fila += '<td>'+marca+'-'+modelo+'</td>';
        fila += '<td>'+placa+'</td>';
        fila += '<td>'+numero_mtc+'</td>';
        fila += '<td align="center"><a id="'+carro_id+'" class="btn btn-default btn-xs btn_modificar_carro" data-toggle="modal" data-target="#myModal"><i class="glyphicon glyphicon-pencil"></i></a></td>';
        fila += '<td align="center"><a id="'+carro_id+'" class="btn btn-danger btn-xs btn_eliminar_carro"><i class="glyphicon glyphicon-remove"></i></a></td>';
        fila += '</tr>';
        $("#tabla_carro_id").append(fila);
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