<?php

namespace console\controllers;

use common\entities\OrganizationPhoto;
use common\entities\OrganizationReview;
use common\entities\SpecialistPhoto;
use common\entities\SpecialistReview;
use common\enums\OrganizationStatus;
use common\enums\SpecialistStatus;
use Yii;
use yii\console\Controller;
use keltstr\simplehtmldom\SimpleHTMLDom;
//use darkdrim\simplehtmldom\SimpleHTMLDom;
use common\entities\City;
use common\entities\Organization;
use common\entities\Specialist;
use common\entities\User;
use common\entities\Catalog;
use common\entities\OrganizationCatalog;


class ParserController extends Controller{

    public $domain = "http://kliniki-online.ru/";
    public $domain2 = "http://kliniki-online.ru";
    public $city;
    public $cityID;
    public $minCountReviews = 30;
    public $maxCountReviews = 50;

    public function __construct() {
        $this->getCity();

        $orgRevTbl = OrganizationReview::tableName();
        $orgTbl = Organization::tableName();
        Yii::$app->db->createCommand()->setSql("delete r from {$orgRevTbl} as r inner join {$orgTbl} as o on o.id = r.organizationId where o.isParsed = 1")->execute();

        $specRevTbl = SpecialistReview::tableName();
        $specTbl = Specialist::tableName();
        Yii::$app->db->createCommand()->setSql("delete r from {$specRevTbl} as r inner join {$specTbl} as s on s.id = r.specialistId where s.isParsed = 1")->execute();
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

        foreach($htmlCityList::str_get_html($data)->find('.city_selector a') as $cityData) {
            $cityTitle = $cityData->plaintext;

            if (!$city = City::find()->where('upper(title) = :title', [':title' => strtoupper($cityTitle)])->one()) {
                echo "Город {$cityTitle} не найден в БД";
                continue;
            }

            $this->cityID = $city->getPrimaryKey();
            $this->city = $cityData->attr['data-city-code'];
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

        for ($i = 1; $i<=$num; $i++ ) {
            $this->organizations( $this->domain . $this->city . '/clinics/all/page/' . $i );
        }

        // Specs
        $num = ($count[1] % 10 != 0) ? ceil($count[1]/10) : $count[1]/10;

        for ($i = 31; $i<=$num; $i++ ) {
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
                        $price = preg_replace( "/[^0-9]/", '', $serv0->plaintext );
                        $updateService['price'][] = (int)$price;
                    }
                }
            }

            //desc
            foreach($htmlOrgDet::str_get_html($data)->find('.clinic_details_fullsize h2') as $desc){
                if($desc->plaintext=="Описание") {
                    $str = strip_tags($desc->next_sibling('p'));
                    $fixed_str = preg_replace('/[\s]{2,}/', ' ', $str);
                    $updateData['desc'] = $fixed_str;
                }
            }

            // Rating
            foreach($htmlOrgDet::str_get_html($data)->find('.kliniki_profile_rating') as $rev1){
                $rate = str_replace('Рейтинг клиники ', '', $rev1->attr['title']);
                $rate = number_format($rate, 2, '.', '');
                $updateData['rating'] = (float)rtrim(rtrim($rate, '0'), '.');
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
            foreach($htmlOrgDet::str_get_html($data)->find('.kliniki_profile_photos a') as $photos){
                $photo['photo'][] = $photos->href;
            }

            foreach($htmlOrgDet::str_get_html($data)->find('.kliniki_profile_photos a') as $photos){
                $style = $photos->getAttribute('style');
                $style = str_replace("background-image: url('", '', $style);
                $style = str_replace("')", '', $style);
                $photo['photoSmall'][] = $style;
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
                    $countRew = preg_replace( "/[^0-9]/", '', $countRew );
                }

                //random
                $countRew = ($countRew > $this->maxCountReviews )? rand( $this->minCountReviews, $this->maxCountReviews) : $countRew;

                $count = ceil((integer)$countRew / 10);

                for ($i = 2; $i <= $count+1; $i++) {

                    // Формирование url
                    $providerCode = str_replace('/' . $this->city . '/', '', $updateData['link']);
                    $url = $domain . "/reviews/" . $idOrg . "/page/" . $i. "?cityCode=" . $this->city . "&typeCode=clinic&providerCode=" . $providerCode;
                    $data = $this->connect($url);

                    foreach ($htmlOrgDet::str_get_html($data)->find('[itemprop=description]') as $desc) {
                        $revs[] = strip_tags($desc);
                    }

                    foreach ($htmlOrgDet::str_get_html($data)->find('p.kliniki_review_color') as $createdAt) {
                        $created[] = strip_tags($createdAt->plaintext);
                    }

                    foreach ($htmlOrgDet::str_get_html($data)->find('.smile [itemprop="ratingValue"]') as $rating) {
                        $like[] = $rating->getAttribute('content');
                    }

                    if (isset($revs)) {
                        $updateData['reviews']['text'] = $revs;
                        $updateData['reviews']['createdAt'] = $created;
                        $updateData['reviews']['rating'] = $like;
                        $updateData['reviews']['link'] = $updateData['link'];
                    }
                }
            }

            $this->actionUpdateOrg($updateData);

            if(isset($photo)){
                $this->actionUpdatePhotoOrg($photo, $updateData['link']);
            }

            if (isset($updateData['reviews'])) {
                $this->actionUpdateReviewsOrg($updateData['reviews']);
            }


            if (isset($updateService)) {
                $updateService['link'] = $updateData['link'];
                $this->actionUpdateServicesOrg($updateService);
            }
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

            //fullDesc
            foreach($htmlDocDet::str_get_html($data)->find('[itemprop=description]') as $desc){
                $fixed_str = preg_replace('/[\s]{2,}/', ' ', $desc->plaintext);
                $updateData['fullDesc'] = $fixed_str;
            }

            // Photo
            foreach($htmlDocDet::str_get_html($data)->find('.kliniki_doctor_photo img') as $photos){
                $photo =  $photos->src;
            }

            // Price
            if(!empty($htmlDocDet::str_get_html($data)->find('[itemprop=priceRange]'))) {

                foreach ($htmlDocDet::str_get_html($data)->find('[itemprop=priceRange]') as $price) {
                    $price = preg_replace("/[^0-9]/", '', $price->plaintext);
                    $price = number_format($price, 2, '.', '');
                    $price = rtrim(rtrim($price, '0'), '.');
                    $updateData['price'] = (int)$price;
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
                $updateData['experience'] = (int)preg_replace("/[^0-9]/", '', $info->plaintext);
            }

            // Rating
            foreach($htmlDocDet::str_get_html($data)->find('.kliniki_resource_ratings .kliniki_rating') as $rev1){
                $rate = str_replace('Рейтинг врача ', '', $rev1->attr['title']);
                $rate = number_format($rate, 2, '.', '');
                $updateData['rating'] = (float)rtrim(rtrim($rate, '0'), '.');
            }

            //Reviews
            if(!empty( $htmlDocDet::str_get_html($data)->find('.kliniki_profile_reviews_count') )) {

                foreach ($htmlDocDet::str_get_html($data)->find('.kliniki_profile_reviews_count') as $count) {
                    (integer)$count = preg_replace("/[^0-9]/", '', trim($count->plaintext));
                    $count = ($count > $this->maxCountReviews )? rand( $this->minCountReviews, $this->maxCountReviews) : $count;
                    $count = ceil(($count / 10));
                }

                foreach ($htmlDocDet::str_get_html($data)->find('.js-book-button') as $dataid) {
                    $idDoc = $dataid->getAttribute('data-id');
                }

                for ($i = 2; $i <= $count+1; $i++) {

                    //url
                    $providerCode = str_replace('/' . $this->city . '/', '', $updateData['link']);
                    $url = $domain . "/reviews/" . $idDoc . "/page/" . $i . "?cityCode=" . $this->city . "&typeCode=doctor&providerCode=" . $providerCode;
                    $data = $this->connect($url);

                    //Text
                    foreach ($htmlDocDet::str_get_html($data)->find('#reviews-list p.kliniki_review_text') as $desc) {
                        $revs[] = strip_tags($desc);
                    }

                    foreach ($htmlDocDet::str_get_html($data)->find('p.kliniki_review_color') as $createdAt) {
                        $created[] = strip_tags($createdAt->plaintext);
                    }

                    foreach ($htmlDocDet::str_get_html($data)->find('.smile itemprop="ratingValue"') as $rating) {
                        $like[] = (float)$rating->getAttribute('content');
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

            if (isset($photo)) {
                $this->actionUpdatePhotoSpec($photo, $updateData['link']);
            }
        }
    }

// Классы для работы с БД

    public function actionUpdateOrg($data) {
        $orgIsExists = Organization::find()->where(['parserLink'=>$data['link']])->exists();

        if ( !$orgIsExists ){
            $org = new Organization();
            $org->isParsed = true;
            $org->name = $data['name'];
            $org->cityId = $this->cityID;
            $org->parserLink = $data['link'];
            $org->address = $data['address'];
            $org->rating = $data['rating'];
            $org->schedule = $data['schedule'];
            $org->description = $data['desc'];
            $org->status = OrganizationStatus::ACTIVE;
            if($org->save()){
                echo "Организация добавлена\n";
            }
        } else{
            echo "Организация существует\n";
        }
    }

    public function actionUpdateSpec($data) {
        if (!$org = Organization::findOne(['parserLink'=>$data['OrganizationId']])) {
            echo "Запись в отзывы НЕ добавлена, орагинизация не найдена ({$data['link']})\n";
            return;
        }

        $specIsExists = Specialist::find()->where(['parserLink'=>$data['link']])->exists();

        if ( !$specIsExists ){
            $spec = new Specialist();
            $org->isParsed = true;
            $spec->name = $data['name'];
            $spec->organizationId = $org->getPrimaryKey();
            $spec->parserLink = $data['link'];
            $spec->description = $data['desc'];
            //$spec->photo = $data['photo'];
            $spec->cost = $data['price'];
            $spec->experience = $data['experience'];
            $spec->rating = $data['rating'];
            $spec->status = SpecialistStatus::ACTIVE;
            $spec->createdAt = 18082018;
            //$spec->organizationId = 1;
            if( $spec->save() ){
                echo "Специалист добавлен\n";
            }
        }else{
            echo "Специалист существует\n";
        }
    }

    public function actionUpdateReviewsOrg($data) {
        if (!$user = User::find()->one()) {
            echo "Запись в отзывы НЕ добавлена, пользоватедль не найден\n";
            return;
        }

        if (!$org = Organization::findOne(['parserLink'=>$data['link']])) {
            echo "Запись в отзывы НЕ добавлена, орагинизация не найдена ({$data['link']})\n";
            return;
        }

        for($i=0; $i<=count($data['text'])-1; $i++){
            $orgReview = new OrganizationReview();
            $orgReview->userId = $user->getPrimaryKey();
            $orgReview->organizationId = $org->getPrimaryKey();
            $orgReview->text = $data['text'][$i];
            $orgReview->rating = rand(1,5);
            $orgReview->createdAt = $data['createdAt'][$i];

            if($orgReview->save()){
                echo "Запись в отзывы добавлена\n";
            }
            unset($revEnt);
        }
    }

    public function actionUpdateReviewsSpec($data) {
        if (!$user = User::find()->one()) {
            echo "Запись в отзывы спец. НЕ добавлена, пользоватедль не найден\n";
            return;
        }

        if (!$spec = Specialist::findOne(['parserLink'=>$data['link']])) {
            echo "Запись в отзывы спец. НЕ добавлена, специалист не найден ({$data['link']})\n";
            return;
        }

        foreach ($data['text'] as $value){
            $specReview = new SpecialistReview();
            $specReview->userId = $user->getPrimaryKey();
            $specReview->specialistId = $spec->getPrimaryKey();
            $specReview->text = $value;
            $specReview->rating = $data['rating'];
            $specReview->createdAt = $data['created'];
            if($specReview->save()){
                echo "Запись в отзывы спец. добавлена\n";
            }
        }
    }

    public function actionUpdateServicesOrg($data) {

        if (!$org = Organization::findOne(['parserLink' => $data['link']])) {
            echo "Запись в услуги НЕ добавлена, организация не найдена ({$data['link']})\n";
            return;
        }

        foreach ($data['name'] as $key=>$value) {

            if (!$catalog = Catalog::findOne(['title'=>$value])) {
                $catalog = new Catalog();
                $catalog->parentId = null;
                $catalog->title = $value;
                $catalog->save();
            }

            $orgCaltalog = new OrganizationCatalog();
            $orgCaltalog->catalogId = $catalog->getPrimaryKey();
            $orgCaltalog->organizationId = $org->getPrimaryKey();
            $orgCaltalog->price = isset($data['price'][$key])? $data['price'][$key] : 0;
            $orgCaltalog->time = isset($data['time'][$key])? $data['time'][$key] : null;
            if($orgCaltalog->save()){
                echo "Запись в услуги добавлена\n";
            }
        }
    }

    public function actionUpdatePhotoOrg($data, $link) {

        if (!$org = Organization::findOne(['parserLink'=>$link])) {
            echo "Запись в отзывы НЕ добавлена, орагинизация не найдена ({$link})\n";
            return;
        }

        foreach ($data['photo'] as $key=>$value) {

            $orgPhoto = new OrganizationPhoto();
            $orgPhoto->organizationId = $org->getPrimaryKey();
            $orgPhoto->photo = $this->domain2 . $data['photo'][$key];
            $orgPhoto->photoSmall = $this->domain2 . $data['photoSmall'][$key];
            $orgPhoto->createdAt = time();
            if($orgPhoto->save()){
                echo "Запись в фото добавлена\n";
            }
        }
    }

    public function actionUpdatePhotoSpec($data, $link) {

        if (!$spec = Specialist::findOne(['parserLink'=>$link])) {
            echo "Запись в фото спец. НЕ добавлена, специалист не найден ({$link})\n";
            return;
        }


        $specPhoto = new SpecialistPhoto();
        $specPhoto->specialistId = $spec->getPrimaryKey();
        $specPhoto->photo = (isset($data)) ? $this->domain2 . $data : null;
        $specPhoto->photoSmall = $this->domain2 . $data;
        $specPhoto->createdAt = time();
        if($specPhoto->save()){
            echo "Запись в фото добавлена\n";
        }
    }

}