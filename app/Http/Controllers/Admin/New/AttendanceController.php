<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Attendance2;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search_column' => 'nullable|string',
            'name' => 'nullable|string|max:255',
        ]);
        
        $search = $request->name;
        $search_column = $request->search_column;
    
        $allowed_columns = [
            'id', 'role', 'name', 'clockin_at', 'clockout_at'
        ];
    
        if (!in_array($search_column, $allowed_columns)) {
            $search_column = null;
        }
    
        $data = Attendance2::query()
            ->with('user.role') // Ensure role relationship is loaded
            ->when($search && $search_column, function ($query) use ($search, $search_column) {
                if ($search_column === 'role') {
                    return $query->whereHas('user.role', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
                }
    
                if ($search_column === 'name') {
                    return $query->whereHas('user', function ($q) use ($search) {
                        $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                    });
                }
    
                return $query->where($search_column, 'like', "%{$search}%");
            })
            ->paginate(10);
    
        return view('admin.attendances.index', compact('data'));
    }


    public function scanner()
    {
        return view('admin.attendances.scanner');
    }

    public function fetchScanner(Request $request)
    {
        $result = $request->result;
    
        if (!preg_match('/^[\w\.-]+@[\w\.-]+\.[a-zA-Z]{2,}_clock(in|out)$/', $result)) {
            return response()->json(['data' => 'Invalid format.']);
        }
    
        [$email, $type] = explode('_', $result);
        $user = User::where('email', $email)->first();
    
        if ($user) {
            if ($user->role_id == 3) {
                $membership = $user->usermemberships()
                    ->where('isapproved', 1)
                    ->where('expiration_at', '>', now())
                    ->latest('expiration_at')
                    ->first();
    
                if (!$membership) {
                    return response()->json(['data' => 'No valid membership found']);
                }
            }
    
            $existingAttendance = Attendance::where('user_id', $user->id)
                ->whereDate('created_at', now()->toDateString())
                ->pluck('type')
                ->toArray();
    
            if ($type === 'clockout' && !in_array('clockin', $existingAttendance)) {
                return response()->json(['data' => "Clockout cannot be used without clocking in first."]);
            }
    
            if (($type === 'clockin' && in_array('clockin', $existingAttendance)) ||
                ($type === 'clockout' && in_array('clockout', $existingAttendance))) {
                return response()->json(['data' => "User has already clocked $type today."]);
            }
    
            $data = new Attendance;
            $data->user_id = $user->id;
            $data->type = $type;
            $data->save();
    
            return response()->json([
                'data' => $user->email . ' has ' . ($type == 'clockin' ? 'clocked in' : 'clocked out') . ' successfully'
            ]);
        } else {
            return response()->json(['data' => 'No data found']);
        }
    }
    
    public function fetchScanner2(Request $request)
    {
        $email = $request->result;
    
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['data' => 'Invalid email format.']);
        }
    
        $user = User::where('email', $email)->first();
    
        if (!$user) {
            return response()->json(['data' => 'No data found']);
        }
    
        if ($user->role_id == 3) {
            $membership = $user->usermemberships()
                ->where('isapproved', 1)
                ->where('expiration_at', '>', now())
                ->latest('expiration_at')
                ->first();
    
            if (!$membership) {
                return response()->json(['data' => 'No valid membership found']);
            }
        }
    
        // Check if the user has already clocked in or out for today
        $attendance = Attendance2::where('user_id', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->orderBy('created_at', 'desc') // Get the latest record
            ->first();
    
        if (!$attendance || $attendance->clockout_at) {
            // If no attendance record exists, or if the user has clocked out, clock in
            $attendance = new Attendance2();
            $attendance->user_id = $user->id;
            $attendance->clockin_at = now();
            $attendance->save();
    
            return response()->json(['data' => $user->email . ' has clocked in successfully.']);
        }
    
        if ($attendance && !$attendance->clockout_at) {
            // If the user has clocked in, clock out
            $attendance->clockout_at = now();
            $attendance->save();
    
            return response()->json(['data' => $user->email . ' has clocked out successfully.']);
        }
    
        return response()->json(['data' => 'An unexpected error occurred.']);
    }
    
    public function print(Request $request)
    {
        $data = Attendance2::all();
    
        $fileName = "attendances_data_" . date('Y-m-d') . ".csv";
    
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=\"$fileName\"");
        header("Pragma: no-cache");
        header("Expires: 0");
    
        $output = fopen('php://output', 'w');
    
        fputcsv($output, [
            'ID', 'Role', 'Member Name', 'Clock-in Time', 'Clock-out Time', 
            'Created At', 'Updated At'
        ]);
    
        foreach ($data as $item) {
            fputcsv($output, [
                $item->id,
                $item->user->role->name,
                $item->user->first_name .' '. $item->user->last_name,
                $item->clockin_at,
                $item->clockout_at,
                $item->created_at,
                $item->updated_at
            ]);
        }
    
        fclose($output);
        exit;
    }
}
