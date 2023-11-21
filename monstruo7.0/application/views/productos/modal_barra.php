<div class="modal-dialog" role="document">
    <div class="modal-content">        
        <div class="modal-body">
            
            <div class="col-xs-6">
                <div aling="center">
                    <span id="codigo"></span>
                </div>
                <br>
            </div>
            
            <div class="col-xs-6">
                <div aling="center">
                    <span id="descripcion_producto"></span>
                </div>
                <br>
            </div>
            
            <div class="col-xs-6">
                <div aling="center">
                    <img height="22px" id="img_codigo_barras">
                </div>
                <br>
            </div>
            
        </div>        
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script src="<?PHP echo base_url(); ?>assets/js/monstruo/config.js"></script>
<script type="text/javascript">
    
    

    ruta_url_item = base_url + 'index.php/WS_productos/ws_select_item/' + modal_producto_id;
    $.getJSON(ruta_url_item)
    .done(function (data){
        precio_base_venta = data[0].precio_base_venta;       
        $('#codigo').text(data[0].codigo);
        $('#descripcion_producto').text(data[0].producto);

        foto_imagen = (data[0].imagen == null) ? 'sin_foto.jpg' : data[0].imagen;        
        $("#img_codigo_barras").attr('src', base_url + 'index.php/productos/barcode_get?text=' + data[0].codigo + '&size=22');
    });
    
</script>    