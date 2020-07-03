<?php
class AdminController extends Controller {

    public function index() {
        $thisMonth = strtotime(date("Y-m-01 00:00:00"));
        
        $data = [
            'users' => [
                'total' => Users::count(),
                'month' => Users::where("join_date", ">=", $thisMonth)->count()
            ],
            'payments' => [
                'total' => Payments::sum('paid'),
                'month' => Payments::where("date_paid", ">=", $thisMonth)->sum('paid'),
            ],
            'servers' => [
                'total' => Servers::count(),
                'month' => Servers::where("date_created", ">=", $thisMonth)->count(),
            ]
        ];

        $dates = $this->getChartDates(14);
        $votes = Votes::getChartData($dates);

        $this->set("chart_keys", array_keys($dates['chart']));
        $this->set("votes_data", $votes);
        $this->set("data", $data);
        return true;
    }

    public function getChartDates($dayLimit = 14, $format = "m.d") {
        $start = time() - (86400 * $dayLimit);
        $end   = time();

        $data = [
            'start'  => $start,
            'format' => $format,
            'chart'  => [],
        ];

        while($start <= $end) {
            $date = date($format, $start);
            $data['chart'][$date] = 0;

            $start += 86400; // increment by 1 day until we reach today
        }

        return $data;
    }

    public function beforeExecute() {
        return parent::beforeExecute();
    }

}