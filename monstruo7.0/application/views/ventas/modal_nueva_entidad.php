<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">Registro Entidad</h4>
        </div>
        <div class="modal-body">
            <div class="row">                        
                <div class="col-xs-12">
                    <div class="form-group">
                        <label for="modal_tipo_entidades">Tipo Entidad</label>
                        <select id="modal_tipo_entidades" class="form form-control">                                
                        </select>                            
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-4">
                    <div class="form-group">
                        <label for="modal_numero_documento">N. Documento</label>
                        <button id="btn_limpiar"><span class="glyphicon glyphicon-erase" aria-hidden="true"></span></button>
                        <input type="text" id="modal_numero_documento" name="modal_numero_documento" class="form-control input-sm" value="00000000">
                        <input type="hidden" id="modal_entidad_id"/>
                    </div>
                </div>                
            </div>  
            <div class="row">
                <div class="col-xs-12">
                    <div class="form-group">
                        <label for="modal_entidad">Razón Social/Nombres Completos</label>
                        <input type="text" id="modal_entidad" class="form-control input-sm">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12">
                    <div class="form-group">
                        <label for="modal_direccion">Dirección</label>
                        <input type="text" id="modal_direccion" class="form-control input-sm">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xs-12">
                    <div class="form-group">
                        <label for="telefono_movil_1">Teléfono</label>
                        <input type="text" id="telefono_movil_1" name="telefono_movil_1" class="form-control input-sm">
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            <button type="button" class="btn btn-primary" id="btn_guardar_entidad">Guardar</button>
        </div>
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
<script type="text/javascript">        
    $.getJSON(base_url + 'index.php/WS_tipo_entidades/select')
    .done(function (data){
        sortJSON(data.tipo_entidades, 'id', 'desc');
        (data.tipo_entidades).forEach(function (repo) {
            var selectedado = (repo.id == 1) ? 'selected' : '';
            $('#modal_tipo_entidades').prepend("<option " + selectedado + " value='" + repo.id + "'>" + repo.tipo_entidad + "</option>");
        });

        if($('#modal_entidad_id').val() != ''){
            $("#modal_tipo_entidades option[value='"+tipo_entidad_id+"']").prop("selected", true);
        }
        if($('#modal_entidad_id').val() != ''){
            $('.modal-title').text('Modificar Producto');
            $('#btn_guardar_entidad').text('Modificar');
        }
    });
            
    $(document).ready(function(){
        
        $("#datos_ws_externa").on('click', function(){       
            toast('success', 2000, 'Buscando');
            var modal_numero_documento = $("#modal_numero_documento").val();
            if( (modal_numero_documento.length != 8) && (modal_numero_documento.length != 11) ){
                toast('Error', 1500, 'Cantidad de dígitos incorrectos.');
                return false;
            }

            //buscador para RUC
            if(modal_numero_documento.length == 11){            
                var url_l = base_url + 'index.php/WS_entidades/buscador_externo_ruc/' + $("#modal_numero_documento").val();
                $.getJSON(url_l)
                    .done(function (data) {                    
                        $("#modal_entidad").val(data.razon_social);
                        $("#modal_direccion").val(data.domicilio_fiscal);
                    })
                    .fail(function() {
                        toast('Error', 1500, 'Datos no encontrados');
                        $("#modal_entidad").val('');
                        $("#modal_direccion").val('');
                    })
            }
            
            //buscador para DNI
            if(modal_numero_documento.length == 8){            
                var url_l = base_url + 'index.php/WS_entidades/buscador_externo_dni/' + $("#modal_numero_documento").val();
                $.getJSON(url_l)
                    .done(function (data) {                    
                        $("#modal_entidad").val(data.entidad);
                    })
                    .fail(function() {
                        toast('Error', 1500, 'Datos no encontrados');
                        $("#modal_entidad").val('');
                        $("#modal_direccion").val('');
                    })
            }
        });
        
        $("#btn_guardar_entidad").on('click', function(){
            if(($('#modal_numero_documento').val() == '') ||  ($('#modal_entidad').val() == '') || ($('#modal_direccion').val() == '')){
                toast('Error', 1500, 'Falta ingresar datos');
                return false;
            }
            
            if(($('#modal_tipo_entidades').val() == 1) && ($('#modal_numero_documento').val().length != 8)){
                toast('Error', 1500, 'DNI debe tener 8 caracteres.');
                return false;
            }
            
            if(($('#modal_tipo_entidades').val() == 2) && ($('#modal_numero_documento').val().length != 11)){
                toast('Error', 1500, 'RUC debe tener 11 caracteres.');
                return false;
            }            
            
            let url_select = base_url + 'index.php/WS_entidades/by_field/id/numero_documento/' + $('#modal_numero_documento').val();
            $.getJSON(url_select)
            .done(function(datos, textStatus, jqXHR){

                if((datos != '') && (datos > 0)){
                    var x = confirm("Número de documento: " + $('#modal_numero_documento').val() + " encontrado en la base de datos. Desea ingresarlo nuevamente.");
                    if (x){ 
                        agregarEntidad();
                    }
                }else{                    
                    agregarEntidad();
                }                
            })
            .fail(function( jqXHR, textStatus, errorThrown ) {
                if ( console && console.log ) {
                    console.log( "Algo ha fallado: " +  textStatus );
                }
            });
        });
        
        $("#modal_tipo_entidades").on('change', function(){
           $("#modal_numero_documento").val('');
        });
        
        $("#btn_limpiar").on('click', function(){
           $("#modal_numero_documento").val(''); 
        });
    });
    
    function agregarEntidad(){
        var data = {
            entidad_id          :$('#modal_entidad_id').val(),
            tipo_entidades      :$('#modal_tipo_entidades').val(),
            numero_documento    :$('#modal_numero_documento').val(),
            entidad             :$('#modal_entidad').val(),
            direccion           :$('#modal_direccion').val(),
            telefono_movil_1    :$('#telefono_movil_1').val(),
        };
        
        let url_save = base_url + 'index.php/WS_entidades/operaciones';
        $.getJSON(url_save, data)
        .done(function(datos, textStatus, jqXHR){
            toast('success', 1500, 'Entidad ingresada correctamente');

            $("#myModal").modal('hide');
            $("#entidad_id").val(datos.entidad_id);
            $("#entidad").val($("#modal_entidad").val() + ' ' + $("#modal_numero_documento").val());
            $("#direccion").val($("#modal_direccion").val());
            $("#tipo_entidad_id").val($('#modal_tipo_entidades').val());
        });
    }
</script>
