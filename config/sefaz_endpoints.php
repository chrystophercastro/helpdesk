<?php
/**
 * Endpoints SEFAZ NF-e v4.00
 * Atualizado: 2025
 * Referência: http://www.nfe.fazenda.gov.br/portal/webServices.aspx
 *
 * Estrutura:
 * - AN: Ambiente Nacional (DistribuicaoDFe, RecepcaoEvento manifesto)
 * - UF: Endpoints por UF para NF-e (Autorização, Consulta, Status, Evento, etc)
 * - SOAP: SoapActions e Namespaces para envelopes SOAP 1.2
 */

return [

    // ================================================================
    // AMBIENTE NACIONAL (AN) — serviços nacionais
    // DistribuicaoDFe e RecepcaoEvento para Manifesto sempre via AN
    // ================================================================
    'AN' => [
        'producao' => [
            'NFeDistribuicaoDFe' => 'https://www1.nfe.fazenda.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx',
            'RecepcaoEvento'     => 'https://www.nfe.fazenda.gov.br/NFeRecepcaoEvento4/NFeRecepcaoEvento4.asmx',
            'NFeConsulta'        => 'https://www.nfe.fazenda.gov.br/NFeConsultaProtocolo4/NFeConsultaProtocolo4.asmx',
            'NFeStatusServico'   => 'https://www.nfe.fazenda.gov.br/NFeStatusServico4/NFeStatusServico4.asmx',
        ],
        'homologacao' => [
            'NFeDistribuicaoDFe' => 'https://hom1.nfe.fazenda.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx',
            'RecepcaoEvento'     => 'https://hom1.nfe.fazenda.gov.br/NFeRecepcaoEvento4/NFeRecepcaoEvento4.asmx',
            'NFeConsulta'        => 'https://hom1.nfe.fazenda.gov.br/NFeConsultaProtocolo4/NFeConsultaProtocolo4.asmx',
            'NFeStatusServico'   => 'https://hom1.nfe.fazenda.gov.br/NFeStatusServico4/NFeStatusServico4.asmx',
        ],
    ],

    // ================================================================
    // SOAP Actions & Namespaces (v4)
    // ================================================================
    'SOAP' => [
        'NFeDistribuicaoDFe' => [
            'action'    => 'http://www.portalfiscal.inf.br/nfe/wsdl/NFeDistribuicaoDFe/nfeDistDFeInteresse',
            'namespace' => 'http://www.portalfiscal.inf.br/nfe/wsdl/NFeDistribuicaoDFe',
        ],
        'RecepcaoEvento' => [
            'action'    => 'http://www.portalfiscal.inf.br/nfe/wsdl/NFeRecepcaoEvento4/nfeRecepcaoEvento',
            'namespace' => 'http://www.portalfiscal.inf.br/nfe/wsdl/NFeRecepcaoEvento4',
        ],
        'NFeConsulta' => [
            'action'    => 'http://www.portalfiscal.inf.br/nfe/wsdl/NFeConsultaProtocolo4/nfeConsultaNF',
            'namespace' => 'http://www.portalfiscal.inf.br/nfe/wsdl/NFeConsultaProtocolo4',
        ],
        'NFeStatusServico' => [
            'action'    => 'http://www.portalfiscal.inf.br/nfe/wsdl/NFeStatusServico4/nfeStatusServicoNF',
            'namespace' => 'http://www.portalfiscal.inf.br/nfe/wsdl/NFeStatusServico4',
        ],
        'NfeAutorizacao' => [
            'action'    => 'http://www.portalfiscal.inf.br/nfe/wsdl/NFeAutorizacao4/nfeAutorizacaoLote',
            'namespace' => 'http://www.portalfiscal.inf.br/nfe/wsdl/NFeAutorizacao4',
        ],
    ],

    // ================================================================
    // PER-UF ENDPOINTS v4 (NF-e)
    // Cada UF tem endpoints de homologação e produção
    // ================================================================
    'UF' => [

        // ── Autorizadoras próprias ──

        'AM' => [
            'homologacao' => [
                'NfeAutorizacao'    => 'https://homnfce.sefaz.am.gov.br/nfce-services/services/NfeAutorizacao4',
                'NfeRetAutorizacao' => 'https://homnfce.sefaz.am.gov.br/nfce-services/services/NfeRetAutorizacao4',
                'NfeInutilizacao'   => 'https://homnfce.sefaz.am.gov.br/nfce-services/services/NfeInutilizacao4',
                'NfeConsulta'       => 'https://homnfce.sefaz.am.gov.br/nfce-services/services/NfeConsulta4',
                'NfeStatusServico'  => 'https://homnfce.sefaz.am.gov.br/nfce-services/services/NfeStatusServico4',
                'RecepcaoEvento'    => 'https://homnfce.sefaz.am.gov.br/nfce-services/services/RecepcaoEvento4',
            ],
            'producao' => [
                'NfeAutorizacao'    => 'https://nfe.sefaz.am.gov.br/services2/services/NfeAutorizacao4',
                'NfeRetAutorizacao' => 'https://nfe.sefaz.am.gov.br/services2/services/NfeRetAutorizacao4',
                'NfeInutilizacao'   => 'https://nfe.sefaz.am.gov.br/services2/services/NfeInutilizacao4',
                'NfeConsulta'       => 'https://nfe.sefaz.am.gov.br/services2/services/NfeConsulta4',
                'NfeStatusServico'  => 'https://nfe.sefaz.am.gov.br/services2/services/NfeStatusServico4',
                'RecepcaoEvento'    => 'https://nfe.sefaz.am.gov.br/services2/services/RecepcaoEvento4',
            ],
        ],

        'BA' => [
            'homologacao' => [
                'NfeAutorizacao'    => 'https://nfce-homologacao.svrs.rs.gov.br/ws/NfeAutorizacao/NFeAutorizacao4.asmx',
                'NfeRetAutorizacao' => 'https://nfce-homologacao.svrs.rs.gov.br/ws/NfeRetAutorizacao/NFeRetAutorizacao4.asmx',
                'NfeInutilizacao'   => 'https://nfce-homologacao.svrs.rs.gov.br/ws/nfeinutilizacao/nfeinutilizacao4.asmx',
                'NfeConsulta'       => 'https://nfce-homologacao.svrs.rs.gov.br/ws/NfeConsulta/NfeConsulta4.asmx',
                'NfeStatusServico'  => 'https://nfce-homologacao.svrs.rs.gov.br/ws/NfeStatusServico/NfeStatusServico4.asmx',
                'RecepcaoEvento'    => 'https://nfce-homologacao.svrs.rs.gov.br/ws/recepcaoevento/recepcaoevento4.asmx',
            ],
            'producao' => [
                'NfeAutorizacao'    => 'https://nfe.sefaz.ba.gov.br/webservices/NFeAutorizacao4/NFeAutorizacao4.asmx',
                'NfeRetAutorizacao' => 'https://nfe.sefaz.ba.gov.br/webservices/NFeRetAutorizacao4/NFeRetAutorizacao4.asmx',
                'NfeInutilizacao'   => 'https://nfe.sefaz.ba.gov.br/webservices/NFeInutilizacao4/NFeInutilizacao4.asmx',
                'NfeConsulta'       => 'https://nfe.sefaz.ba.gov.br/webservices/NFeConsultaProtocolo4/NFeConsultaProtocolo4.asmx',
                'NfeStatusServico'  => 'https://nfe.sefaz.ba.gov.br/webservices/NFeStatusServico4/NFeStatusServico4.asmx',
                'RecepcaoEvento'    => 'https://nfe.sefaz.ba.gov.br/webservices/NFeRecepcaoEvento4/NFeRecepcaoEvento4.asmx',
            ],
        ],

        'CE' => [
            'homologacao' => [
                'NfeAutorizacao'    => 'https://nfce-homologacao.svrs.rs.gov.br/ws/NfeAutorizacao/NFeAutorizacao4.asmx',
                'NfeRetAutorizacao' => 'https://nfce-homologacao.svrs.rs.gov.br/ws/NfeRetAutorizacao/NFeRetAutorizacao4.asmx',
                'NfeInutilizacao'   => 'https://nfce-homologacao.svrs.rs.gov.br/ws/nfeinutilizacao/nfeinutilizacao4.asmx',
                'NfeConsulta'       => 'https://nfce-homologacao.svrs.rs.gov.br/ws/NfeConsulta/NfeConsulta4.asmx',
                'NfeStatusServico'  => 'https://nfce-homologacao.svrs.rs.gov.br/ws/NfeStatusServico/NfeStatusServico4.asmx',
                'RecepcaoEvento'    => 'https://nfce-homologacao.svrs.rs.gov.br/ws/recepcaoevento/recepcaoevento4.asmx',
            ],
            'producao' => [
                'NfeAutorizacao'    => 'https://nfe.sefaz.ce.gov.br/nfe4/services/NFeAutorizacao4?WSDL',
                'NfeRetAutorizacao' => 'https://nfe.sefaz.ce.gov.br/nfe4/services/NFeRetAutorizacao4?WSDL',
                'NfeInutilizacao'   => 'https://nfe.sefaz.ce.gov.br/nfe4/services/NFeInutilizacao4?WSDL',
                'NfeConsulta'       => 'https://nfe.sefaz.ce.gov.br/nfe4/services/NFeConsultaProtocolo4?WSDL',
                'NfeStatusServico'  => 'https://nfe.sefaz.ce.gov.br/nfe4/services/NFeStatusServico4?WSDL',
                'RecepcaoEvento'    => 'https://nfe.sefaz.ce.gov.br/nfe4/services/NFeRecepcaoEvento4?WSDL',
            ],
        ],

        'GO' => [
            'homologacao' => [
                'NfeAutorizacao'    => 'https://homolog.sefaz.go.gov.br/nfe/services/NFeAutorizacao4?wsdl',
                'NfeRetAutorizacao' => 'https://homolog.sefaz.go.gov.br/nfe/services/NFeRetAutorizacao4?wsdl',
                'NfeInutilizacao'   => 'https://homolog.sefaz.go.gov.br/nfe/services/NFeInutilizacao4?wsdl',
                'NfeConsulta'       => 'https://homolog.sefaz.go.gov.br/nfe/services/NFeConsultaProtocolo4?wsdl',
                'NfeStatusServico'  => 'https://homolog.sefaz.go.gov.br/nfe/services/NFeStatusServico4?wsdl',
                'RecepcaoEvento'    => 'https://homolog.sefaz.go.gov.br/nfe/services/NFeRecepcaoEvento4?wsdl',
                'CadConsulta'       => 'https://homolog.sefaz.go.gov.br/nfe/services/v2/CadConsultaCadastro2?wsdl',
            ],
            'producao' => [
                'NfeAutorizacao'    => 'https://nfe.sefaz.go.gov.br/nfe/services/NFeAutorizacao4?wsdl',
                'NfeRetAutorizacao' => 'https://nfe.sefaz.go.gov.br/nfe/services/NFeRetAutorizacao4?wsdl',
                'NfeInutilizacao'   => 'https://nfe.sefaz.go.gov.br/nfe/services/NFeInutilizacao4?wsdl',
                'NfeConsulta'       => 'https://nfe.sefaz.go.gov.br/nfe/services/NFeConsultaProtocolo4?wsdl',
                'NfeStatusServico'  => 'https://nfe.sefaz.go.gov.br/nfe/services/NFeStatusServico4?wsdl',
                'RecepcaoEvento'    => 'https://nfe.sefaz.go.gov.br/nfe/services/NFeRecepcaoEvento4?wsdl',
                'CadConsulta'       => 'https://nfe.sefaz.go.gov.br/nfe/services/v2/CadConsultaCadastro2?wsdl',
            ],
        ],

        'MG' => [
            'homologacao' => [
                'NfeAutorizacao'    => 'https://hnfce.fazenda.mg.gov.br/nfce/services/NFeAutorizacao4',
                'NfeRetAutorizacao' => 'https://hnfce.fazenda.mg.gov.br/nfce/services/NFeRetAutorizacao4',
                'NfeInutilizacao'   => 'https://hnfce.fazenda.mg.gov.br/nfce/services/NFeInutilizacao4',
                'NfeConsulta'       => 'https://hnfce.fazenda.mg.gov.br/nfce/services/NFeConsultaProtocolo4',
                'NfeStatusServico'  => 'https://hnfce.fazenda.mg.gov.br/nfce/services/NFeStatusServico4',
                'RecepcaoEvento'    => 'https://hnfce.fazenda.mg.gov.br/nfce/services/NFeRecepcaoEvento4',
            ],
            'producao' => [
                'NfeAutorizacao'    => 'https://nfe.fazenda.mg.gov.br/nfe2/services/NFeAutorizacao4',
                'NfeRetAutorizacao' => 'https://nfe.fazenda.mg.gov.br/nfe2/services/NFeRetAutorizacao4',
                'NfeInutilizacao'   => 'https://nfe.fazenda.mg.gov.br/nfe2/services/NFeInutilizacao4',
                'NfeConsulta'       => 'https://nfe.fazenda.mg.gov.br/nfe2/services/NFeConsultaProtocolo4',
                'NfeStatusServico'  => 'https://nfe.fazenda.mg.gov.br/nfe2/services/NFeStatusServico4',
                'RecepcaoEvento'    => 'https://nfe.fazenda.mg.gov.br/nfe2/services/NFeRecepcaoEvento4',
            ],
        ],

        'MS' => [
            'homologacao' => [
                'NfeAutorizacao'    => 'https://hom.nfce.sefaz.ms.gov.br/ws/NFeAutorizacao4',
                'NfeRetAutorizacao' => 'https://hom.nfce.sefaz.ms.gov.br/ws/NFeRetAutorizacao4',
                'NfeInutilizacao'   => 'https://hom.nfce.sefaz.ms.gov.br/ws/NFeInutilizacao4',
                'NfeConsulta'       => 'https://hom.nfce.sefaz.ms.gov.br/ws/NFeConsultaProtocolo4',
                'NfeStatusServico'  => 'https://hom.nfce.sefaz.ms.gov.br/ws/NFeStatusServico4',
                'RecepcaoEvento'    => 'https://hom.nfce.sefaz.ms.gov.br/ws/NFeRecepcaoEvento4',
            ],
            'producao' => [
                'NfeAutorizacao'    => 'https://nfe.sefaz.ms.gov.br/ws/NFeAutorizacao4',
                'NfeRetAutorizacao' => 'https://nfe.sefaz.ms.gov.br/ws/NFeRetAutorizacao4',
                'NfeInutilizacao'   => 'https://nfe.sefaz.ms.gov.br/ws/NFeInutilizacao4',
                'NfeConsulta'       => 'https://nfe.sefaz.ms.gov.br/ws/NFeConsultaProtocolo4',
                'NfeStatusServico'  => 'https://nfe.sefaz.ms.gov.br/ws/NFeStatusServico4',
                'RecepcaoEvento'    => 'https://nfe.sefaz.ms.gov.br/ws/NFeRecepcaoEvento4',
            ],
        ],

        'MT' => [
            'homologacao' => [
                'NfeAutorizacao'    => 'https://homologacao.sefaz.mt.gov.br/nfcews/services/NfeAutorizacao4',
                'NfeRetAutorizacao' => 'https://homologacao.sefaz.mt.gov.br/nfcews/services/NfeRetAutorizacao4',
                'NfeInutilizacao'   => 'https://homologacao.sefaz.mt.gov.br/nfcews/services/NfeInutilizacao4',
                'NfeConsulta'       => 'https://homologacao.sefaz.mt.gov.br/nfcews/services/NfeConsulta4',
                'NfeStatusServico'  => 'https://homologacao.sefaz.mt.gov.br/nfcews/services/NfeStatusServico4',
                'RecepcaoEvento'    => 'https://homologacao.sefaz.mt.gov.br/nfcews/services/RecepcaoEvento4',
            ],
            'producao' => [
                'NfeAutorizacao'    => 'https://nfe.sefaz.mt.gov.br/nfews/v2/services/NfeAutorizacao4',
                'NfeRetAutorizacao' => 'https://nfe.sefaz.mt.gov.br/nfews/v2/services/NfeRetAutorizacao4',
                'NfeInutilizacao'   => 'https://nfe.sefaz.mt.gov.br/nfews/v2/services/NfeInutilizacao4',
                'NfeConsulta'       => 'https://nfe.sefaz.mt.gov.br/nfews/v2/services/NfeConsulta4',
                'NfeStatusServico'  => 'https://nfe.sefaz.mt.gov.br/nfews/v2/services/NfeStatusServico4',
                'RecepcaoEvento'    => 'https://nfe.sefaz.mt.gov.br/nfews/v2/services/RecepcaoEvento4',
            ],
        ],

        'PE' => [
            'homologacao' => [
                'NfeAutorizacao'    => 'https://nfce-homologacao.svrs.rs.gov.br/ws/NfeAutorizacao/NFeAutorizacao4.asmx',
                'NfeRetAutorizacao' => 'https://nfce-homologacao.svrs.rs.gov.br/ws/NfeRetAutorizacao/NFeRetAutorizacao4.asmx',
                'NfeInutilizacao'   => 'https://nfce-homologacao.svrs.rs.gov.br/ws/nfeinutilizacao/nfeinutilizacao4.asmx',
                'NfeConsulta'       => 'https://nfce-homologacao.svrs.rs.gov.br/ws/NfeConsulta/NfeConsulta4.asmx',
                'NfeStatusServico'  => 'https://nfce-homologacao.svrs.rs.gov.br/ws/NfeStatusServico/NfeStatusServico4.asmx',
                'RecepcaoEvento'    => 'https://nfce-homologacao.svrs.rs.gov.br/ws/recepcaoevento/recepcaoevento4.asmx',
            ],
            'producao' => [
                'NfeAutorizacao'    => 'https://nfe.sefaz.pe.gov.br/nfe-service/services/NFeAutorizacao4',
                'NfeRetAutorizacao' => 'https://nfe.sefaz.pe.gov.br/nfe-service/services/NFeRetAutorizacao4',
                'NfeInutilizacao'   => 'https://nfe.sefaz.pe.gov.br/nfe-service/services/NFeInutilizacao4',
                'NfeConsulta'       => 'https://nfe.sefaz.pe.gov.br/nfe-service/services/NFeConsultaProtocolo4',
                'NfeStatusServico'  => 'https://nfe.sefaz.pe.gov.br/nfe-service/services/NFeStatusServico4',
                'RecepcaoEvento'    => 'https://nfe.sefaz.pe.gov.br/nfe-service/services/NFeRecepcaoEvento4',
            ],
        ],

        'PR' => [
            'homologacao' => [
                'NfeAutorizacao'    => 'https://homologacao.nfce.sefa.pr.gov.br/nfce/NFeAutorizacao4',
                'NfeRetAutorizacao' => 'https://homologacao.nfce.sefa.pr.gov.br/nfce/NFeRetAutorizacao4',
                'NfeInutilizacao'   => 'https://homologacao.nfce.sefa.pr.gov.br/nfce/NFeInutilizacao4',
                'NfeConsulta'       => 'https://homologacao.nfce.sefa.pr.gov.br/nfce/NFeConsultaProtocolo4',
                'NfeStatusServico'  => 'https://homologacao.nfce.sefa.pr.gov.br/nfce/NFeStatusServico4',
                'RecepcaoEvento'    => 'https://homologacao.nfce.sefa.pr.gov.br/nfce/NFeRecepcaoEvento4',
            ],
            'producao' => [
                'NfeAutorizacao'    => 'https://nfe.sefa.pr.gov.br/nfe/NFeAutorizacao4',
                'NfeRetAutorizacao' => 'https://nfe.sefa.pr.gov.br/nfe/NFeRetAutorizacao4',
                'NfeInutilizacao'   => 'https://nfe.sefa.pr.gov.br/nfe/NFeInutilizacao4',
                'NfeConsulta'       => 'https://nfe.sefa.pr.gov.br/nfe/NFeConsultaProtocolo4',
                'NfeStatusServico'  => 'https://nfe.sefa.pr.gov.br/nfe/NFeStatusServico4',
                'RecepcaoEvento'    => 'https://nfe.sefa.pr.gov.br/nfe/NFeRecepcaoEvento4',
            ],
        ],

        'RS' => [
            'homologacao' => [
                'NfeAutorizacao'    => 'https://nfce-homologacao.sefazrs.rs.gov.br/ws/NfeAutorizacao/NFeAutorizacao4.asmx',
                'NfeRetAutorizacao' => 'https://nfce-homologacao.sefazrs.rs.gov.br/ws/NfeRetAutorizacao/NFeRetAutorizacao4.asmx',
                'NfeInutilizacao'   => 'https://nfce-homologacao.sefazrs.rs.gov.br/ws/nfeinutilizacao/nfeinutilizacao4.asmx',
                'NfeConsulta'       => 'https://nfce-homologacao.sefazrs.rs.gov.br/ws/NfeConsulta/NfeConsulta4.asmx',
                'NfeStatusServico'  => 'https://nfce-homologacao.sefazrs.rs.gov.br/ws/NfeStatusServico/NfeStatusServico4.asmx',
                'RecepcaoEvento'    => 'https://nfce-homologacao.sefazrs.rs.gov.br/ws/recepcaoevento/recepcaoevento4.asmx',
            ],
            'producao' => [
                'NfeAutorizacao'    => 'https://nfe.sefazrs.rs.gov.br/ws/NfeAutorizacao/NFeAutorizacao4.asmx',
                'NfeRetAutorizacao' => 'https://nfe.sefazrs.rs.gov.br/ws/NfeRetAutorizacao/NFeRetAutorizacao4.asmx',
                'NfeInutilizacao'   => 'https://nfe.sefazrs.rs.gov.br/ws/nfeinutilizacao/nfeinutilizacao4.asmx',
                'NfeConsulta'       => 'https://nfe.sefazrs.rs.gov.br/ws/NfeConsulta/NfeConsulta4.asmx',
                'NfeStatusServico'  => 'https://nfe.sefazrs.rs.gov.br/ws/NfeStatusServico/NfeStatusServico4.asmx',
                'RecepcaoEvento'    => 'https://nfe.sefazrs.rs.gov.br/ws/recepcaoevento/recepcaoevento4.asmx',
            ],
        ],

        'SP' => [
            'homologacao' => [
                'NfeAutorizacao'    => 'https://homologacao.nfce.fazenda.sp.gov.br/ws/NFeAutorizacao4.asmx',
                'NfeRetAutorizacao' => 'https://homologacao.nfce.fazenda.sp.gov.br/ws/NFeRetAutorizacao4.asmx',
                'NfeInutilizacao'   => 'https://homologacao.nfce.fazenda.sp.gov.br/ws/NFeInutilizacao4.asmx',
                'NfeConsulta'       => 'https://homologacao.nfce.fazenda.sp.gov.br/ws/NFeConsultaProtocolo4.asmx',
                'NfeStatusServico'  => 'https://homologacao.nfce.fazenda.sp.gov.br/ws/NFeStatusServico4.asmx',
                'RecepcaoEvento'    => 'https://homologacao.nfce.fazenda.sp.gov.br/ws/NFeRecepcaoEvento4.asmx',
            ],
            'producao' => [
                'NfeAutorizacao'    => 'https://nfe.fazenda.sp.gov.br/ws/NFeAutorizacao4.asmx',
                'NfeRetAutorizacao' => 'https://nfe.fazenda.sp.gov.br/ws/NFeRetAutorizacao4.asmx',
                'NfeInutilizacao'   => 'https://nfe.fazenda.sp.gov.br/ws/NFeInutilizacao4.asmx',
                'NfeConsulta'       => 'https://nfe.fazenda.sp.gov.br/ws/NFeConsultaProtocolo4.asmx',
                'NfeStatusServico'  => 'https://nfe.fazenda.sp.gov.br/ws/NFeStatusServico4.asmx',
                'RecepcaoEvento'    => 'https://nfe.fazenda.sp.gov.br/ws/NFeRecepcaoEvento4.asmx',
            ],
        ],

        // ── SVRS (Sefaz Virtual RS) — usada por: AC, AL, AP, DF, ES, PB, PI, RJ, RN, RO, RR, SC, SE, TO ──

        'SVRS' => [
            'homologacao' => [
                'NfeAutorizacao'    => 'https://nfe-homologacao.svrs.rs.gov.br/ws/NfeAutorizacao/NFeAutorizacao4.asmx',
                'NfeRetAutorizacao' => 'https://nfe-homologacao.svrs.rs.gov.br/ws/NfeRetAutorizacao/NFeRetAutorizacao4.asmx',
                'NfeInutilizacao'   => 'https://nfe-homologacao.svrs.rs.gov.br/ws/nfeinutilizacao/nfeinutilizacao4.asmx',
                'NfeConsulta'       => 'https://nfe-homologacao.svrs.rs.gov.br/ws/NfeConsulta/NfeConsulta4.asmx',
                'NfeStatusServico'  => 'https://nfe-homologacao.svrs.rs.gov.br/ws/NfeStatusServico/NfeStatusServico4.asmx',
                'RecepcaoEvento'    => 'https://nfe-homologacao.svrs.rs.gov.br/ws/recepcaoevento/recepcaoevento4.asmx',
            ],
            'producao' => [
                'NfeAutorizacao'    => 'https://nfe.svrs.rs.gov.br/ws/NfeAutorizacao/NFeAutorizacao4.asmx',
                'NfeRetAutorizacao' => 'https://nfe.svrs.rs.gov.br/ws/NfeRetAutorizacao/NFeRetAutorizacao4.asmx',
                'NfeInutilizacao'   => 'https://nfe.svrs.rs.gov.br/ws/nfeinutilizacao/nfeinutilizacao4.asmx',
                'NfeConsulta'       => 'https://nfe.svrs.rs.gov.br/ws/NfeConsulta/NfeConsulta4.asmx',
                'NfeStatusServico'  => 'https://nfe.svrs.rs.gov.br/ws/NfeStatusServico/NfeStatusServico4.asmx',
                'RecepcaoEvento'    => 'https://nfe.svrs.rs.gov.br/ws/recepcaoevento/recepcaoevento4.asmx',
            ],
        ],

        // ── SVAN (Sefaz Virtual AN) — usada por: MA, PA, PI ──

        'SVAN' => [
            'homologacao' => [
                'NfeAutorizacao'    => 'https://hom.sefazvirtual.fazenda.gov.br/NFeAutorizacao4/NFeAutorizacao4.asmx',
                'NfeRetAutorizacao' => 'https://hom.sefazvirtual.fazenda.gov.br/NFeRetAutorizacao4/NFeRetAutorizacao4.asmx',
                'NfeInutilizacao'   => 'https://hom.sefazvirtual.fazenda.gov.br/NFeInutilizacao4/NFeInutilizacao4.asmx',
                'NfeConsulta'       => 'https://hom.sefazvirtual.fazenda.gov.br/NFeConsulta4/NFeConsulta4.asmx',
                'NfeStatusServico'  => 'https://hom.sefazvirtual.fazenda.gov.br/NFeStatusServico4/NFeStatusServico4.asmx',
                'RecepcaoEvento'    => 'https://hom.sefazvirtual.fazenda.gov.br/NFeRecepcaoEvento4/NFeRecepcaoEvento4.asmx',
            ],
            'producao' => [
                'NfeAutorizacao'    => 'https://www.sefazvirtual.fazenda.gov.br/NFeAutorizacao4/NFeAutorizacao4.asmx',
                'NfeRetAutorizacao' => 'https://www.sefazvirtual.fazenda.gov.br/NFeRetAutorizacao4/NFeRetAutorizacao4.asmx',
                'NfeInutilizacao'   => 'https://www.sefazvirtual.fazenda.gov.br/NFeInutilizacao4/NFeInutilizacao4.asmx',
                'NfeConsulta'       => 'https://www.sefazvirtual.fazenda.gov.br/NFeConsulta4/NFeConsulta4.asmx',
                'NfeStatusServico'  => 'https://www.sefazvirtual.fazenda.gov.br/NFeStatusServico4/NFeStatusServico4.asmx',
                'RecepcaoEvento'    => 'https://www.sefazvirtual.fazenda.gov.br/NFeRecepcaoEvento4/NFeRecepcaoEvento4.asmx',
            ],
        ],
    ],

    // ================================================================
    // MAPEAMENTO UF → Autorizadora
    // UFs que usam SVRS ou SVAN em vez de serviço próprio (para NF-e)
    // ================================================================
    'UF_AUTORIZADORA' => [
        'AC' => 'SVRS', 'AL' => 'SVRS', 'AP' => 'SVRS',
        'AM' => 'AM',   'BA' => 'BA',   'CE' => 'CE',
        'DF' => 'SVRS', 'ES' => 'SVRS', 'GO' => 'GO',
        'MA' => 'SVAN', 'MG' => 'MG',   'MS' => 'MS',
        'MT' => 'MT',   'PA' => 'SVAN', 'PB' => 'SVRS',
        'PE' => 'PE',   'PI' => 'SVRS', 'PR' => 'PR',
        'RJ' => 'SVRS', 'RN' => 'SVRS', 'RO' => 'SVRS',
        'RR' => 'SVRS', 'RS' => 'RS',   'SC' => 'SVRS',
        'SE' => 'SVRS', 'SP' => 'SP',   'TO' => 'SVRS',
    ],

    // ================================================================
    // QR CODE URLs por UF (para NFC-e)
    // ================================================================
    'QRCODE' => [
        'AC' => 'http://hml.sefaznet.ac.gov.br/nfce/qrcode?',
        'AL' => 'http://nfce.sefaz.al.gov.br/QRCode/consultarNFCe.jsp',
        'AM' => 'http://homnfce.sefaz.am.gov.br/nfceweb/consultarNFCe.jsp?',
        'BA' => 'http://hnfe.sefaz.ba.gov.br/servicos/nfce/qrcode.aspx',
        'CE' => 'http://nfceh.sefaz.ce.gov.br/pages/ShowNFCe.html',
        'DF' => 'http://www.fazenda.df.gov.br/nfce/qrcode?',
        'ES' => 'http://homologacao.sefaz.es.gov.br/ConsultaNFCe/qrcode.aspx?',
        'GO' => 'https://nfewebhomolog.sefaz.go.gov.br/nfeweb/sites/nfce/danfeNFCe',
        'MA' => 'http://www.hom.nfce.sefaz.ma.gov.br/portal/consultarNFCe.jsp?',
        'MG' => 'https://portalsped.fazenda.mg.gov.br/portalnfce/sistema/qrcode.xhtml',
        'MS' => 'http://www.dfe.ms.gov.br/nfce/qrcode?',
        'MT' => 'http://homologacao.sefaz.mt.gov.br/nfce/consultanfce',
        'PA' => 'https://appnfc.sefa.pa.gov.br/portal-homologacao/view/consultas/nfce/nfceForm.seam',
        'PB' => 'http://www.sefaz.pb.gov.br/nfcehom',
        'PE' => 'http://nfcehomolog.sefaz.pe.gov.br/nfce-web/consultarNFCe',
        'PI' => 'http://www.sefaz.pi.gov.br/nfce/qrcode',
        'PR' => 'http://www.fazenda.pr.gov.br/nfce/qrcode/',
        'RJ' => 'https://consultadfe.fazenda.rj.gov.br/consultaNFCe/QRCode',
        'RN' => 'http://hom.nfce.set.rn.gov.br/consultarNFCe.aspx',
        'RO' => 'http://www.nfce.sefin.ro.gov.br/consultanfce/consulta.jsp',
        'RR' => 'http://200.174.88.103:8080/nfce/servlet/qrcode?',
        'RS' => 'https://www.sefaz.rs.gov.br/NFCE/NFCE-COM.aspx?',
        'SC' => 'https://hom.sat.sef.sc.gov.br/nfce/consulta',
        'SE' => 'http://www.hom.nfe.se.gov.br/portal/consultarNFCe.jsp?',
        'SP' => 'https://www.homologacao.nfce.fazenda.sp.gov.br/NFCeConsultaPublica/Paginas/ConsultaQRCode.aspx',
        'TO' => 'http://homologacao.sefaz.to.gov.br/nfce/qrcode',
    ],
];
