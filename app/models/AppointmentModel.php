<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Planned market visits.
 */
class AppointmentModel extends Model
{
    protected $table = 'pwesto_appointments';
    protected $primary_key = 'id';
    protected $fillable = ['id', 'user_id', 'vendor_id', 'visit_date', 'visit_time', 'status', 'created_at'];

    public const VISIT_TIMES = ['8:00 AM', '9:00 AM', '10:00 AM', '11:00 AM', '1:00 PM', '2:00 PM', '3:00 PM'];

    public function for_user($userId)
    {
        $rows = $this->db->table('pwesto_appointments a')
            ->select('a.id, a.vendor_id, v.name AS vendor_name, a.visit_date, a.visit_time, a.status, a.created_at')
            ->inner_join('pwesto_vendors v', 'v.id = a.vendor_id')
            ->where('a.user_id', '=', $userId)
            ->get_all();
        usort($rows, function ($a, $b) {
            return [$a['visit_date'], $this->time_rank($a['visit_time']), $b['created_at']]
                <=> [$b['visit_date'], $this->time_rank($b['visit_time']), $a['created_at']];
        });
        return $rows;
    }

    /** @return string|false the appointment ID, or false when the stall is not listed */
    public function plan($userId, $vendorId, $visitDate, $visitTime)
    {
        if ($this->call->model('VendorModel')->find_public($vendorId) === null) {
            return false;
        }
        $id = random_id();
        $this->insert([
            'id' => $id,
            'user_id' => $userId,
            'vendor_id' => $vendorId,
            'visit_date' => $visitDate,
            'visit_time' => $visitTime,
            'status' => 'Planned',
            'created_at' => utc_now(),
        ]);
        return $id;
    }

    public function cancel($userId, $appointmentId)
    {
        return $this->db->table($this->table)
            ->where('id', '=', $appointmentId)
            ->where('user_id', '=', $userId)
            ->where('status', '=', 'Planned')
            ->update(['status' => 'Cancelled']) === 1;
    }

    private function time_rank($time)
    {
        $rank = array_search($time, self::VISIT_TIMES, true);
        return $rank === false ? PHP_INT_MAX : $rank;
    }
}
