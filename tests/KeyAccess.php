<?php
namespace Tests\Unit\libs\gateways;

class KeyAccess
{
    const CIELO = array(
        // Chaves sandbox Codificar
        array(
            ['key' => 'cielo_merchant_key', 'value' => 'BKUWHFVDBQSOTKINJCNPMCTEFZROQRFBXSHRYIPJ'],
            ['key' => 'cielo_merchant_id', 'value' => '851c6271-df49-4ee6-b212-79ea22ae839f']
        ),
    );

    const BANCARD = array(
        // Chaves sandbox Codificar
        array(
            ['key' => 'bancard_public_key', 'value' => 'CG22puGHxrRMtIIbxoxnFkhKH0PIgRl'],
            ['key' => 'bancard_private_key', 'value' => 'hWj2OrfeKQAjQwUm(RgUx3co,UDThPJiS4qB)x)7']
        ),
    );

    const IPAG = array( 
        // Chaves sandbox Codificar
        array(
            ['key' => 'ipag_api_id', 'value' => 'codificar@sandbox.com.br'],
            ['key' => 'ipag_api_key', 'value' => '7C0A-A3813B2B-84875BF7-F70ECD01-5530']
        )
    );

    const IPAG_PIX = array(
        // Chaves sandbox Codificar
        array(
            ['key' => 'pix_ipag_api_id', 'value' => 'codificar@sandbox.com.br'],
            ['key' => 'pix_ipag_api_key', 'value' => '7C0A-A3813B2B-84875BF7-F70ECD01-5530'],
            ['key' => 'pix_ipag_expiration_time', 'value' => '30'],
            ['key' => 'pix_ipag_version', 'value' => '2'],
        )
    );

    const PAGARME_V2 = array(
        // Chaves sandbox Codificar
        array(
            ['key' => 'pagarme_encryption_key', 'value' => 'ek_test_pHEXVtJxrD2z7BR2k3vABIcQhg9Lob'],
            ['key' => 'pagarme_recipient_id', 'value' => 're_ckzznucdp00ep0j9tu08gvi2b'],
            ['key' => 'pagarme_api_key', 'value' => 'ak_test_PlTjytFjSz5RLwcUK9TFssfmKMac8y']
        )
    );

    const PAGARME_V5 = array(
        // Chaves sandbox Codificar
        array(
            ['key' => 'pagarme_secret_key', 'value' => 'sk_test_QZJV0vs9OuAKwWDn'],
            ['key' => 'pagarme_recipient_id', 'value' => 'rp_RjMAPEjHXFj6qNeE']
        )
    );

    const BRASPAG = array(
        // Chaves sandbox Codificar
        array(
            ['key' => 'braspag_merchant_id', 'value' => 'b330b05f-b27f-4497-a682-943b392a2b96'],
            ['key' => 'braspag_merchant_key', 'value' => 'ULMLYDYEXNPHUZZPHUHIFXAWZTWTJUIMUZHZVUAT']
        )
    );

    const ADIQ = array(
        // Chaves sandbox Codificar
        array(
            ['key' => 'adiq_client_id', 'value' => 'A1EF2F6F-8BA0-4C2F-91EA-8E1603D9FD7D'],
            ['key' => 'adiq_client_secret', 'value' => '93D46FF3-B98C-4BFF-92CD-3A3A58BDD371'],
        )
    );

    /**
     * Get array key random
     * @return array
     */
    public static function getArrayKeys($gateway)
    {
        switch ($gateway) {
            case 'cielo':
                $keys = self::CIELO;
                break;
            case 'bancard':
                $keys = self::BANCARD;
                break;
            case 'ipag':
                $keys = self::IPAG;
                break;
            case 'pagarme':
                $keys = self::PAGARME_V5;
                break;
            case 'pagarmev2':
                $keys = self::PAGARME_V2;
                break;
            case 'braspag':
                $keys = self::BRASPAG;
                break;
            case 'adiq':
                $keys = self::ADIQ;
                break;
            default:
                $keys = array();           
                break;
        }

        $max = count($keys) - 1;
        $index = mt_rand(0,$max);

        return $keys[$index];
    }

    /**
     * Get array key random
     * @return array
     */
    public static function getArrayKeysPix($gateway)
    {
        switch ($gateway) {
            case 'cielo':
                $keys = self::CIELO;
                break;
            case 'ipag':
                $keys = self::IPAG_PIX;
                break;
            default:
                $keys = array();           
                break;
        }

        $max = count($keys) - 1;
        $index = mt_rand(0,$max);

        return $keys[$index];
    }
}
