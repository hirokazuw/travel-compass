<?php

declare(strict_types=1);

namespace App\Services\FlightUrls;

use App\Models\FlightCity;

final class JtbUrlBuilder implements FlightProviderUrlBuilder
{
    public function __construct(private FlightCity $cities) {}

    public function build(string $origin, string $destination, string $departure, string $return, int $travelers): string
    {
        return FlightUrl::validate($this->url($origin, $destination, $departure, $return, $travelers));
    }
    private function url(string $origin, string $destination, string $departure, string $return, int $travelers): string
    {
        $from = $this->cities->code($origin);
        $to = $this->cities->code($destination);
        if ($from === null || $to === null) return 'https://www.jtb.co.jp/ovs_air/';
        $params = [
            'trvlType' => $return !== '' ? 1 : 0,
            'deptDt1' => str_replace('-', '', $departure),
            'deptCd1' => strtoupper($from),
            'deptCtyCd1' => '',
            'arvlCd1' => strtoupper($to),
            'arvlCtyCd1' => '',
            'totalNumAdlt' => max(1, $travelers),
            'totalNumChld' => 0,
            'totalNumIns' => 0,
            'totalNumInf' => 0,
            'nonstopFltSpecifiedFlg' => 0,
            'cabinCls' => 0,
            'alnc' => 0,
        ];
        if ($return !== '') {
            $params += [
                'deptDt2' => str_replace('-', '', $return),
                'deptCd2' => strtoupper($to),
                'deptCtyCd2' => '',
                'arvlCd2' => strtoupper($from),
                'arvlCtyCd2' => '',
            ];
        }
        for ($leg = $return !== '' ? 3 : 2; $leg <= 6; $leg++) {
            $params += [
                'deptDt' . $leg => '',
                'deptCd' . $leg => '',
                'deptCtyCd' . $leg => '',
                'arvlCd' . $leg => '',
                'arvlCtyCd' . $leg => '',
            ];
        }
        $params['caCd'] = '';
        return 'https://www.jtb.co.jp/ovs_air/search/search_result/?'
            . FlightUrl::query($params);
    }

}
