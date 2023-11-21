<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="titulo_modal">Registrar Carro--</h4>
        </div>
        <div class="modal-body">
            <form>
                <div class="row">
                    <div class="col-xs-6">
       			<div class="form-group">
                            <label for="modal_carro">Marca</label>
                            <input type="text" id="modal_marca" name="modal_marca" class="form-control input-sm">
       			</div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-6">
       			<div class="form-group">
                            <label for="modal_carro">Modelo</label>
                            <input type="text" id="modal_modelo" name="modal_modelo" class="form-control input-sm">
       			</div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-6">
       			<div class="form-group">
                            <label for="modal_carro">Placa</label>
                            <input type="text" id="modal_placa" name="modal_placa" class="form-control input-sm">
       			</div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-6">
       			<div class="form-group">
                            <label for="modal_carro">Número MTC</label>
                            <input type="text" id="modal_numero_mtc" name="modal_numero_mtc" class="form-control input-sm">
                            <input type="hidden" id="modal_carro_id"/>
       			</div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            <button type="button" class="btn btn-primary" id="btn_guardar_carro">Guardar</button>
        </div>
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
<script type="text/javascript">
    var url_save = base_url + 'index.php/carros/insert_max_id';            
            
    $(document).ready(function(){                        
        $("#btn_guardar_carro").on('click', function(){            
            if($('#modal_carro').val() == ''){
                toast('Error', 1500, 'Debe ingresar Carro');
                return false;
            }

            var data = {
                carro_id    :$('#modal_carro_id').val(),
                marca       :$('#modal_marca').val(),
                modelo      :$('#modal_modelo').val(),
                placa       :$('#modal_placa').val(),
                numero_mtc  :$('#modal_numero_mtc').val()
            };

            $.getJSON(url_save, data)
                .done(function(datos, textStatus, jqXHR){
                    $('#txt_carro').val($('#modal_placa').val() + ' ' + $('#modal_marca').val()  + ' ' + $('#modal_modelo').val());
                    $('#txt_carro_id').val(datos);
                    
                    toast('success', 1500, 'Carro ingresado correctamente');
                    $("#myModal").modal('hide');                                            
                })
                .fail(function( jqXHR, textStatus, errorThrown ) {
                    if ( console && console.log ) {
                        console.log( "Algo ha fallado: " +  textStatus );
                    }
                });                   
        });                        
    });        
</script>