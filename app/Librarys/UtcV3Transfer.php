<?php

namespace App\Librarys;

use App\Models\Tenant\ActAgainRecord;
use App\Models\Tenant\ActAgainShareRecord;
use App\Models\Tenant\ActRedPacketRecord;
use App\Models\Tenant\Turntable\TurntableRecord;

class UtcV3Transfer
{

    public static function  updateStatus(array $result, array $data)
    {
        $state_field = [
            'again' => 'status',
            'again_share' => 'status',
            'red_packet' => 'state',
            'turnable' => 'state'
        ];
        $tenant = $data['tenant_id'];
        $id = $data['record_id'];
        $type = $data['act_type'];

        if ($tenant) {
            tenancy()->initialize($tenant);
        } else {
            tenancy()->end();
        }

        switch ($type) {
            case "again":
                $record = ActAgainRecord::find($id);
                break;
            case "again_share":
                $record = ActAgainShareRecord::find($id);
                break;
            case "red_packet":
                $record = ActRedPacketRecord::find($id);
                break;
            case "turnable":
                $record = TurntableRecord::find($id);
                break;
            default:
                return;
        }

        if (empty($record)) {
            return;
        }

        if ($result['status_code'] == 200) {
            if ($result['data']['detail_status'] === 'SUCCESS') {
                $record->{$state_field[$type]} = 1;
                $record->err_code = '';
                $record->err_msg = '';
                $record->received_at =  date('Y-m-d H:i:s', strtotime($result['data']['update_time']));
            } else if ($result['data']['detail_status'] === 'FAIL') {
                $errMsg = config('wechat.v3_transfer_error_msg');
                $record->{$state_field[$type]} = -1;
                $record->err_code = $result['data']['fail_reason'];
                $record->err_msg =  $errMsg[$result['data']['fail_reason']] ?? '';
            }
        } else {
            $record->state = -1;
            $record->err_code = $res['data']['code'] ?? '';
            $record->err_msg = $res['data']['message'] ?? '';
        }
        $record->save();
    }
}
