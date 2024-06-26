<?php

namespace App\Livewire\Leaverequest;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\Employee;
use App\Models\Leaverequest;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LeaveRequestForm extends Component
{
    use WithFileUploads;
    
    public $employeeRecord;
    public $date;
    public $first_name;
    public $middle_name;
    public $last_name;
    public $department_name;
    public $available_credits;
    public $current_position;
    public $salary;

    public $employee_id;
    public $date_of_filling;
    public $type_of_leave;
    public $type_of_leave_others;
    public $type_of_leave_sub_category;
    public $type_of_leave_description;
    public $num_of_days_work_days_applied;
    public $inclusive_start_date;
    public $inclusive_end_date;
    public $commutation;
    public $commutation_signature_of_appli;
    public $total_earned_vaca;
    public $less_this_appli_vaca;
    public $balance_vaca;
    public $total_earned_sick;
    public $less_this_appli_sick;
    public $balance_sick;
    public $as_of_filling;
    public $auth_off_sig_a;
    public $status_description;
    public $auth_off_sig_b;
    public $days_with_pay;
    public $days_without_pay;
    public $others;
    public $disapprove_reason;
    public $auth_off_sig_c_and_d;


    public function mount(){
        $loggedInUser = auth()->user();
        $employeeRecord = Employee::select('first_name', 'middle_name', 'last_name', 'department_id', 'employee_id', 'current_position', 'salary', 'vacation_credits', 'sick_credits')
                                    ->where('employee_id', $loggedInUser->employee_id)
                                    ->first(); 
                          

        $departmentName = DB::table('departments')->where('department_id', $employeeRecord->department_id[0])->value('department_name');
        $this->available_credits = $employeeRecord->vacation_credits + $employeeRecord->sick_credits;
        $this->employee_id = $employeeRecord->employee_id;
        $this->first_name = $employeeRecord->first_name;
        $this->middle_name = $employeeRecord->middle_name;
        $this->last_name = $employeeRecord->last_name;
        $this->department_name = $departmentName;
        $this->current_position = $employeeRecord->current_position;
        $this->salary = $employeeRecord->salary;
        $this->available_credits = $employeeRecord->sick_credits + $employeeRecord->vacation_credits;
        $dateToday = Carbon::now()->toDateString();
        $this->date = $dateToday;
        $this->date_of_filling = $dateToday;
    }

    public function updated($keys){
        if(in_array($keys, ['inclusive_start_date', 'inclusive_end_date'])){
            $startDate = Carbon::parse($this->inclusive_start_date);
            $endDate = Carbon::parse($this->inclusive_end_date);
            $num_of_days_work_days_applied = $startDate->diffInDays($endDate);
            // $num_of_hours_work_days_applied = $startDate->diffInHours($endDate);
            $num_of_seconds_work_days_applied = $startDate->diffInMinutes($endDate);
            // dd($num_of_seconds_work_days_applied);
            if ($startDate->startOfDay() == $endDate->startOfDay()){
                // $conversionValues = [
                //     0.002, 0.004, 0.006, 0.008, 0.010, 0.012, 0.015, 0.017, 0.019, 0.021,
                //     0.023, 0.025, 0.027, 0.029, 0.031, 0.033, 0.035, 0.037, 0.040, 0.042,
                //     0.044, 0.046, 0.048, 0.050, 0.052, 0.054, 0.056, 0.058, 0.060, 0.062,
                //     0.065, 0.067, 0.069, 0.071, 0.073, 0.075, 0.077, 0.079, 0.081, 0.083,
                //     0.085, 0.087, 0.090, 0.092, 0.094, 0.096, 0.098, 0.100, 0.102, 0.104,
                //     0.106, 0.108, 0.110, 0.112, 0.115, 0.117, 0.119, 0.121, 0.123, 0.125,
                // ];
                $days = $num_of_seconds_work_days_applied / 60;
                if($days > 8){
                    $days = 8;
                }
                // dd($seconds, $num_of_seconds_work_days_applied);
                // $decimalPart = ($num_of_seconds_work_days_applied - floor($num_of_seconds_work_days_applied)) * 60;
                $hoursLeave = $days * 0.125;
                
                // $this->$num_of_days_work_days_applied = number_format($hoursLeave , 3);
                $this->num_of_days_work_days_applied = number_format($hoursLeave, 3);
            }
            else{
                $dividedValue = $num_of_seconds_work_days_applied / 1440;
                $this->num_of_days_work_days_applied = number_format($dividedValue, 3);
            }
        }

    }

    
    public function removeImage($item){
        $this->$item = null;
    }

    private function generateRefNumber(){
        $today = date('Ymd');

        $randomDigits = '';
        for ($i = 0; $i < 5; $i++) {
            $randomDigits .= random_int(0, 9); // More secure random number generation
        }
        // Combine the date and random digits
        $referenceNumber = $today . $randomDigits;
        return $referenceNumber;
     }


    protected $rules = [
        'type_of_leave' => 'required|in:Others,Vacation Leave,Mandatory/Forced Leave,Sick Leave,Maternity Leave,Paternity Leave,Special Privilege Leave,Solo Parent Leave,Study Leave,10-Day VAWC Leave,Rehabilitation Privilege,Special Leave Benefits for Women,Special Emergency Leave,Adoption Leave',
        'type_of_leave_others' => 'required_if:type_of_leave,Others|max:100',
        'type_of_leave_sub_category' => 'nullable|in:Others,Within the Philippines,Abroad,Out Patient,Special Leave Benefits for Women,Completion of Master\'s degree,BAR/Board Examination Review,Monetization of leave credits,Terminal Leave,In Hospital, Others',
        'type_of_leave_description' => 'required_if:type_of_leave_sub_category,Others|min:10|max:500',
        'inclusive_start_date' => 'required|after_or_equal:date_of_filling|before_or_equal:inclusive_end_date',
        'inclusive_end_date' => 'required|after_or_equal:inclusive_start_date',
        // 'num_of_days_work_days_applied' => 'required|lte:available_credits',
        'commutation' => 'required|in:not requested,requested',
        'commutation_signature_of_appli' => 'required|mimes:jpg,png,pdf|extensions:jpg,png,pdf|max:5120'
    ];

    protected $validationAttributes = [
        'type_of_leave' => 'Type of Leave',
        'type_of_leave_others' => 'Others',
        'type_of_leave_sub_category' => 'Sub Category',
        'type_of_leave_description' => 'Leave Description',
    ];


    public function submit(){
        foreach($this->rules as $rule => $validationRule){
            $this->validate([$rule => $validationRule]);
            $this->resetValidation();
        }   

        if (in_array($this->type_of_leave, ['Vacation Leave', 'Mandatory/Forced Leave', 'Sick Leave'])) {
            $this->validate(['num_of_days_work_days_applied' => 'required|lte:available_credits',]);
        }
        
        $loggedInUser = auth()->user();

        $leaverequestdata = new Leaverequest();

        $randomNumber = 0;
        while(True) {
            $randomNumber = $this->generateRefNumber();
            $existingRecord = Leaverequest::where('reference_num', $randomNumber)->first();
            if(!$existingRecord){
                break;
            }
         
        }

        $leaverequestdata->reference_num = $randomNumber;
        $leaverequestdata->employee_id = $loggedInUser->employee_id;
        $leaverequestdata->status = "Pending";
        $leaverequestdata->date_of_filling = $this->date_of_filling;
        $leaverequestdata->type_of_leave = $this->type_of_leave;
        $leaverequestdata->type_of_leave_others = $this->type_of_leave_others;
        $leaverequestdata->type_of_leave_sub_category = $this->type_of_leave_sub_category;
        $leaverequestdata->type_of_leave_description = $this->type_of_leave_description;
        $leaverequestdata->num_of_days_work_days_applied = $this->num_of_days_work_days_applied;
        $leaverequestdata->inclusive_start_date = $this->inclusive_start_date;
        $leaverequestdata->inclusive_end_date = $this->inclusive_end_date;
        $leaverequestdata->commutation = $this->commutation;
        $department_id = Employee::where('employee_id', $loggedInUser->employee_id)->value('department_id');
        $departmentName = DB::table('departments')->where('department_id', $department_id)->value('department_name');

        $leaverequestdata->office_department = $departmentName;

        //  In case something goes wrong, replace the storing of image with this.
        // $imageData = $this->commutation_signature_of_appli->store('photos\leaverequest\\', 'local');
        // $imageData = Storage::disk('local')->path($imageData);
        // $imageData = file_get_contents($imageData);
        // $imageData = base64_encode($imageData);
        // $leaverequestdata->commutation_signature_of_appli = $imageData;


        $imageData = file_get_contents($this->commutation_signature_of_appli->getRealPath());
        $leaverequestdata->commutation_signature_of_appli  = base64_encode($imageData);

        $this->js("alert('Leave Request submitted!')"); 
       
        $leaverequestdata->save();

        return redirect()->to(route('LeaveRequestTable'));

    }

    // public function updateNumOfDays($start_date, $end_date){
    //     dd($start_date);
     
    // }
    public function render()
    {
        return view('livewire.leaverequest.leave-request-form')->extends('components.layouts.app');
    }
}
