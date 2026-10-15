<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Batch;
use App\Models\Department;
use App\Models\Notice;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Database\Seeder;

/**
 * Sample batches and students taken from the design, for trying the system out.
 * Remove everything with: php artisan migrate:fresh --seed
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $gd = Department::where('code', 'GD')->firstOrFail();

        $batches = [
            ['GD-2607', 'সকালের ব্যাচ', 'শনি · সোম · বুধ', 'সকাল ১০:০০ – দুপুর ১২:০০', '2026-07-04', '2027-01-03', 'মো. রফিক হোসেন', 'ল্যাব ২', 25, 'running'],
            ['GD-2610', 'সান্ধ্য ব্যাচ', 'রবি · মঙ্গল · বৃহস্পতি', 'সন্ধ্যা ৬:০০ – রাত ৮:০০', '2026-10-11', '2027-04-10', 'শিরিন আক্তার', 'ল্যাব ১', 25, 'running'],
            ['GD-2701', 'সকালের ব্যাচ', 'শনি · সোম · বুধ', 'সকাল ১০:০০ – দুপুর ১২:০০', '2027-01-09', '2027-07-08', 'মো. রফিক হোসেন', 'ল্যাব ২', 25, 'enrolling'],
        ];
        foreach ($batches as [$code, $name, $days, $time, $start, $end, $teacher, $room, $cap, $status]) {
            Batch::updateOrCreate(['code' => $code], compact('name', 'days', 'teacher', 'room', 'status') + [
                'department_id' => $gd->id, 'class_time' => $time, 'start_date' => $start, 'end_date' => $end, 'capacity' => $cap,
            ]);
        }

        foreach (['মো. রফিক হোসেন' => 'ফটোশপ ও ইলাস্ট্রেটর', 'শিরিন আক্তার' => 'ব্র্যান্ডিং ও লেআউট'] as $name => $subject) {
            $teacher = Teacher::firstOrCreate(['name' => $name], ['department_id' => $gd->id, 'subject' => $subject, 'bio' => '[অভিজ্ঞতা ও যোগ্যতা]']);
            Batch::where('teacher', $name)->update(['teacher_id' => $teacher->id]);
        }

        $batch = Batch::where('code', 'GD-2607')->first();
        $people = [
            ['আব্দুল্লাহ আল মামুন', 'Abdullah Al Mamun', 'মো. মিজানুর রহমান', 12000],
            ['নুসরাত জাহান', 'Nusrat Jahan', 'আবুল কাসেম', 12000],
            ['রাকিব হাসান', 'Rakib Hasan', 'মো. শহিদুল ইসলাম', 6000],
            ['তানভীর আহমেদ', 'Tanvir Ahmed', 'মো. জাহাঙ্গীর আলম', 8000],
            ['মো. ফাহিম উদ্দিন', 'Md. Fahim Uddin', 'নুরুল আলম', 0],
        ];

        foreach ($people as $i => [$bn, $en, $father, $paid]) {
            if (Student::where('name_en', $en)->exists()) {
                continue;
            }
            [$code, $roll] = Student::nextCodeFor($batch);
            $student = Student::create([
                'batch_id' => $batch->id, 'student_code' => $code, 'roll' => $roll,
                'name_bn' => $bn, 'name_en' => $en, 'father_name' => $father, 'mother_name' => 'রোকেয়া বেগম',
                'dob' => '2005-03-14', 'gender' => str_contains($bn, 'নুসরাত') ? 'মহিলা' : 'পুরুষ', 'nationality' => 'বাংলাদেশি',
                'guardian_phone' => '01800-000'.(101 + $i), 'phone' => '01700-000'.(101 + $i),
                'present_village' => 'স্টেশন রোড, বারইয়ারহাট', 'present_upazila' => 'মীরসরাই', 'present_district' => 'চট্টগ্রাম',
                'admission_date' => '2026-06-2'.(6 + min($i, 3)), 'course_fee' => 12000, 'admission_fee' => 500, 'discount' => $i === 3 ? 500 : 0,
                'installments' => 5, 'next_installment_date' => $paid >= 12000 ? null : now()->addDays(20 - 10 * $i)->toDateString(),
                'next_installment_amount' => $paid >= 12000 ? null : 2000, 'status' => 'active', 'password' => '123456',
                'education' => [['exam' => 'এসএসসি / দাখিল', 'inst' => 'মীরসরাই সরকারি উচ্চ বিদ্যালয়', 'group' => 'বিজ্ঞান', 'board' => 'চট্টগ্রাম বোর্ড', 'year' => '2021', 'result' => 'জিপিএ ৪.৮৯']],
            ]);

            $left = $paid >= 12000 ? $student->totalPayable() : $paid;
            $n = 1;
            while ($left > 0) {
                $amount = min($left, $n === 1 ? 3000 : 2500);
                Payment::create(['student_id' => $student->id, 'receipt_no' => Payment::nextReceiptNo(), 'paid_on' => now()->subMonths(4 - min($n, 4))->toDateString(),
                    'item' => $n === 1 ? 'ভর্তি ফি + ১ম কিস্তি' : bn_ordinal($n).' কিস্তি', 'amount' => $amount, 'method' => $n % 2 ? 'নগদ (ক্যাশ)' : 'বিকাশ']);
                $left -= $amount;
                $n++;
            }

            Assessment::create(['student_id' => $student->id, 'title' => 'মডিউল টেস্ট ১ — ফটোশপ বেসিক', 'held_on' => '2026-07-20', 'score' => 78 + $i * 3, 'status' => 'passed']);
            Assessment::create(['student_id' => $student->id, 'title' => 'ইলাস্ট্রেটর টেস্ট', 'status' => 'pending']);
        }

        // Ten class days of attendance for the morning batch.
        $day = now()->subDays(30);
        $students = $batch->students()->pluck('id');
        for ($k = 0; $k < 10; $k++) {
            $day = $day->addDays(3);
            foreach ($students as $j => $id) {
                Attendance::updateOrCreate(['student_id' => $id, 'date' => $day->toDateString()], ['batch_id' => $batch->id, 'present' => ($j + $k) % 7 !== 0, 'status' => ($j + $k) % 7 !== 0 ? 'present' : 'absent']);
            }
        }

        Notice::firstOrCreate(['title' => 'ভর্তি চলছে — সকল বিভাগে নতুন ব্যাচ'], ['tag' => 'admission', 'published_on' => now()->toDateString(), 'is_public' => true]);
        Notice::firstOrCreate(['title' => 'ইলাস্ট্রেটর মডিউল টেস্টের তারিখ ঘোষণা'], ['tag' => 'exam', 'published_on' => now()->toDateString(), 'is_public' => false, 'batch_id' => $batch->id]);
    }
}
