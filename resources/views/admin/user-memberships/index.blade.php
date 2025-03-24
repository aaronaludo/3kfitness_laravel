@extends('layouts.admin')
@section('title', 'User Memberships')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">User Memberships</h2></div>
                <div class="d-flex align-items-center">
                    <button type="button" class="btn btn-danger ms-2">
                        <i class="fa-solid fa-print"></i>&nbsp;&nbsp;&nbsp;Print
                    </button>
                </div>
            </div>
            <div class="col-lg-12 mb-20">
                <div class="box">
                    <div class="row">
                        <div class="col-lg-5">
                            <form action="{{ route('admin.staff-account-management.user-memberships') }}" method="GET" class="d-flex">
                                <div class="input-group mb-3 mb-lg-0 w-100">
                                    <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                                    <input type="text" class="form-control" name="member_name" placeholder="Search by Member Name" value="{{ request('member_name') }}" />
                                </div>
                        </div>
                        <div class="col-lg-5">
                            <select name="search_column" class="form-select mb-3">
                                <option value="id" {{ request('search_column', 'id') == 'id' ? 'selected' : '' }}>ID</option>
                                <option value="member_name" {{ request('search_column') == 'member_name' ? 'selected' : '' }}>Member Name</option>
                                <option value="membership" {{ request('search_column') == 'membership' ? 'selected' : '' }}>Membership</option>
                                <option value="expiration_at" {{ request('search_column') == 'expiration_at' ? 'selected' : '' }}>Expiration Date</option>
                                <option value="created_at" {{ request('search_column') == 'created_at' ? 'selected' : '' }}>Created Date</option>
                                <option value="updated_at" {{ request('search_column') == 'updated_at' ? 'selected' : '' }}>Updated Date</option>
                                <option value="status" {{ request('search_column') == 'status' ? 'selected' : '' }}>Status</option>
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <button type="submit" class="btn btn-danger w-100">Search</button>
                        </div>
                            </form>
                    </div>
                </div>
            </div>            
            <div class="col-lg-12">
                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif
                <div class="box">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="sortable" data-column="id">ID <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="member_name">Member Name <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="membership">Membership <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="expiration_date">Expiration Date <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="created_date">Created Date <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="updated_date">Updated Date <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="status">Status <i class="fa fa-sort"></i></th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-body">
                                        @foreach($data as $item)
                                            <tr>
                                                <td>{{ $item->id }}</td>
                                                <td>{{ $item->user->first_name }} {{ $item->user->last_name }}</td>
                                                <td>{{ $item->membership->name }}</td>
                                                <td>{{ $item->expiration_at }}</td>
                                                <td>{{ $item->created_at }}</td>
                                                <td>{{ $item->updated_at }}</td>
                                                <td>
                                                    <div class="d-flex">
                                                        <form action="{{ route('admin.staff-account-management.user-memberships.isapprove') }}" method="POST">
                                                            @csrf
                                                            <input type="hidden" name="id" value="{{ $item->id }}">
                                                            <button type="submit" class="btn btn-warning" title="Pending" name="isapproved" value="0" {{ $item->isapproved == 0 ? 'disabled' : '' }}>
                                                                <i class="fa-solid fa-check"></i> Pending
                                                            </button>
                                                            <button type="submit" class="btn btn-success" title="Approve" name="isapproved" value="1" {{ $item->isapproved == 1 ? 'disabled' : '' }}>
                                                                <i class="fa-solid fa-check"></i> Approve
                                                            </button>
                                                            <button type="submit" class="btn btn-danger" title="Reject" name="isapproved" value="2" {{ $item->isapproved == 2 ? 'disabled' : '' }}>
                                                                <i class="fa-solid fa-times"></i> Reject
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex">
                                                        <div class="action-button"><a href="{{ route('admin.staff-account-management.user-memberships.view', $item->id) }}" title="View"><i class="fa-solid fa-eye"></i></a></div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                {{ $data->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection