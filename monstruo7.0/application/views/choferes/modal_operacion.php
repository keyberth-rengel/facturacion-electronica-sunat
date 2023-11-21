<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="titulo_modal">Registrar Chofer</h4>
        </div>
        <div class="modal-body">
            <form>
                <div class="row">
                    <div class="col-xs-6">
       			<div class="form-group">
                            <label for="modal_chofer">Nombres</label>
                            <input type="text" id="modal_nombres" name="modal_nombres" class="form-control input-sm">
       			</div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-6">
       			<div class="form-group">
                            <label for="modal_chofer">Apellidos</label>
                            <input type="text" id="modal_apellidos" name="modal_apellidos" class="form-control input-sm">
                            <input type="hidden" id="modal_chofer_id"/>
       			</div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-xs-6">
       			<div class="form-group">
                            <label for="modal_chofer">Tipo</label>
                            <select class="form-control" id="tipo_entidad_id"></select>
       			</div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-xs-6">
       			<div class="form-group">
                            <label for="modal_chofer">Número documento</label>
                            <input type="text" id="modal_numero_documento" name="modal_numero_documento" class="form-control input-sm">
                            <input type="hidden" id="modal_chofer_id"/>
       			</div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-6">
       			<div class="form-group">
                            <label for="modal_chofer">Licencia</label>
                            <input type="text" id="modal_licencia" name="modal_licencia" class="form-control input-sm">
                            <input type="hidden" id="modal_chofer_id"/>
       			</div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            <button type="button" class="btn btn-primary" id="btn_guardar_chofer">Guardar</button>
        </div>
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
<script type="text/javascript">
    var url_save = base_url + 'index.php/choferes/operaciones';
    var cadena_tipo_entidad = '';       
    
    $.getJSON(base_url + 'index.php/WS_tipo_entidades/select_all')
    .done(function (data) {
        (data).forEach(function (repo) {
            cadena_tipo_entidad += "<option value='" + repo.id + "'>" + repo.tipo_entidad + "</option>";
        });                        
        $('#tipo_entidad_id').append(cadena_tipo_entidad);
    });
            
    $(document).ready(function(){                        
        $("#btn_guardar_chofer").on('click', function(){            
            if($('#modal_chofer').val() == ''){
                toast('Error', 1500, 'Debe ingresar Chofer');
                return false;
            }

            var data = {
                chofer_id           :$('#modal_chofer_id').val(),
                nombres             :$('#modal_nombres').val(),
                apellidos           :$('#modal_apellidos').val(),
                numero_documento    :$('#modal_numero_documento').val(),
                tipo_entidad_id     :$('#tipo_entidad_id').val(),
                licencia            :$('#modal_licencia').val()
            };

            $.getJSON(url_save, data)
                .done(function(datos, textStatus, jqXHR){
                    toast('success', 1500, 'Chofer ingresado correctamente');
                    $("#myModal").modal('hide');
                        
                    $("#tabla_chofer_id > tbody").remove();
                    $("#lista_id_pagination > li").remove();
                    carga_inicial();
                })
                .fail(function( jqXHR, textStatus, errorThrown ) {
                    if ( console && console.log ) {
                        console.log( "Algo ha fallado: " +  textStatus );
                    }
                });                   
        });                        
        
        if(chofer_id != ''){
            ruta_url_item = base_url + 'index.php/WS_choferes/select_item/' + chofer_id;
            $.getJSON(ruta_url_item)
            .done(function (data){
                console.log(data);
                $('#modal_nombres').val(data[0].nombres);
                $('#modal_apellidos').val(data[0].apellidos);
                $('#modal_numero_documento').val(data[0].numero_documento);
                $('#modal_licencia').val(data[0].licencia);
                $('#modal_chofer_id').val(chofer_id);
                $('#titulo_modal').text('Modificar Chofer');
                $('#btn_guardar_chofer').text('Modificar');

                //tipo documento chofer
                $("#tipo_entidad_id option[value='" + data[0].tipo_entidad_id + "']").prop('selected', true);
            });                
        }
    });        
</script>
