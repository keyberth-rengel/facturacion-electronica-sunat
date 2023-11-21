<html>
    <head>
        <title>Comprobante PDF-A4</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <style>
            body {
                font-family: Arial, Helvetica, Verdana;
            }
            
            #contenedor{
                width: 100%;
                height: 200px;
            }
            
            #uno{
                font-size: 16px;
                width: 31%;
                height: 15%;
                float: left;
                padding-top: 2px;
                padding-right: 10px;
            }
            
            #dos{
                font-size: 13px;
                width: 37%;
                height: 15%;
                float: left;
                padding-top: 10px;
                
            }
            
            #tres{
                font-size: 14px;
                margin-top: 2px;
                padding-top: 10px;
                padding-left: 25px;
                width: 32%;
                height: 12%;
                float: left;
                
                border: 1px solid gray;
                border-radius: 25px;
            }
            
            #cabecera_1{
                font-size: 14px;                
            }

            .fecha{
                float: left;
                width: 23%;
                border: 1px solid gray;
                border-radius: 10px;
                margin-right: 15px;
                padding-left: 7px;
            }
            
            #documentos{
                float: left;
                width: 46%;
                height: 39px;
                border: 1px solid gray;
                border-radius: 10px;
                padding-left: 7px;
            }
            
            .left_izquierda{
                float: left;
                border: 1px solid gray;
                border-radius: 10px;
                width: 48%;
                margin-right: 15px;
                padding-left: 7px;
            }
            
            .left_izquierda_tabla{
                float: left;
                padding-left: 7px;
            }
            
            .limpiando{
                clear: both;
                height: 1px;
            }
            
            .limpiando_fila{
                clear: both;
                height: 2px;
            }
            
            .border_div{
                border: 1px solid gray;
                border-radius: 10px;
                padding-left: 7px;
            }
            
            .responsive {
                width: 100%;
                max-width: 400px;
                max-height: 200px;
            }
            
            #div_observacion{
                width: 90%;
                float: left;
                
            }
            
            #div_qr{                
                width: 10%;
                float: left;                                
            }
            
            #footerPdf {
                position: fixed; 
                bottom: -60px; 
                left: 0px; 
                right: 0px;
                height: 135px; 
                
                
                /*line-height: 35px;*/
            }
        </style>
    </head>   
                
    <body>
        <div id="contenedor">
            <div id="uno">                
                <img src="<?PHP FCPATH;?>images/empresas/<?php echo $empresa['foto'];?>" height="140" style="text-align:center;" class="responsive" >
            </div>
            <div id="dos">
                <span><strong>Dirección: </strong><?php echo $empresa['domicilio_fiscal']?></span><br>
                <span><strong>Telf: </strong><?php echo $empresa['telefono_fijo']." // ".$empresa['telefono_movil']?></span>
            </div>
            <div align="center" id="tres">
                <div><b>RUC: <?php echo $empresa['ruc'];?></b></div>
                <br>
                GUIA DE REMISIÓN ELECTRÓNICA TRANSPORTISTA
                <br>
                <br>
                <div id="numero_guia">Nº: <?php echo $cabecera['serie']."-".$cabecera['numero']?></div>
            </div>
        </div>
        
        <div id="cabecera_1">
            <span><b>Número de Registro MTC: </b><?php echo $cabecera['numero_mtc']?></span><br>
            <span><b>Fecha y hora de emisión: </b><?php echo $cabecera['fecha_emision_cf']."-".$cabecera['hora_emision_cf']?></span><br>
            <span><b>Fecha de inicio de Traslado: </b><?php echo $cabecera['fecha_traslado_cf']?></span><br><br>
            
            <span><b>Punto de Partida: </b><?php echo $cabecera['partida_direccion']?></span><br>
            <span><b>Punto de llegada: </b><?php echo $cabecera['llegada_direccion']?></span><br><br>
            
            <span><b>Datos del remitente: </b><?php echo $cabecera['remitente_entidad']." N.:".$cabecera['remitente_numero_documento']?></span><br>
            <span><b>Datos del destinatario: </b><?php echo $cabecera['destinatario_entidad']." N.:".$cabecera['destinatario_numero_documento']?></span><br><br>
            
            <span><b>Documento relacionado: </b><?php echo $cabecera['adjunto_tipo_documento']." ".$cabecera['adjunto_serie']."-".$cabecera['adjunto_numero']?></span><br>
            <span><b>Peso Bruto total de la carga: </b><?php echo "KGM ".$cabecera['peso_total']?></span><br><br>
            
            <span><b>Datos del vehiculo: </b><br>
                <?php
                $principal = (count($carros) == 2) ? 'Principal' : '';
                $secundario = (count($carros) == 2) ? 'Secundario' : '';
                $ii = 0;
                foreach ($carros as $value_carros){
                    if($ii == 0){
                        echo "<b>$principal</b> ";
                    } else {
                        echo "<b>$secundario</b> ";    
                    }                    
                    echo "Placa: ".$value_carros['vehiculo_placa']." &nbsp;&nbsp;&nbsp;Número MTC:".$value_carros['vehiculo_mtc']."<br>";
                    $ii++;
                }
                ?>                
            </span>            
            <br>
            <span><b>Datos del conductor: </b><?php echo $cabecera['conductor_nombres']." ".$cabecera['conductor_apellidos']." &nbsp;&nbsp;". $cabecera['chofer_tipo_entidad_id'] . ":" . $cabecera['conductor_dni'] . " &nbsp;&nbsp;Licencia:".$cabecera['conductor_licencia']?></span><br><br>
        </div>
        
        <div class="border_div">
            <div class="left_izquierda_tabla" style="width: 10%">Cantidad</div>
            <div class="left_izquierda_tabla" style="width: 10%">Medida</div>
            <div class="left_izquierda_tabla" style="width: 80%">Descripción</div>
            <div class="limpiando"></div>
            <hr>
            <?php
            $i = 1;
            foreach ($detalle as $value_detalle){
                $pintado = ($i % 2) == 0 ? "background-color: #E0E0E0" : ""; 
                $i++;
            ?>
            <div class="left_izquierda_tabla" style="width: 10%; <?php echo $pintado?>"><?php echo $value_detalle['cantidad']?></div>
            <div class="left_izquierda_tabla" style="width: 10%; <?php echo $pintado?>"><?php echo $value_detalle['codigo_unidad']?></div>
            <div class="left_izquierda_tabla" style="width: 77%; <?php echo $pintado?>"><?php echo $value_detalle['producto']?></div>
            <div class="limpiando_fila"></div>
            <?php
            }?>            
        </div>
        <div id="cabecera_1">
        <?php 
        if($cabecera['sub_contratista_entidad'] != null){?>
        <span><b>Sub contratista: </b><?php echo $cabecera['sub_contratista_entidad'];?></span><br>
        <span><b>N documento: </b><?php echo $cabecera['sub_contratista_numero_documento'];?></span><br><br>
        <?php
        }
        
        if($cabecera['pagador_entidad'] != null){?>
        <span><b>Pagador Flete: </b><?php echo $cabecera['pagador_entidad'];?></span><br>
        <span><b>N documento: </b><?php echo $cabecera['pagador_numero_documento'];?></span><br><br>
        <?php
        }
        ?>                
        </div>
        <div id="footerPdf">
            <div id="div_observacion">
                <span><b>Observaciones:</b><?php echo $cabecera['observaciones'];?></span>
            </div>
            <div id="div_qr">
                <img src="<?php echo $rutaqr?>" style="width:2cm;height: 2cm;">
            </div>
        </div>
    </body>
</html>