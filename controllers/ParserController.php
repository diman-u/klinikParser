<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use console\models\Parser;
use darkdrim\simplehtmldom\SimpleHTMLDom;
use common\entities\City;
use common\entities\Organization;
use common\entities\OrganizationReview as ReviewOrg;
use common\entities\Specialist;
use common\entities\SpecialistReview as ReviewSpec;
use common\entities\User;
use common\entities\Catalog;
use common\entities\OrganizationCatalog;


class ParserController extends Controller{

    public $domain = "http://kliniki-online.ru/";
    public $city;
    public $cityID;

    public function __construct() {
        $this->getCity();
        //Yii::$app->db->createCommand()->truncateTable('organization')->execute();
        Yii::$app->db->createCommand()->truncateTable('organization_review')->execute();
        //Yii::$app->db->createCommand()->truncateTable('specialist')->execute();
        Yii::$app->db->createCommand()->truncateTable('specialist_review')->execute();
    }

    public function connect($url){

        $arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );

        $response = file_get_contents($url, false, stream_context_create($arrContextOptions));

        return $response;
    }

    public function getCity(){

        $data = $this->connect($this->domain);
        $htmlCityList = new SimpleHTMLDom();
        $cityTable = new City();

        foreach($htmlCityList::str_get_html($data)->find('.city_selector a') as $city) {
            $cityTitle = $city->plaintext;
            $objCity = $cityTable::find()->where(['title' => $cityTitle])->one();
            $this->cityID = $objCity->id;
            $this->city = $city->attr['data-city-code'];
            //}

            $this->getCountOrg($this->domain . $this->city . '/clinics/all/');
        }
    }

    public function getCountOrg($url) {

        $data = $this->connect($url);
        $htmlOrgList = new SimpleHTMLDom();

        foreach( $htmlOrgList::str_get_html($data)->find('li.tab_wrapper div') as $item ){
            $val = preg_replace( "/[^0-9]/", '', $item->plaintext );
            $count[] = $val;
        }

        // Organizations
        $num = ($count[0] % 10 != 0) ? ceil($count[0]/10) : $count[0]/10;

        for($i=1; $i<=$num; $i++ ){
            //$this->organizations( $this->domain . $this->city . '/clinics/all/page/' . $i );
        }

        // Specs
        $num = ($count[1] % 10 != 0) ? ceil($count[1]/10) : $count[1]/10;

        for($i=1; $i<=$num; $i++ ){
            $this->specialist( $this->domain . $this->city . '/doctors/all/page/' . $i );
        }
    }

    public function organizations($url) {

        $domain = "http://kliniki-online.ru";
        $data = $this->connect($url);
        $htmlOrgList = new SimpleHTMLDom();
        $htmlOrgDet = new SimpleHTMLDom();
        unset($updateData);

        foreach($htmlOrgList::str_get_html($data)->find('[data-ajax-zone=results] .kliniki_clinic_name a') as $element){

            //links
            $updateData['name'] = $element->plaintext;
            $updateData['link'] = $element->href;
            $data = $this->connect($domain . $element->href);

            //address
            foreach($htmlOrgDet::str_get_html($data)->find('[itemprop=streetAddress]') as $address){
                $updateData['address'] = trim($address->plaintext);
            }

            //Services

            if (!empty( $htmlOrgDet::str_get_html($data)->find('.kliniki_price_list') )) {

                foreach($htmlOrgDet::str_get_html($data)->find('td[itemprop=name]') as $serv0){
                    $updateService['name'][] = trim($serv0->plaintext);
                }

                foreach($htmlOrgDet::str_get_html($data)->find('.kliniki_price_list td span') as $serv0){

                    if( trim($serv0->plaintext) != 'Цена в рублях' ){
                        $price = $serv0->plaintext;
                        $price = str_replace('руб.', '', $price);
                        $price = preg_replace('/\s+/', '', trim($price));
                        //$price = number_format($price);
                        $updateService['price'][] = $price;
                    }
                }
            }

            //desc
            foreach($htmlOrgDet::str_get_html($data)->find('.clinic_details_fullsize h2') as $desc){
                $str = strip_tags($desc->next_sibling('p'));
                $fixed_str = preg_replace('/[\s]{2,}/', ' ', $str);
                $updateData['desc'] = $fixed_str;
            }

            // Rating
            foreach($htmlOrgDet::str_get_html($data)->find('.kliniki_profile_rating') as $rev1){
                $rate = str_replace('Рейтинг клиники ', '', $rev1->attr['title']);
                $rate = number_format($rate, 2, '.', '');
                $updateData['rating'] = rtrim(rtrim($rate, '0'), '.');
            }

            //schedule
            if (!empty($htmlOrgDet::str_get_html($data)->find('.reminder_paragraph'))) {

                foreach ($htmlOrgDet::str_get_html($data)->find('.reminder_paragraph') as $desc) {

                    $updateData['schedule'] = trim($desc->plaintext);
                }
            }else{
                $updateData['schedule'] = 'n';
            }

            //photo
            foreach($htmlOrgDet::str_get_html($data)->find('.kliniki_preview_photos a') as $photos){
                $ph[] = $photos->href;
            }

            if(isset($ph)){
                $updateData['logo'] = implode(', ', $ph);
            } else {
                $updateData['logo'] = "";
            }

        //Reviews

            //id организации
            foreach($htmlOrgDet::str_get_html($data)->find('.online_appointment_container .js-book-button') as $dataid){
                $idOrg = $dataid->getAttribute('data-id');
            }

            if (!empty($htmlOrgDet::str_get_html($data)->find('.js-switch-tab-review'))) {

                //Вычисление кол-ва ajax запросов
                foreach ($htmlOrgDet::str_get_html($data)->find('.js-switch-tab-review') as $count) {
                    $countRew = substr(trim($count->plaintext), 0, -11);
                }

                $count = ceil((integer)$countRew / 10);

                for ($i = 1; $i <= $count; $i++) {

                    // Формирование url
                    $providerCode = str_replace('/' . $this->city . '/', '', $updateData['link']);
                    $url = $domain . "/reviews/" . $idOrg . "/page/" . $i . "?cityCode=" . $this->city . "&typeCode=clinic&providerCode=" . $providerCode;
                    $data = $this->connect($url);

                    foreach ($htmlOrgDet::str_get_html($data)->find('[itemprop=description]') as $desc) {
                        $revs[] = strip_tags($desc);
                    }

                    foreach ($htmlOrgDet::str_get_html($data)->find('p.kliniki_review_color') as $createdAt) {
                        $created[] = strip_tags($createdAt->plaintext);
                    }

                    foreach ($htmlOrgDet::str_get_html($data)->find('.smile itemprop="ratingValue"') as $rating) {
                        $like[] = $rating->getAttribute('content');
                    }

                    if (isset($revs)) {
                        $updateData['reviews']['text'] = $revs;
                        $updateData['reviews']['createdAt'] = $created;
                        $updateData['reviews']['rating'] = $like;
                        $updateData['reviews']['link'] = $updateData['link'];
                        unset($revs);
                    }

                    if (isset($updateData['reviews'])) {
                        $this->actionUpdateReviewsOrg($updateData['reviews']);
                    }

                }
            }

            $this->actionUpdateOrg($updateData);

//            if (isset($updateService)) {
//                $updateService['link'] = $updateData['link'];
//                $this->actionUpdateServicesOrg($updateService);
//            }
        }
    }

    public function specialist($url) {

        $domain = "http://kliniki-online.ru";
        $data = $this->connect($url);
        $htmlDocList = new SimpleHTMLDom();
        $htmlDocDet = new SimpleHTMLDom();
        unset($updateData);
        unset($updateReviews);

        foreach($htmlDocList::str_get_html($data)->find('[data-ajax-zone=results] .kliniki_clinic_name a') as $element){

            $data = $this->connect($domain . $element->href);
            $updateData['link'] = $element->href;
            $updateReviews['link'] = $element->href;

            // OrganizationId
            if (!empty($htmlDocDet::str_get_html($data)->find('.clinic_header a'))) {

                foreach ($htmlDocDet::str_get_html($data)->find('.clinic_header a') as $title) {
                    $updateData['OrganizationId'] = $title->href;
                }
            } else {
                $updateData['OrganizationId'] = 0;
            }

            //Name
            foreach($htmlDocDet::str_get_html($data)->find('h1[itemprop=name]') as $name){
                $updateData['name'] = $name->plaintext;
            }

            //Desc
            foreach($htmlDocDet::str_get_html($data)->find('span[itemprop=name]') as $desc){
                $updateData['desc'] = strip_tags($desc);
            }

            // Photo
            foreach($htmlDocDet::str_get_html($data)->find('.kliniki_doctor_photo img') as $photos){
                $updateData['photo'] =  $photos->src;
            }

            // Price
            if(!empty($htmlDocDet::str_get_html($data)->find('[itemprop=priceRange]'))) {

                foreach ($htmlDocDet::str_get_html($data)->find('[itemprop=priceRange]') as $price) {
                    $price = str_replace('руб.', '', $price->plaintext);
                    $price = str_replace(' ', '', $price);
                    $price = preg_replace('/\s+/', ' ', $price);
                    $price = str_replace("	", " ", $price);
                    $price = preg_replace('!\s++!u', ' ', $price);
                    while (strpos($price, "  ") !== false) {
                        $price = str_replace("  ", " ", $price);
                    }
                    //$price = number_format($price, 2, '.', '');
                    $updateData['price'] = rtrim(rtrim($price, '0'), '.');
                }
            }else{
                $updateData['price'] = 0;
            }

            // Services
            foreach($htmlDocDet::str_get_html($data)->find('.kliniki_doctor_description div') as $service){
                $updateData['service'] = $service->plaintext;
            }

            // Stage
            foreach($htmlDocDet::str_get_html($data)->find('.kliniki_doctor_resume') as $stage){
                $info = $stage->find("p", 0);
                $updateData['experiance'] = $str = preg_replace("/[^0-9]/", '', $info->plaintext);
            }

            // Rating
            foreach($htmlDocDet::str_get_html($data)->find('.kliniki_resource_ratings .kliniki_rating') as $rev1){
                $rate = str_replace('Рейтинг врача ', '', $rev1->attr['title']);
                $rate = number_format($rate, 2, '.', '');
                $updateData['rating'] = rtrim(rtrim($rate, '0'), '.');
            }

            //Reviews
            if(!empty( $htmlDocDet::str_get_html($data)->find('.kliniki_profile_reviews_count') )) {

                foreach ($htmlDocDet::str_get_html($data)->find('.kliniki_profile_reviews_count') as $count) {
                    (integer)$str = preg_replace("/[^0-9]/", '', trim($count->plaintext));
                    $count = ceil(($str / 10));
                }

                foreach ($htmlDocDet::str_get_html($data)->find('.js-book-button') as $dataid) {
                    $idDoc = $dataid->getAttribute('data-id');
                }

                for ($i = 2; $i <= $count+1; $i++) {

                    //url
                    $providerCode = str_replace('/' . $this->city . '/', '', $updateData['link']);
                    echo "\n" . $url = $domain . "/reviews/" . $idDoc . "/page/" . $i . "?cityCode=" . $this->city . "&typeCode=doctor&providerCode=" . $providerCode;
                    $data = $this->connect($url);

                    //Text
                    foreach ($htmlDocDet::str_get_html($data)->find('#reviews-list p.kliniki_review_text') as $desc) {
                        $revs[] = strip_tags($desc);
                    }

                    foreach ($htmlDocDet::str_get_html($data)->find('p.kliniki_review_color') as $createdAt) {
                        $created[] = strip_tags($createdAt->plaintext);
                    }

                    foreach ($htmlDocDet::str_get_html($data)->find('.smile itemprop="ratingValue"') as $rating) {
                        $like[] = $rating->getAttribute('content');
                    }
                }

                if (isset($revs)) {
                    $updateData['reviews']['text'] = $revs;
                    $updateData['reviews']['created'] = $created;
                    $updateData['reviews']['rating'] = $like;
                    $updateData['reviews']['link'] = $updateData['link'];
                    unset($revs);
                }
            }

            $this->actionUpdateSpec($updateData);
            if(isset($updateData['reviews'])){
                $this->actionUpdateReviewsSpec($updateData['reviews']);
            }
        }
    }

// Классы для работы с БД

    public function actionUpdateOrg($data) {

        $orgEnt = new Organization();
        $org = $orgEnt::find()->where(['link'=>$data['link']])->exists();

        if ( !$org ){
            $orgEnt->name = $data['name'];
            $orgEnt->cityId = $this->cityID;
            $orgEnt->link = $data['link'];
            $orgEnt->address = $data['address'];
            $orgEnt->rating = $data['rating'];
            $orgEnt->schedule = $data['schedule'];
            $orgEnt->logo = $data['logo'];
            $orgEnt->status = 'active';
            if($orgEnt->save()){
                echo "Организация добавлена\n";
            }
        } else{
            echo "Организация существует\n";
        }
    }

    public function actionUpdateSpec($data) {

        $specEnt = new Parser();
        $isSpec = $specEnt::find()->where(['link'=>$data['link']])->exists();
        $org = new Organization();
        $orgId = $org::find()->where(['link'=>$data['OrganizationId']])->one();
        $orgId = (empty($orgId))? 0 : $orgId->id ;

        if ( !$isSpec ){
            $specEnt->name = $data['name'];
            $specEnt->organizationId = $orgId;
            $specEnt->link = $data['link'];
            $specEnt->description = $data['desc'];
            $specEnt->photo = $data['photo'];
            $specEnt->cost = $data['price'];
            //$spec->service = $data['service'];
            $specEnt->experience = $data['experiance'];
            $specEnt->rating = $data['rating'];
            $specEnt->status = 'active';
            $specEnt->createdAt = 18082018;
            $specEnt->organizationId = 1;
            if( $specEnt->save() ){
                echo "Специалист добавлен\n";
            }
        }else{
            echo "Специалист существует\n";
        }
    }

    public function actionUpdateReviewsOrg($data) {

        $user = new User();
        $userID = $user::find()->one();
        $idOrg = Organization::findOne(['link'=>$data['link']]);

        if(empty($idOrg)){
            $idOrg = 1;
        }else{
            $idOrg = $idOrg->id;
        }

        for($i=0; $i<=count($data['text'])-1; $i++){
            $revEnt = new ReviewOrg();
            $revEnt->userId = $userID->id;
            $revEnt->organizationId = $idOrg;
            $revEnt->text = $data['text'][$i];
            $revEnt->rating = 1;
            $revEnt->createdAt = $data['createdAt'][$i];

            if($revEnt->save()){
                echo "Запись в отзывы добавлена\n";
            }
            unset($revEnt);
        }
    }

    public function actionUpdateReviewsSpec($data) {

        $user = new User();
        $userID = $user::find()->one();

        $spec = new Specialist();
        $idSpec = $spec::find()->where(['link'=>$data['link']])->one();

        foreach ($data['text'] as $value){
            $specEnt = new ReviewSpec();
            $specEnt->userId = $userID->id;
            $specEnt->specialistId = $idSpec->id;
            $specEnt->text = $value;
            $specEnt->rating = $data['rating'];
            $specEnt->createdAt = $data['created'];
            if($specEnt->save()){
                echo "Запись в отзывы добавлена\n";
            }
        }
    }

    public function actionUpdateServicesOrg($data) {

        $orgServ = new OrganizationCatalog();
        $catalog = new Catalog();
        $idOrg = Organization::findOne(['link' => $data['link']]);


        foreach ($data['name'] as $key=>$value) {

            if ( Catalog::find()->where(['title'=>$value])->exists()) {
                $idCat = Catalog::findOne(['title'=>$value]);
            }else{
                $catalog->parentId = null;
                $catalog->title = $value;
                $catalog->save();
                $idCat = Catalog::findOne(['title'=>$value]);
            }

            $orgServ->catalogId = isset($idCat->id)? $idCat->id : 1;
            $orgServ->organizationId = isset($idOrg->id)? $idOrg->id : 0;
            $orgServ->price = isset($data['price'][$key])? $data['price'][$key] : 0;
            //$orgServ->price = 1000;
            $orgServ->time = isset($data['time'][$key])? $data['time'][$key] : null;
            if($orgServ->save()){
                echo "Запись в сервисы добавлена\n";
            }
        }
    }

}