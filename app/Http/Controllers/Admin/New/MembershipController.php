<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Membership;

class MembershipController extends Controller
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
            'id', 'name', 'month', 'members_approved', 'members_pending', 'members_reject',
            'created_at', 'updated_at',
        ];
    
        if (!in_array($search_column, $allowed_columns)) {
            $search_column = null;
        }
    
        $data = Membership::query()
            ->withCount([
                'usermemberships as members_approved' => function ($query) {
                    $query->where('isapproved', 1);
                },
                'usermemberships as members_pending' => function ($query) {
                    $query->where('isapproved', 0);
                },
                'usermemberships as members_reject' => function ($query) {
                    $query->where('isapproved', 2);
                },
            ])
            ->when($search && $search_column, function ($query) use ($search, $search_column) {
                if (in_array($search_column, ['members_approved', 'members_pending', 'members_reject'])) {
                    return $query->having($search_column, '=', (int) $search);
                }
                return $query->where($search_column, 'like', "%{$search}%");
            })
            ->paginate(10);
    
        return view('admin.memberships.index', compact('data'));
    }


    public function view($id)
    {
        $data = Membership::findOrFail($id);

        return view('admin.memberships.view', compact('data'));
    }

    public function create()
    {
        return view('admin.memberships.create');
    }

    public function edit($id)
    {
        $data = Membership::findOrFail($id);

        return view('admin.memberships.edit', compact('data'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            // 'currency' => 'required',
            'price' => 'required'
        ]);

        $data = new Membership;
        $data->name = $request->name;
        // $data->currency = $request->currency;
        $data->price = $request->price;
        // $data->year = $request->year;
        $data->month = $request->month;
        // $data->week = $request->week;
        $data->save();

        return redirect()->route('admin.staff-account-management.memberships')->with('success', 'Membership added successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            // 'currency' => 'required',
            'price' => 'required'
        ]);

        $data = Membership::findOrFail($id);
        $data->name = $request->name;
        // $data->currency = $request->currency;
        $data->price = $request->price;
        // $data->year = $request->year;
        $data->month = $request->month;
        // $data->week = $request->week;
        $data->save();

        return redirect()->route('admin.staff-account-management.memberships')->with('success', 'Membership updated successfully');
    }

    public function delete(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:memberships,id',
                'password' => 'required',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }
        
        $user = $request->user();
    
        if (!\Hash::check($request->password, $user->password)) {
            return redirect()->back()->withErrors(['password' => 'Invalid password.'])->withInput();
        }
        
        $data = Membership::findOrFail($request->id);
        $data->delete();

        return redirect()->route('admin.staff-account-management.memberships')->with('success', 'Membership deleted successfully');
    }
    
    public function print(Request $request)
    {
        $data = Membership::all();
        
        $fileName = "memberships_data_" . date('Y-m-d') . ".csv";
    
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=\"$fileName\"");
        header("Pragma: no-cache");
        header("Expires: 0");
    
        $output = fopen('php://output', 'w');
    
        fputcsv($output, [
            'ID', 'Name', 'Price', 'Month', 'Total Members Approved', 'Total Members Pending', 'Total Members Reject', 'Created At', 'Updated At',
        ]);
                    
        foreach ($data as $item) {
            fputcsv($output, [
                $item->id,
                $item->name,
                $item->price,
                $item->month ?? 0,
                $item->usermemberships->where('isapproved', 1)->count(),
                $item->usermemberships->where('isapproved', 2)->count(),
                $item->usermemberships->where('isapproved', 3)->count(),
                $item->created_at,
                $item->updated_at
            ]);
        }

    
        fclose($output);
        exit;
        
    }
}
