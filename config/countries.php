<?php

/*
| Country dial codes for the WhatsApp input.
| Format: ISO2|dial|Arabic name|English name  (flag emoji is derived from ISO2)
| Priority markets first, then the full list — both are searchable in the UI.
*/

$raw = <<<'LIST'
SA|966|السعودية|Saudi Arabia
EG|20|مصر|Egypt
AE|971|الإمارات|United Arab Emirates
KW|965|الكويت|Kuwait
QA|974|قطر|Qatar
BH|973|البحرين|Bahrain
OM|968|عمان|Oman
JO|962|الأردن|Jordan
LB|961|لبنان|Lebanon
IQ|964|العراق|Iraq
SY|963|سوريا|Syria
YE|967|اليمن|Yemen
PS|970|فلسطين|Palestine
SD|249|السودان|Sudan
LY|218|ليبيا|Libya
TN|216|تونس|Tunisia
DZ|213|الجزائر|Algeria
MA|212|المغرب|Morocco
MR|222|موريتانيا|Mauritania
SO|252|الصومال|Somalia
DJ|253|جيبوتي|Djibouti
KM|269|جزر القمر|Comoros
TR|90|تركيا|Turkey
GB|44|المملكة المتحدة|United Kingdom
US|1|الولايات المتحدة|United States
CA|1|كندا|Canada
DE|49|ألمانيا|Germany
FR|33|فرنسا|France
IT|39|إيطاليا|Italy
ES|34|إسبانيا|Spain
NL|31|هولندا|Netherlands
BE|32|بلجيكا|Belgium
CH|41|سويسرا|Switzerland
AT|43|النمسا|Austria
SE|46|السويد|Sweden
NO|47|النرويج|Norway
DK|45|الدنمارك|Denmark
FI|358|فنلندا|Finland
IE|353|إيرلندا|Ireland
PT|351|البرتغال|Portugal
GR|30|اليونان|Greece
PL|48|بولندا|Poland
CZ|420|التشيك|Czechia
RO|40|رومانيا|Romania
HU|36|المجر|Hungary
BG|359|بلغاريا|Bulgaria
RU|7|روسيا|Russia
UA|380|أوكرانيا|Ukraine
CN|86|الصين|China
JP|81|اليابان|Japan
KR|82|كوريا الجنوبية|South Korea
IN|91|الهند|India
PK|92|باكستان|Pakistan
BD|880|بنغلاديش|Bangladesh
LK|94|سريلانكا|Sri Lanka
NP|977|نيبال|Nepal
ID|62|إندونيسيا|Indonesia
MY|60|ماليزيا|Malaysia
SG|65|سنغافورة|Singapore
TH|66|تايلاند|Thailand
VN|84|فيتنام|Vietnam
PH|63|الفلبين|Philippines
AU|61|أستراليا|Australia
NZ|64|نيوزيلندا|New Zealand
ZA|27|جنوب أفريقيا|South Africa
NG|234|نيجيريا|Nigeria
KE|254|كينيا|Kenya
GH|233|غانا|Ghana
ET|251|إثيوبيا|Ethiopia
TZ|255|تنزانيا|Tanzania
UG|256|أوغندا|Uganda
SN|221|السنغال|Senegal
CI|225|ساحل العاج|Côte d'Ivoire
CM|237|الكاميرون|Cameroon
BR|55|البرازيل|Brazil
AR|54|الأرجنتين|Argentina
MX|52|المكسيك|Mexico
CL|56|تشيلي|Chile
CO|57|كولومبيا|Colombia
PE|51|بيرو|Peru
IR|98|إيران|Iran
AF|93|أفغانستان|Afghanistan
AZ|994|أذربيجان|Azerbaijan
KZ|7|كازاخستان|Kazakhstan
UZ|998|أوزبكستان|Uzbekistan
GE|995|جورجيا|Georgia
AM|374|أرمينيا|Armenia
CY|357|قبرص|Cyprus
MT|356|مالطا|Malta
IL|972|إسرائيل|Israel
HK|852|هونغ كونغ|Hong Kong
TW|886|تايوان|Taiwan
MV|960|المالديف|Maldives
LIST;

$countries = [];

foreach (explode("\n", trim($raw)) as $line) {
    [$iso, $dial, $ar, $en] = explode('|', $line);

    $flag = '';
    foreach (str_split(strtoupper($iso)) as $char) {
        $flag .= mb_chr(0x1F1E6 + (ord($char) - 65), 'UTF-8');
    }

    $countries[] = [
        'iso' => $iso,
        'dial' => '+'.$dial,
        'name_ar' => $ar,
        'name_en' => $en,
        'flag' => $flag,
    ];
}

return [
    'default' => 'SA',
    'list' => $countries,
];
