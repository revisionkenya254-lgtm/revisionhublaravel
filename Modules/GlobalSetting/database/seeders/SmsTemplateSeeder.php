<?php

namespace Modules\GlobalSetting\database\seeders;

use Illuminate\Database\Seeder;
use Modules\GlobalSetting\app\Models\SmsTemplate;

class SmsTemplateSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        $templates = self::smsTemplates();

        SmsTemplate::truncate();
        foreach ($templates as $template) {
            $new_template = new SmsTemplate();
            $new_template->name = $template['name'];
            $new_template->message = $template['message'];
            $new_template->save();
        }
    }

    public static function smsTemplates() {
        return [
            [
                'name'    => 'approved_refund',
                'message' => '"{{refund_amount}}" USD refunded to your account. - "{{user_name}}"',
            ],
            [
                'name'    => 'approved_withdraw',
                'message' => 'Your withdrawal has been approved. - "{{user_name}}"',
            ],
            [
                'name'    => 'rejected_withdraw',
                'message' => 'Withdrawal request rejected. - "{{user_name}}"',
            ],
            [
                'name'    => 'instructor_request_approved',
                'message' => 'You are now approved as an instructor.',
            ],
            [
                'name'    => 'instructor_request_rejected',
                'message' => 'Instructor request rejected. Please resubmit.',
            ],
            [
                'name'    => 'order_completed',
                'message' => 'Order "#{{order_id}}" placed. Paid "{{paid_amount}}" via "{{payment_method}}".',
            ],
            [
                'name'    => 'payment_status',
                'message' => 'Order "#{{order_id}}" payment status is "{{payment_status}}".',
            ],
            [
                'name'    => 'qna_reply_mail',
                'message' => 'Instructor replied to your question on "{{lesson}}".',
            ],
            [
                'name'    => 'live_class_mail',
                'message' => 'Live class on "{{lesson}}" starts "{{start_time}}". Link: "{{join_url}}"',
            ],
            [
                'name'    => 'gift_course',
                'message' => '"{{sender_name}}" gifted you a course! Claim: "{{link}}"',
            ],
        ];
    }
}