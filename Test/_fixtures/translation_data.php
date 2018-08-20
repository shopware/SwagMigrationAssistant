<?php declare(strict_types=1);

return [
    0 => [
            'id' => '3',
            'objecttype' => 'custom_facet',
            'objectdata' => 'a:1:{i:0;a:1:{s:5:"label";s:9:"Varianten";}}',
            'objectkey' => '1',
            'objectlanguage' => '1',
            'dirty' => '0',
            '_locale' => 'de_DE',
            'name' => null,
        ],
    1 => [
            'id' => '4',
            'objecttype' => 'article',
            'objectdata' => 'a:6:{s:9:"metaTitle";s:23:"Variant product - title";s:10:"txtArtikel";s:15:"Variant product";s:19:"txtshortdescription";s:35:"Variant product - short description";s:19:"txtlangbeschreibung";s:36:"<p>Variant product - description</p>";s:11:"txtkeywords";s:26:"Variant product - keywords";s:11:"txtpackunit";s:12:"package unit";}',
            'objectkey' => '5',
            'objectlanguage' => '3',
            'dirty' => '1',
            '_locale' => 'en_GB',
            'name' => null,
        ],
    2 => [
            'id' => '5',
            'objecttype' => 'article',
            'objectdata' => 'a:6:{s:9:"metaTitle";s:20:"Main product - title";s:10:"txtArtikel";s:12:"Main product";s:19:"txtshortdescription";s:32:"Main product - short description";s:19:"txtlangbeschreibung";s:33:"<p>Main product - description</p>";s:11:"txtkeywords";s:23:"Main product - keywords";s:11:"txtpackunit";s:12:"package unit";}',
            'objectkey' => '1',
            'objectlanguage' => '3',
            'dirty' => '1',
            '_locale' => 'en_GB',
            'name' => null,
        ],
    3 => [
            'id' => '1',
            'objecttype' => 'config_mails',
            'objectdata' => 'a:4:{s:8:"fromMail";s:18:"{config name=mail}";s:8:"fromName";s:22:"{config name=shopName}";s:7:"subject";s:38:"Documents to your order {$orderNumber}";s:7:"content";s:331:"{include file="string:{config name=emailheaderplain}"}

Hello {$sUser.salutation|salutation} {$sUser.firstname} {$sUser.lastname},

Thank you for your order at {config name=shopName}. In the attachement you will find documents about your order as PDF.
We wish you a nice day.

{include file="string:{config name=emailfooterplain}"}";}',
            'objectkey' => '64',
            'objectlanguage' => '2',
            'dirty' => '0',
            '_locale' => null,
            'name' => null,
        ],
    4 => [
            'id' => '2',
            'objecttype' => 'documents',
            'objectdata' => 'a:4:{i:1;a:1:{s:4:"name";s:7:"Invoice";}i:2;a:1:{s:4:"name";s:18:"Notice of delivery";}i:3;a:1:{s:4:"name";s:6:"Credit";}i:4;a:1:{s:4:"name";s:12:"Cancellation";}}',
            'objectkey' => '1',
            'objectlanguage' => '2',
            'dirty' => '0',
            '_locale' => null,
            'name' => null,
        ],
];
