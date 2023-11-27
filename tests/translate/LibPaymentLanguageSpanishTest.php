<?php

namespace Tests\Unit\libs\translate;

use Tests\TestCase;

// to run test: sail artisan test --filter LibPaymentLanguageSpanishTest
class LibPaymentLanguageSpanishTest extends TestCase
{
    /**
     * Testa  exist files total
     * 
     * @return void
	 */
    function testExistsFilesScreen()
    // Caminhos para os arquivos de array em diferentes diretórios
	{

        
        $path = base_path() . '/vendor/codificar/payment-gateway/src/resources/lang';
        $directoryPt = "$path/pt-br/*.php";
        $directoryEs = "$path/es-py/*.php";
        $filesPt = glob($directoryPt);
        $filesEs = glob($directoryEs);

        $filesNamePt = array_map(function($file) { 
            return basename($file);
        }, $filesPt);

        $filesNameEs = array_map(function($file) { 
            return basename($file);
        }, $filesEs);


        if(count(array_diff($filesNamePt, $filesNameEs)) > 0) {
            $this->assertTrue(False, " # Erro: Total de arquivos de tradução português com os arquivos de Espanhol não conferem\n");
        }

        $this->assertTrue(true, " - Arquivos de Tradução es conferem com pt-BR ");
        return array(
            'filesPt' => $filesPt, 
            'filesEs' => $filesEs
        );
	}

    /**
     * test if every file ins translate pt-br and es is match
     * 
     * @depends testExistsFilesScreen
     * 
     */
    function testEachMatchTranslateSuccess($files)
    {

        $filesPt = $files['filesPt']; 
        $filesEs = $files['filesEs'];

        $isError = false;
        $errors = array();
        foreach ($filesPt as $key => $file) {
            $arrPt = include($filesPt[$key]);
            $arrEs = include($filesEs[$key]);

            $diff = array_diff_key($arrPt, $arrEs);
            if(count($diff) > 0) {
                $isError = true;
                $namePt =  basename($filesPt[$key]);
                $nameEs =  basename($filesEs[$key]);
                $errorName = "$namePt [pt-BR] Diferente de $nameEs [es-PY] - por favor verificar\n" . print_r($diff,1);
                array_push($errors, $errorName);
            }
        }

        if($isError) {
            $this->assertTrue(False, " # Erro: Arquivos de tradução português com os arquivos de Espanhol não conferem\n" . print_r($errors,1));
        }

        $this->assertTrue(true, " - Arquivos de Tradução es conferem com pt-BR ");
    }


}
