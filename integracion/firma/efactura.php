<?php
header ('Content-type: text/html; charset=utf-8'); 
require 'see/lib/xmlseclibs-master/xmlseclibs.php';
class Factura{
    public function firmar(DOMDocument $domDocument, $ruc=""){
		
		//return false;
		$ReferenceNodeName = 'ExtensionContent';
		$privateKey = file_get_contents('certificado/server_key.pem');
		$publicKey = file_get_contents('certificado/server.pem');
//		$privateKey = file_get_contents('cer_pesquera/CERTIFICADOCLAVEPRIVADA.pem');
//		$publicKey = file_get_contents('cer_pesquera/CERTIFICADOCLAVEPUBLICASUNAT.pem');
//		$privateKey = file_get_contents('cer_sistemas_integrales/CERTIFICADOCLAVEPRIVADA.pem')CERTIFICADOCLAVEPUBLICASUNAT.pem;
//		$publicKey = file_get_contents('cer_sistemas_integrales/CERTIFICADOCLAVEPUBLICASUNAT.pem');

		//$domDocument = new \DOMDocument();
		//$domDocument->loadXML($xmlstr);
		$objSign = new XMLSecurityDSig($ruc);
		$objSign->setCanonicalMethod(XMLSecurityDSig::C14N);
		$objSign->addReference(
			$domDocument, 
			XMLSecurityDSig::SHA1, 
			array('http://www.w3.org/2000/09/xmldsig#enveloped-signature'),
			$options = array('force_uri' => true)
		);
		
		$objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type'=>'private'));
		$objKey->loadKey($privateKey);
		
		// Sign the XML file
		$Node = $domDocument->getElementsByTagName($ReferenceNodeName)->item(1);
		if (!($Node)) $Node = $domDocument->getElementsByTagName($ReferenceNodeName)->item(0);
		$objSign->sign($objKey, $Node);
		// Add the associated public key to the signature
		$objSign->add509Cert($publicKey);
		return $domDocument;
	}
}

//$xmlstr = file_get_contents("20602535933-01-F001-56.xml");
//$name_file = "20604051984-01-F001-131.xml";
//$name_file = "20604051984-01-F001-56.xml";
$name_file = "20604051984-03-B001-7.xml";
//$name_file = "20604051984-RC-20221104-1.xml";
$xmlstr = file_get_contents($name_file);
//$xmlstr = file_get_contents("F001-48.xml");
$domDocument = new \DOMDocument();
$domDocument->loadXML($xmlstr);
$factura  = new Factura();
$xml = $factura->firmar($domDocument);
$content = $xml->saveXML();
//echo $content;
echo "Documento Firmado";
file_put_contents("archivo_firmado_con_certificado---".$name_file, $content);
?>