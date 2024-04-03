<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    'name' => 'Testtype',
    'filenamePrefix' => 'abc',
    'filenameSuffix' => 'def',
    'documentNumber' => '',
    'global' => true,
    'config' => [
        'pageOrientation' => 'portrait',
        'pageSize' => 'letter',
        'displayHeader' => true,
        'displayFooter' => true,
        'displayLineItems' => true,
        'displayPageCount' => true,
        'displayPrices' => true,
        'displayLineItemPosition' => true,
        'itemsPerPage' => '5',
        'displayCompanyAddress' => true,
        'companyAddress' => 'a',
        'companyName' => 'b',
        'companyEmail' => 'c@d.ef',
        'companyUrl' => 'g',
        'taxNumber' => 'h',
        'taxOffice' => 'i',
        'vatId' => 'j',
        'bankName' => 'k',
        'bankIban' => 'l',
        'bankBic' => 'm',
        'placeOfJurisdiction' => 'n',
        'placeOfFulfillment' => 'o',
        'executiveDirector' => 'p',
    ],
    'salesChannels' => [
        [
            'id' => '3e447eb526c44d6a8d5397581ba2eb31',
            'salesChannelId' => null,
            'documentType' => [
                'name' => 'Delivery note',
                'technicalName' => 'delivery_note',
                'id' => '3292943c32e5499f9a54cc5ff1a16abe',
            ],
        ],
    ],
    'documentType' => [
        'name' => 'Delivery note',
        'technicalName' => 'delivery_note',
        'id' => '3292943c32e5499f9a54cc5ff1a16abe',
    ],
    'id' => '285c94d513214fbdafd2b2ba84d630e4',
];
