<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    public function all()
    {
        $data = Schedule::all();
    
        $data = $data->map(function ($item) {
            $now = now();
            $start_date = \Carbon\Carbon::parse($item->class_start_date);
            $end_date = \Carbon\Carbon::parse($item->class_end_date);
    
            $status = 'Past';
            if ($now->lt($start_date)) {
                $status = 'Future';
            } elseif ($now->between($start_date, $end_date)) {
                $status = 'Present';
            }
    
            return [
                'id' => $item->id,
                'name' => $item->name,
                'class_code' => $item->class_code,
                'trainer' => $item->trainer_id == 0 ? 'No Trainer for now' : optional($item->user)->first_name . ' ' . optional($item->user)->last_name,
                'slots' => $item->slots,
                'link' => '0',
                'class_start_date' => $item->class_start_date,
                'class_end_date' => $item->class_end_date,
                'isenabled' => $item->isenabled ? 'Enabled' : 'Disabled',
                'status' => $status,
                'isadminapproved' => $item->isadminapproved,
                'rejection_reason' => $item->rejection_reason,
                'created_at' => $item->created_at,
            ];
        });
    
        return response()->json(['data' => $data]);
    }
    
    public function index(Request $request)
    {
        $request->validate([
            'search_column' => 'nullable|string',
            'name' => 'nullable|string|max:255',
        ]);
    
        $search = $request->name;
        $search_column = $request->search_column;
    
        $allowed_columns = [
            'id', 'name', 'class_code', 'trainer_id', 'slots',
            'class_start_date', 'class_end_date', 'isadminapproved',
            'rejection_reason', 'created_at'
        ];
    
        if (!in_array($search_column, $allowed_columns)) {
            $search_column = null;
        }
    
        $classescreatedbyadmin = Schedule::where('created_role', 'Admin')->count();
        $classescreatedbystaff = Schedule::where('created_role', 'Staff')->count();
    
        $data = Schedule::query()
            ->when($search && $search_column, function ($query) use ($search, $search_column) {
                return $query->where($search_column, 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->through(function ($schedule) {
                $schedule->user_schedules_count = $schedule->user_schedules->count();
                return $schedule;
            });
    
        return view('admin.gymmanagement.schedules', compact('data', 'classescreatedbyadmin', 'classescreatedbystaff'));
    }

    public function view($id)
    {
        $data = Schedule::findOrFail($id);

        return view('admin.gymmanagement.schedules-view', compact('data'));
    }

    public function create()
    {
        $trainers = User::where('role_id', 5)->get();
        
        return view('admin.gymmanagement.schedules-create', compact('trainers'));
    }

    public function edit($id)
    {
        $data = Schedule::findOrFail($id);
        $trainers = User::where('role_id', 5)->get();
        
        return view('admin.gymmanagement.schedules-edit', compact('data', 'trainers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'isenabled' => 'required',
            'slots' => 'required|integer|min:1',
            'class_start_date' => 'required|date',
            'class_end_date' => 'required|date|after:class_start_date',
            'trainer_id' => 'required',
        ]);
        
        $startRange = Carbon::parse($request->class_start_date)->subHour();
        $endRange = Carbon::parse($request->class_end_date)->addHour();
        
        $existingSchedule = Schedule::where('trainer_id', $request->trainer_id)
            ->where(function ($query) use ($startRange, $endRange) {
                $query->whereBetween('class_start_date', [$startRange, $endRange])
                      ->orWhereBetween('class_end_date', [$startRange, $endRange])
                      ->orWhere(function ($q) use ($startRange, $endRange) {
                          $q->where('class_start_date', '<=', $startRange)
                            ->where('class_end_date', '>=', $endRange);
                      });
            })
            ->first();
        
        if ($existingSchedule) {
            return back()->withErrors(['schedule' => 'The trainer is already booked within this time range.']);
        }
        
        $data = new Schedule;
        $data->name = $request->name;
        $nameParts = explode(' ', $request->name);
        $initials = array_map(fn($word) => strtoupper($word[0]), $nameParts);
        $prefix = implode('', $initials);
        $latestCode = Schedule::where('class_code', 'LIKE', "$prefix-%")
            ->orderBy('class_code', 'desc')
            ->value('class_code');
    
        $number = $latestCode ? intval(substr($latestCode, strlen($prefix) + 1)) + 1 : 1;
        $data->class_code = sprintf('%s-%02d', $prefix, $number);
        $data->slots = $request->slots;
        $data->class_start_date = $request->class_start_date;
        $data->class_end_date = $request->class_end_date;
        $data->isenabled = $request->isenabled;
        $data->trainer_id = $request->trainer_id;
        $data->isadminapproved = $request->trainer_id == 0 ? 0 : 1; 
        $data->created_role = $request->user()->role_id == 1 || $request->user()->role_id == 4 ? 'Admin' : 'Staff';
        
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $destinationPath = public_path('images/schedules');
            
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
    
            $image->move($destinationPath, $imageName);
            $data->image = 'images/schedules/' . $imageName;
        }
        
        $data->save();
    
        $log = new Log;
        $log->message = $request->user()->first_name . " " . $request->user()->last_name . " has created class successfully.";
        $log->role_name = 'Admin';
        $log->save();
        
        return redirect()->route('admin.gym-management.schedules')->with('success', 'Schedule added successfully');
    }    

    public function update(Request $request, $id)
    {
        $data = Schedule::findOrFail($id);
        
        $request->validate([
            'name' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'isenabled' => 'required',
            'slots' => 'required|integer|min:1',
            'class_start_date' => 'required',
            'class_end_date' => 'required',
            'trainer_id' => 'required',
            'class_code' => 'required'
        ]);

        $startRange = Carbon::parse($request->class_start_date)->subHour();
        $endRange = Carbon::parse($request->class_end_date)->addHour();
        
        $existingSchedule = Schedule::where('trainer_id', $request->trainer_id)
            ->where('id', '!=', $data->id)
            ->where(function ($query) use ($startRange, $endRange) {
                $query->whereBetween('class_start_date', [$startRange, $endRange])
                      ->orWhereBetween('class_end_date', [$startRange, $endRange])
                      ->orWhere(function ($q) use ($startRange, $endRange) {
                          $q->where('class_start_date', '<=', $startRange)
                            ->where('class_end_date', '>=', $endRange);
                      });
            })
            ->first();
        
        if ($existingSchedule) {
            return back()->withErrors(['schedule' => 'The trainer is already booked within this time range.']);
        }
        
        $data->name = $request->name;
        $data->slots = $request->slots;
        $data->class_start_date = $request->class_start_date;
        $data->class_end_date = $request->class_end_date;
        $data->isenabled = $request->isenabled;
        $data->trainer_id = $request->trainer_id;
        $data->class_code = $request->class_code;
        $data->isadminapproved = $request->trainer_id == 0 ? 0 : 1;
    
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $destinationPath = public_path('images/schedules');
            
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
    
            $image->move($destinationPath, $imageName);
            $data->image = 'images/schedules/' . $imageName;
        }
        
        $data->save();

        $log = new Log;
        $log->message = $request->user()->first_name . " " . $request->user()->last_name . " has updated class successfully.";
        $log->role_name = 'Admin';
        $log->save();
        
        return redirect()->route('admin.gym-management.schedules')->with('success', 'Schedule updated successfully');
    }

    public function adminacceptance(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:schedules,id',
            'isadminapproved' => 'required'
        ]);

        $data = Schedule::findOrFail($request->id);
        $data->isadminapproved = $request->isadminapproved;
        $data->rejection_reason = null;
        $data->save();

        return redirect()->route('admin.gym-management.schedules')->with('success', 'Schedule changed successfully');
    }
    
    public function delete(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:schedules,id',
                'password' => 'required',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }
        
        $user = $request->user();
    
        if (!\Hash::check($request->password, $user->password)) {
            return redirect()->back()->withErrors(['password' => 'Invalid password.'])->withInput();
        }
        
        $data = Schedule::findOrFail($request->id);
        $data->delete();

        return redirect()->route('admin.gym-management.schedules')->with('success', 'Schedule deleted successfully');
    }
    
    public function rejectmessage(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:schedules,id',
                'rejection_reason' => 'required',
                'password' => 'required',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }
        
        $user = $request->user();
    
        if (!\Hash::check($request->password, $user->password)) {
            return redirect()->back()->withErrors(['password' => 'Invalid password.'])->withInput();
        }
        
        $data = Schedule::findOrFail($request->id);
        $data->rejection_reason = $request->rejection_reason;
        $data->isadminapproved = 2;
        $data->save();

        return redirect()->route('admin.gym-management.schedules')->with('success', 'Schedule changed successfully');
    }
    
    public function print(Request $request)
    {
        $data = Schedule::all();
    
        $fileName = "classes_" . date('Y-m-d') . ".csv";
    
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=\"$fileName\"");
        header("Pragma: no-cache");
        header("Expires: 0");
    
        $output = fopen('php://output', 'w');
    
        fputcsv($output, [
            'ID', 'Class Name', 'Class Code', 'Trainer', 'Slots', 'Total Members Enrolled', 'Class Start Date and Time', 'Class End Date and Time', 'Status', 'Categorization', 'Admin Acceptance', 'Reject Reason', 'Created Date', 'Updated Date',
        ]);
    
        foreach ($data as $item) {
            $now = now();
            $start_date = \Carbon\Carbon::parse($item->class_start_date);
            $end_date = \Carbon\Carbon::parse($item->class_end_date);
        
            if ($now->lt($start_date)) {
                $status = 'Future';
            } elseif ($now->between($start_date, $end_date)) {
                $status = 'Present';
            } else {
                $status = 'Past';
            }
        
            fputcsv($output, [
                $item->id,
                $item->name,
                $item->class_code,
                $item->trainer_id == 0 ? 'No Trainer for now' : optional($item->user)->first_name . ' ' . optional($item->user)->last_name,
                $item->slots,
                0,
                $item->class_start_date,
                $item->class_end_date,
                $item->isenabled ? 'Enabled' : 'Disabled',
                $status,
                $item->isadminapproved == 0 ? 'Pending' : 
                ($item->isadminapproved == 1 ? 'Approve' : 
                ($item->isadminapproved == 2 ? 'Reject' : '')),
                $item->rejection_reason,
                $item->created_at,
                $item->updated_at,
            ]);
        }

    
        fclose($output);
        exit;
    }
    
}
