<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class StaffAccountManagementController extends Controller
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
            'id', 'name', 'email', 'role_id', 'phone_number',
            'created_at'
        ];
        
        if (!in_array($search_column, $allowed_columns)) {
            $search_column = null;
        }
        
        $data = User::where('role_id', 2)
            ->when($search && $search_column, function ($query) use ($search, $search_column) {
                if ($search_column === 'name') {
                    return $query->where(function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%");
                    });
                } else {
                    return $query->where($search_column, 'like', "%{$search}%");
                }
            })
            ->paginate(10);
    
        return view('admin.staffaccountmanagement.index', compact('data'));
    }

    
    public function add()
    {
        return view('admin.staffaccountmanagement.add');
    }

    public function view($id)
    {
        $data = User::where('role_id', 2)->find($id);

        return view('admin.staffaccountmanagement.view', compact('data'));
    }

    public function edit($id)
    {
        $data = User::where('role_id', 2)->find($id);

        return view('admin.staffaccountmanagement.edit', compact('data'));
    }
    
    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'address' => 'required',
            'phone_number' => 'required',
            'email' => 'required|email|unique:users',
            'password' => ['required', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.staff-account-management.add')
                ->withErrors($validator)
                ->withInput();
        }

        $users = new User;
        $users->role_id = 2;
        $users->status_id = 2;
        $users->first_name = $request->first_name;
        $users->last_name = $request->last_name;
        $users->address = $request->address;
        $users->phone_number = $request->phone_number;
        $users->email = $request->email;
        $users->password = $request->password;
        $users->save();

        return redirect()->route('admin.staff-account-management.index')->with('success', 'Staff created successfully');
    }
    
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'address' => 'required',
            'phone_number' => 'required',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|confirmed',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.staff-account-management.index')
                ->withErrors($validator)
                ->withInput();
        }
        
        $data = User::findOrFail($id);
        $data->role_id = 2;
        $data->status_id = 2;
        $data->first_name = $request->first_name;
        $data->last_name = $request->last_name;
        $data->address = $request->address;
        $data->phone_number = $request->phone_number;
        $data->email = $request->email;
        
        if ($request->filled('password')) {
            $data->password = bcrypt($request['password']);
        }
        
        $data->save();

        return redirect()->route('admin.staff-account-management.index')->with('success', 'Staff updated successfully');
    }
    
    /*public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id',
        ]);

        $data = User::where('role_id', 2)->findOrFail($request->id);
        $data->delete();

        return redirect()->route('admin.staff-account-management.index')->with('success', 'Staff deleted successfully');
    }*/
    
    public function delete(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:users,id',
                'password' => 'required',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }
        
        $user = $request->user();
    
        if (!\Hash::check($request->password, $user->password)) {
            return redirect()->back()->withErrors(['password' => 'Invalid password.'])->withInput();
        }
        
        $data = User::where('role_id', 2)->findOrFail($request->id);
        $data->delete();

        return redirect()->route('admin.staff-account-management.index')->with('success', 'Staff deleted successfully');
    }
    
    public function print(Request $request)
    {
        $data = User::where('role_id', 2)->get();
        
        $fileName = "staffs_data_" . date('Y-m-d') . ".csv";
    
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=\"$fileName\"");
        header("Pragma: no-cache");
        header("Expires: 0");
    
        $output = fopen('php://output', 'w');
    
        fputcsv($output, [
            'ID', 'Name', 'Email', 'Type', 'Contact Number', 'Created At', 'Updated At'
        ]);
    
        foreach ($data as $item) {
            fputcsv($output, [
                $item->id,
                $item->first_name .' '. $item->last_name,
                $item->email,
                $item->role->name,
                $item->phone_number,
                $item->created_at,
                $item->updated_at
            ]);
        }
    
        fclose($output);
        exit;
        
    }
}
