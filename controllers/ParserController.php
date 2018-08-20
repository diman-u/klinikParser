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


class ParserController extends Controller{

    public $domain = "http://kliniki-online.ru/";
    public $city;
    public $cityID;

    public function __construct() {
        $this->getCity();
        Yii::$app->db->createCommand()->truncateTable('organization_review')->execute();
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

        foreach($htmlOrgList::str_get_html($data)->find('[data-ajax-zone=results] .kliniki_clinic_name a') as $element){

            //links
            $updateData['name'] = $element->plaintext;
            $updateData['link'] = $element->href;
            $data = $this->connect($domain . $element->href);

            //address
            foreach($htmlOrgDet::str_get_html($data)->find('[itemprop=streetAddress]') as $address){
                $updateData['address'] = trim($address->plaintext);
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

            //reminder
            foreach($htmlOrgDet::str_get_html($data)->find('.reminder_paragraph') as $desc){
                $updateData['schedule'] = trim($desc->plaintext);
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

            foreach($htmlOrgDet::str_get_html($data)->find('.js-book-button') as $dataid){
                 $idOrg = $dataid->getAttribute('data-id');
            }

            $providerCode = str_replace('/'.$this->city.'/', '', $updateData['link']);
            $url = $domain . "/reviews/". $idOrg ."/page/2?cityCode=". $this->city ."&typeCode=clinic&providerCode=" . $providerCode;
            $data = $this->connect($url);

            foreach($htmlOrgDet::str_get_html($data)->find('[itemprop=description]') as $desc){
                $revs[] = strip_tags($desc);
            }

            foreach($htmlOrgDet::str_get_html($data)->find('p.kliniki_review_color') as $createdAt){
                $created[] = strip_tags($createdAt->plaintext);
            }

            foreach($htmlOrgDet::str_get_html($data)->find('span.kliniki_review_color') as $user){
                $userId[] = trim(strip_tags($user->plaintext));
            }

            foreach($htmlOrgDet::str_get_html($data)->find('.smile itemprop="ratingValue"') as $rating){
                $like[] = $rating->getAttribute('content');
            }

            if(isset($revs)){
                $updateData['reviews']['text'] = $revs;
                //Вопрос
                $updateData['reviews']['createdAt'] = $created;
                $updateData['reviews']['rating'] = $like;
                $updateData['reviews']['userId'] = $userId;
                $updateData['reviews']['link'] = $updateData['link'];

                unset($revs);
            }
//
            //print_r($updateData['link']); die();

            //doc
//            foreach($htmlOrgDet::str_get_html($data)->find('.kliniki_profile_doctors .list-view .kliniki_experts_title a') as $docs){
//                //$updateData['docs'] = $docs->plaintext . "\n";
//                //echo $this->domain . $docs->href . "\n";
//            }

            $this->actionUpdateOrg($updateData);
            $this->actionUpdateReviewsOrg($updateData['reviews']);
            unset($updateData);
        }
    }

    public function specialist($url) {

        $domain = "http://kliniki-online.ru";
        $data = $this->connect($url);
        $htmlDocList = new SimpleHTMLDom();
        $htmlDocDet = new SimpleHTMLDom();

        foreach($htmlDocList::str_get_html($data)->find('[data-ajax-zone=results] .kliniki_clinic_name a') as $element){

            $data = $this->connect($domain . $element->href);
            $updateData['link'] = $element->href;

            //Name
            foreach($htmlDocDet::str_get_html($data)->find('h1[itemprop=name]') as $name){
                $updateData['name'] = $name->plaintext;
            }

            //Desc
            foreach($htmlDocDet::str_get_html($data)->find('span[itemprop=name]') as $desc){
                $updateData['desc'] = strip_tags($desc);
            }

            //fullDesc
//            foreach($htmlDocDet::str_get_html($data)->find('[itemprop=description]') as $desc){
//                $fixed_str = preg_replace('/[\s]{2,}/', ' ', $desc->plaintext);
//                $updateData['fullDesc'] = $fixed_str;
//            }

            // Photo
            foreach($htmlDocDet::str_get_html($data)->find('.kliniki_doctor_photo img') as $photos){
                $updateData['photo'] =  $photos->src;
            }

            // Price
            foreach($htmlDocDet::str_get_html($data)->find('[itemprop=priceRange]') as $price){

                $price = str_replace('руб.', '', $price->plaintext);
                $price = str_replace(' ', '', $price);
                $price = preg_replace('/\s+/', ' ', $price);
                $price = str_replace("	", " ", $price);
                $price = preg_replace('!\s++!u', ' ', $price);
                while( strpos($price,"  ")!==false){
                    $price = str_replace("  ", " ", $price);
                }
                //$price = number_format($price, 2, '.', '');
                $updateData['price'] = rtrim(rtrim($price, '0'), '.');
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
            foreach($htmlDocDet::str_get_html($data)->find('.js-book-button') as $dataid){
                $idDoc = $dataid->getAttribute('data-id');
            }

            $providerCode = str_replace('/'.$this->city.'/', '', $updateData['link']);
            $url = $domain . "/reviews/". $idDoc ."/page/2?cityCode=". $this->city ."&typeCode=doctor&providerCode=" . $providerCode;
            $data = $this->connect($url);

            foreach($htmlDocDet::str_get_html($data)->find('[itemprop=description]') as $desc){
                 $revs[] = strip_tags($desc);
            }

            foreach($htmlDocDet::str_get_html($data)->find('p.kliniki_review_color') as $createdAt){
                $updateReviews['created'] = strip_tags($createdAt->plaintext);
            }

            foreach($htmlDocDet::str_get_html($data)->find('span.kliniki_review_color') as $user){
                $updateReviews['userId'] = trim(strip_tags($user->plaintext));
            }

            foreach($htmlDocDet::str_get_html($data)->find('.smile itemprop="ratingValue"') as $rating){
                $updateReviews['like'] = $rating->getAttribute('content');
            }

            if(isset($revs)){
                $updateReviews['reviews'] = $revs;
                $updateReviews['reviews'] = $updateData['link'];
                unset($revs);
            }

            //print_r($updateReviews);
            $this->actionUpdateSpec($updateData);
            //$this->actionUpdateReviewsSpec($updateData);
            unset($updateReviews);
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
                echo "Запись добавлена\n";
            }
        } else{
            echo "Запись существует\n";
        }
    }

    public function actionUpdateSpec($data) {

        $specEnt = new Parser();
        $isSpec = $specEnt::find()->where(['link'=>$data['link']])->exists();

        if ( !$isSpec ){    print_r($data);
            $specEnt->name = $data['name'];
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
                echo "Запись добавлена\n";
            }
        }else{
            echo "Запись существует\n";
        }
    }

    public function actionUpdateReviewsOrg($data) {

        $revEnt = new ReviewOrg();
        $user = new User();
        $userID = $user::find()->one();
        $org = new Organization();
        $idOrg = $org::find()->where(['link'=>$data['link']])->one();

        $revEnt->userId = $userID->id;
        $revEnt->organizationId = $idOrg->id;
        $revEnt->text = $data['text'][0];
        $revEnt->rating = $data['rating'];
        $revEnt->createdAt = $data['createdAt'];
        if($revEnt->save()){
            echo "Запись в отзывы добавлена\n";
        }
        die();
    }

    public function actionUpdateReviewsSpec($data) {

        $user = new User();
        $userID = $user::find()->one();
        $specEnt = new ReviewSpec();
        $spec = new Specialist();
        $idSpec = $spec::find()->where(['link'=>$data['link']])->one();

        $specEnt->userId = $userID->id;
        $specEnt->specialistId = $idSpec->id;
        $specEnt->text = $data['text'];
        $specEnt->rating = $data['rating'];
        $specEnt->createdAt = $data['created'];
        if($specEnt->save()){
            echo "Запись в отзывы добавлена\n";
        }
        die();
    }

}