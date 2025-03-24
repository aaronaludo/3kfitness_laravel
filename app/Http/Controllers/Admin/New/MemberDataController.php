<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Membership;
use App\Models\UserMembership;
use App\Models\Schedule;
use Carbon\Carbon;

class MemberDataController extends Controller
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
            'id', 'name', 'phone_number', 'email', 'created_at',
            'updated_at'
        ];
        
        // name is combine first_name and last_name, i want to combine it when search_column
        
        if (!in_array($search_column, $allowed_columns)) {
            $search_column = null;
        }
        
        $current_time = Carbon::now();
     
        $gym_members = User::where('role_id', 3)
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

        return view('admin.gymmanagement.memberdata', compact('gym_members', 'current_time'));
    }  
    
    public function view($id)
    {
        $gym_member = User::where('role_id', 3)->findOrFail($id);

        return view('admin.gymmanagement.memberdata-view', compact('gym_member'));
    }

    public function create()
    {
        $memberships = Membership::all();
        $classes = Schedule::all();
        
        return view('admin.gymmanagement.memberdata-create', compact('memberships', 'classes'));
    }

    public function edit($id)
    {
        $gym_member = User::where('role_id', 3)->findOrFail($id);
        $memberships = Membership::all();
        $current_time = Carbon::now();
        
        $gym_member_membership = optional($gym_member->usermemberships()
            ->where('isapproved', 1)
            ->where('expiration_at', '>=', $current_time)
            ->orderBy('created_at', 'desc')
            ->first()
        )->membership;
        
        return view('admin.gymmanagement.memberdata-edit', compact('gym_member', 'memberships', 'gym_member_membership'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'profile_picture' => 'nullable|mimes:jpeg,png,jpg,gif|max:2048',
            'first_name' => 'required',
            'last_name' => 'required',
            'address' => 'required',
            'phone_number' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed',
            'membership_id' => 'required'
        ]);

        $users = new User;
        $users->role_id = 3;
        $users->status_id = 2;
        $users->first_name = $validatedData['first_name'];
        $users->last_name = $validatedData['last_name'];
        $users->address = $validatedData['address'];
        $users->phone_number = $validatedData['phone_number'];
        $users->email = $validatedData['email'];
        $users->password = bcrypt($validatedData['password']);
        
        $destinationPath = public_path('uploads');
        
        if ($request->hasFile('profile_picture')) {
            $profilePicture = $request->file('profile_picture');
            $profilePictureUrlName = time() . '_image.' . $profilePicture->getClientOriginalExtension();
            $profilePicture->move($destinationPath, $profilePictureUrlName);
            $users->profile_picture = 'uploads/' . $profilePictureUrlName;
        }
        
        $users->save();

        $membership = Membership::find($validatedData['membership_id']);
        $data = new UserMembership;
        $data->user_id = $users->id;
        $data->membership_id = $validatedData['membership_id'];
        $data->isapproved = 1;
        $data->proof_of_payment = 'blank_for_now';
    
        $currentDate = new \DateTime();
        if ($membership->year) {
            $currentDate->modify("+{$membership->year} years");
        }
        if ($membership->month) {
            $currentDate->modify("+{$membership->month} months");
        }
        if ($membership->week) {
            $currentDate->modify("+{$membership->week} weeks");
        }
        $data->expiration_at = $currentDate;

        $data->save();
        
        return redirect()->route('admin.gym-management.members')->with('success', 'Gym member added successfully');
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'membership_id' => 'required'
        ]);
        
        if ($validatedData['membership_id'] == 0) {
            $membership = (object) ['year' => 0, 'month' => 0, 'week' => 0]; 
            $validatedData['membership_id'] = null;
        } else {
            $membership = Membership::find($validatedData['membership_id']);
        }
    
        $gym_member = User::where('role_id', 3)->findOrFail($id);
        
         $existingMemberships = UserMembership::where('user_id', $gym_member->id)->get();
        foreach ($existingMemberships as $existingMembership) {
            $existingMembership->isapproved = 0;
            $existingMembership->save();
        }
        
        $data = new UserMembership;
        $data->user_id = $gym_member->id;
        $data->membership_id = $validatedData['membership_id'];
        $data->isapproved = 1;
        $data->proof_of_payment = 'blank_for_now';
        
        $currentDate = new \DateTime();
        if ($membership->year) {
            $currentDate->modify("+{$membership->year} years");
        }
        if ($membership->month) {
            $currentDate->modify("+{$membership->month} months");
        }
        if ($membership->week) {
            $currentDate->modify("+{$membership->week} weeks");
        }
        $data->expiration_at = $currentDate;
        
        $data->save();
        

        return redirect()->route('admin.gym-management.members')->with('success', 'Gym member updated successfully');
    }
    
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
        
        $data = User::where('role_id', 3)->findOrFail($request->id);
        $data->delete();

        return redirect()->route('admin.gym-management.members')->with('success', 'Gym member deleted successfully');
    }
    
    public function print(Request $request)
    {
        $data = User::where('role_id', 3)->get();
    
        $fileName = "members_data_" . date('Y-m-d') . ".csv";
    
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=\"$fileName\"");
        header("Pragma: no-cache");
        header("Expires: 0");
    
        $output = fopen('php://output', 'w');
    
        fputcsv($output, [
            'ID', 'Membership Name', 'Membership Expiration Date', 'Name', 'Phone Number', 'Email', 'Created At', 'Updated At',
        ]);
        
        $current_time = Carbon::now();
                    
        foreach ($data as $item) {
            $membership = optional($item->usermemberships()
                ->where('isapproved', 1)
                ->where('expiration_at', '>=', $current_time)
                ->orderBy('created_at', 'desc')
                ->first());
        
            fputcsv($output, [
                $item->id,
                optional($membership->membership)->name ?? 'No Membership',
                $membership->expiration_at ?? 'No Expiration Date',
                $item->first_name . ' ' . $item->last_name,
                $item->phone_number,
                $item->email,
                $item->created_at,
                $item->updated_at
            ]);
        }

    
        fclose($output);
        exit;
        
    }

}
