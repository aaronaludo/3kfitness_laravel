@extends('layouts.admin')
@section('title', 'Classes')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">Classes</h2></div>
                <div class="d-flex align-items-center">
                    <a class="btn btn-danger" href="{{ route('admin.gym-management.schedules.create') }}">
                        <i class="fa-solid fa-plus"></i>&nbsp;&nbsp;&nbsp;Add
                    </a>
                    <form action="{{ route('admin.gym-management.schedules.print') }}" method="POST" id="print-form">
                        @csrf
                        <button class="btn btn-danger ms-2" type="submit" id="print-submit-button">
                            <i class="fa-solid fa-print"></i>&nbsp;&nbsp;&nbsp;
                            <span id="print-loader" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                            Print
                        </button>
                    </form>
                </div>
            </div>
            <div class="col-lg-12 mb-20">
                <div class="box">
                    <div class="row">
                            <div class="col-lg-5">
                                <form action="{{ route('admin.gym-management.schedules') }}" method="GET" class="d-flex">
                                    <div class="input-group mb-3 mb-lg-0 w-100">
                                        <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                                        <input type="text" class="form-control" name="name" placeholder="Search by " value="{{ request('name') }}" />
                                    </div>
                            </div>
                            <div class="col-lg-5">
                                <select name="search_column" class="form-select mb-3">
                                    <option value="id" {{ request('search_column', 'id') == 'id' ? 'selected' : '' }}>ID</option>
                                    <option value="name" {{ request('search_column') == 'name' ? 'selected' : '' }}>Class Name</option>
                                    <option value="class_code" {{ request('search_column') == 'class_code' ? 'selected' : '' }}>Class Code</option>
                                    <option value="trainer_id" {{ request('search_column') == 'trainer_id' ? 'selected' : '' }}>Trainer</option>
                                    <option value="slots" {{ request('search_column') == 'slots' ? 'selected' : '' }}>Slots</option>
                                    <option value="class_start_date" {{ request('search_column') == 'class_start_date' ? 'selected' : '' }}>Class Start Date and Time</option>
                                    <option value="class_end_date" {{ request('search_column') == 'class_end_date' ? 'selected' : '' }}>Class End Date and Time</option>
                                    <option value="isadminapproved" {{ request('search_column') == 'isadminapproved' ? 'selected' : '' }}>Status</option>
                                    <option value="rejection_reason" {{ request('search_column') == 'rejection_reason' ? 'selected' : '' }}>Reject Reason</option>
                                    <option value="created_at" {{ request('search_column') == 'created_at' ? 'selected' : '' }}>Created Date</option>
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
                <div class="row">
                    <div class="col-12 col-lg-6">
                        <div class="tile tile-primary">
                            <div class="tile-heading">Total Classes Created by Admin</div>
                            <div class="tile-body">
                                <i class="fa-solid fa-hashtag"></i>
                                <h2 class="float-end">{{ $classescreatedbyadmin }}</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="tile tile-primary">
                            <div class="tile-heading">Total Classes Created by Staff</div>
                            <div class="tile-body">
                                <i class="fa-solid fa-hashtag"></i>
                                <h2 class="float-end">{{ $classescreatedbystaff }}</h2>
                            </div>
                        </div>
                    </div>
            </div>
            <div class="col-lg-12">
                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="box">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="table-responsive mb-3">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                    <tr>
                                            <th class="sortable" data-column="id">ID <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="class_name">Class Name <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="class_code">Class Code <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="trainer">Trainer <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="slots">Slots <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="total_members_enrolled">Total Members Enrolled <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="start_date">Class Start Date and Time <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="end_date">Class End Date and Time <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="status">Status <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="categorization">Categorization <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="admin_acceptance">Admin Acceptance <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="reject_reason">Reject Reason <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="created_date">Created Date <i class="fa fa-sort"></i></th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-body">
                                        @foreach($data as $item)
                                            <tr>
                                                <td>{{ $item->id }}</td>
                                                <td>{{ $item->name }}</td>
                                                <td>{{ $item->class_code }}</td>
                                                <td>
                                                    {{ $item->trainer_id == 0 ? 'No Trainer for now' : optional($item->user)->first_name .' '. optional($item->user)->last_name }}
                                                </td>
                                                <td>{{ $item->slots }}</td>
                                                <td>
                                                    <a href="#" 
                                                        class="show-modal text-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#userSchedulesModal"
                                                        data-id="{{ $item->id }}"
                                                        data-user-schedules="{{ $item->user_schedules }}">
                                                        {{ $item->user_schedules_count }}
                                                    </a>
                                                </td>
                                                <td>{{ $item->class_start_date }}</td>
                                                <td>{{ $item->class_end_date }}</td>
                                                <td>{{ $item->isenabled ? 'Enabled' : 'Disabled' }}</td>
                                                <td>
                                                    @php
                                                        $now = now(); // Get the current date and time using Laravel's helper
                                                        $start_date = \Carbon\Carbon::parse($item->class_start_date);
                                                        $end_date = \Carbon\Carbon::parse($item->class_end_date);
                                                    @endphp
                                                
                                                    @if ($now->lt($start_date))
                                                        <span class="badge rounded-pill bg-warning">Future</span>
                                                    @elseif ($now->between($start_date, $end_date))
                                                        <span class="badge rounded-pill bg-success">Present</span>
                                                    @else
                                                        <span class="badge rounded-pill bg-primary">Past</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <form method="POST" action="{{ route('admin.gym-management.schedules.adminacceptance') }}">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="id" value="{{ $item->id }}">
                                                
                                                        <div class="d-flex">
                                                            <button type="submit" class="btn btn-warning me-2 fw-bold" value="0" name="isadminapproved" {{ $item->isadminapproved == 0 ? 'disabled' : '' }}>Pending</button>
                                                            <button type="submit" class="btn btn-success me-2 fw-bold" value="1" name="isadminapproved" {{ $item->isadminapproved == 1 ? 'disabled' : '' }}>Approve</button>
                                                            @if($item->user_schedules_count == 0)
                                                                <button type="submit" class="btn btn-danger me-2 fw-bold" value="2" name="isadminapproved" {{ $item->isadminapproved == 2 ? 'disabled' : '' }}>Reject</button>
                                                            @else
                                                                <button type="button" class="btn btn-danger fw-bold" data-bs-toggle="modal" data-bs-target="#rejectModal-{{ $item->id }}" data-id="{{ $item->id }}" {{ $item->isadminapproved == 2 ? 'disabled' : '' }}>Reject</button>
                                                            @endif
                                                        </div>
                                                    </form>
                                                </td>
                                                <td>
                                                    {{ $item->rejection_reason }}
                                                </td>
                                                <td>
                                                    {{ $item->created_at }}
                                                </td>
                                                <td>
                                                    <div class="d-flex">
                                                        <!--<div class="action-button"><a href="{{ route('admin.gym-management.schedules.view', $item->id) }}" title="View"><i class="fa-solid fa-eye"></i></a></div>-->
                                                        <div class="action-button"><a href="{{ route('admin.gym-management.schedules.edit', $item->id) }}" title="Edit"><i class="fa-solid fa-pencil text-primary"></i></a></div>
                                                        <div class="action-button">
                                                            <!--<form action="{{ route('admin.gym-management.schedules.delete') }}" method="POST" style="display: inline;">-->
                                                            <!--    @csrf-->
                                                            <!--    @method('DELETE')-->
                                                            <!--    <input type="hidden" name="id" value="{{ $item->id }}">-->
                                                            <!--    <button type="submit" title="Delete" style="background: none; border: none; padding: 0; cursor: pointer;">-->
                                                            <!--        <i class="fa-solid fa-trash text-danger"></i>-->
                                                            <!--    </button>-->
                                                            <!--</form>-->
                                                            <button type="button" data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $item->id }}" data-id="{{ $item->id }}" title="Delete" style="background: none; border: none; padding: 0; cursor: pointer;">
                                                                <i class="fa-solid fa-trash text-danger"></i>
                                                            </button>
                                                        </div> 
                                                    </div>
                                                </td>
                                            </tr>
                                            <div class="modal fade" id="rejectModal-{{ $item->id }}" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="rejectModalLabel">Reject Reason ({{ $item->class_code }})</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('admin.gym-management.schedules.rejectmessage') }}" method="POST" id="reject-modal-form-{{ $item->id }}">
                                                            @csrf
                                                            <input type="hidden" name="id" value="{{ $item->id }}">
                                                            <div class="modal-body">
                                                                <textarea class="form-control" name="rejection_reason" id="rejectReason" rows="4" placeholder="Enter reason for rejection"></textarea>
                                                                <div class="input-group mt-3">
                                                                    <input class="form-control password-input" type="password" name="password" placeholder="Enter your password">
                                                                    <button class="btn btn-outline-secondary reveal-button" type="button">Show</button>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <!--<button type="submit" class="btn btn-danger">Submit</button>-->
                                                                <button class="btn btn-danger" type="submit" id="reject-modal-submit-button-{{ $item->id }}">
                                                                    <span id="reject-modal-loader-{{ $item->id }}" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                                                    Submit
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal fade" id="deleteModal-{{ $item->id }}" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="rejectModalLabel">Are you sure you want to delete this class ({{ $item->class_code }})?</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('admin.gym-management.schedules.delete') }}" method="POST" id="delete-modal-form-{{ $item->id }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="id" value="{{ $item->id }}">
                                                            <div class="modal-body">
                                                                <div class="input-group mt-3">
                                                                    <input class="form-control password-input" type="password" name="password" placeholder="Enter your password">
                                                                    <button class="btn btn-outline-secondary reveal-button" type="button">Show</button>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <!--<button type="submit" class="btn btn-danger">Submit</button>-->
                                                                <button class="btn btn-danger" type="submit" id="delete-modal-submit-button-{{ $item->id }}">
                                                                    <span id="delete-modal-loader-{{ $item->id }}" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                                                    Submit
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal fade" id="userSchedulesModal" tabindex="-1" aria-labelledby="userSchedulesModalLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="userSchedulesModalLabel">User Schedules</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <ul id="modalUserSchedules" class="list-group">
                                                                <li class='list-group-item'>No users found</li>
                                                            </ul>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <script>
                                                document.addEventListener("DOMContentLoaded", function () {
                                                const modal = document.getElementById("userSchedulesModal");
                                                const modalList = document.getElementById("modalUserSchedules");
                                                const modalTitle = document.getElementById("userSchedulesModalLabel");

                                                modal.addEventListener("show.bs.modal", function (event) {
                                                    let triggerElement = event.relatedTarget;
                                                    let dataUserSchedules = triggerElement.getAttribute("data-user-schedules");

                                                    const dataUserSchedulesList = JSON.parse(dataUserSchedules);
                                                    console.log(dataUserSchedulesList);

                                                    modalTitle.innerHTML = `User Schedules - 1`;

                                                    modalList.innerHTML = "";
                                                    dataUserSchedulesList.forEach(item => {
                                                        modalList.innerHTML += `<li class='list-group-item'>${item.user_id} (${item.schedule_id})</li>`;
                                                    });
                                                });
                                            });
                                            </script>
                                            <script>
                                                document.getElementById('reject-modal-form-{{ $item->id }}').addEventListener('submit', function(e) {
                                                    const submitButton = document.getElementById('reject-modal-submit-button-{{ $item->id }}');
                                                    const loader = document.getElementById('reject-modal-loader-{{ $item->id }}');
                                        
                                                    submitButton.disabled = true;
                                                    loader.classList.remove('d-none');
                                                });
                                            </script>
                                            <script>
                                                document.getElementById('delete-modal-form-{{ $item->id }}').addEventListener('submit', function(e) {
                                                    const submitButton = document.getElementById('delete-modal-submit-button-{{ $item->id }}');
                                                    const loader = document.getElementById('delete-modal-loader-{{ $item->id }}');
                                        
                                                    submitButton.disabled = true;
                                                    loader.classList.remove('d-none');
                                                });
                                            </script>
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