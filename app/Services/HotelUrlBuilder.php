<?php
declare(strict_types=1);
namespace App\Services;
final class HotelUrlBuilder
{
    public function build(string $destination, string $checkIn, string $checkOut, int $adults, int $children, bool $domestic): array
    {
        $common=['destination'=>$destination,'startDate'=>$checkIn,'endDate'=>$checkOut,'adults'=>max(1,$adults),'children'=>max(0,$children)];
        if (!$domestic) return [
            'expedia'=>'https://www.expedia.co.jp/Hotel-Search?'.$this->query($common),
            'hotels'=>'https://jp.hotels.com/Hotel-Search?'.$this->query($common),
            'jtb'=>'https://www.jtb.co.jp/kokunai-hotel/list/?'.$this->query(['q'=>$destination]),
        ];
        return [
            'rakuten'=>'https://travel.rakuten.co.jp/yado/keyword/'.rawurlencode($destination).'.html',
            'jalan'=>'https://www.jalan.net/uw/uwp2011/uww2011init.do?'.$this->query(['keyword'=>$destination]),
            'yahoo'=>'https://travel.yahoo.co.jp/search/?'.$this->query(['keyword'=>$destination]),
            'ikyu'=>'https://www.ikyu.com/search/?'.$this->query(['keyword'=>$destination]),
            'expedia'=>'https://www.expedia.co.jp/Hotel-Search?'.$this->query($common),
        ];
    }
    private function query(array $params): string { return http_build_query($params,'','&',PHP_QUERY_RFC3986); }
}
