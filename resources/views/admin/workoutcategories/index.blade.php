@extends('layouts.admin')
@section('title', 'Workout Categories')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">Workout Categories</h2></div>
                <div class="d-flex align-items-center">
                    <a class="btn btn-danger" href="{{ route('admin.workout-categories.create') }}"><i class="fa-solid fa-plus"></i>&nbsp;&nbsp;&nbsp;Add</a>
                    <form action="{{ route('admin.workout-categories.print') }}" method="POST" id="print-form">
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
                            <form action="{{ route('admin.workout-categories.index') }}" method="GET" class="d-flex">
                                <div class="input-group mb-3 mb-lg-0 w-100">
                                    <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                                    <input type="text" class="form-control" name="name" placeholder="Search by" value="{{ request('name') }}" />
                                </div>
                        </div>
                        <div class="col-lg-5">
                            <select name="search_column" class="form-select mb-3">
                                <option value="id" {{ request('search_column', 'id') == 'id' ? 'selected' : '' }}>ID</option>
                                <option value="title" {{ request('search_column') == 'title' ? 'selected' : '' }}>Title</option>
                                <option value="trainer" {{ request('search_column') == 'trainer' ? 'selected' : '' }}>Trainer</option>
                                <option value="calories" {{ request('search_column') == 'calories' ? 'selected' : '' }}>Calories</option>
                                <option value="equipment" {{ request('search_column') == 'equipment' ? 'selected' : '' }}>Equipment</option>
                                <option value="net_duration" {{ request('search_column') == 'net_duration' ? 'selected' : '' }}>Net Duration</option>
                                <option value="benefits" {{ request('search_column') == 'benefits' ? 'selected' : '' }}>Benefits</option>
                                <option value="session_details" {{ request('search_column') == 'session_details' ? 'selected' : '' }}>Session Details</option>
                                <option value="created_at" {{ request('search_column') == 'created_at' ? 'selected' : '' }}>Created Date</option>
                                <option value="updated_at" {{ request('search_column') == 'updated_at' ? 'selected' : '' }}>Updated Date</option>
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
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="sortable" data-column="id">ID <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="title">Title <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="trainer">Trainer <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="calories">Calories <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="equipment">Equipment <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="net_duration">Net Duration <small>(minutes)</small> <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="benefits">Benefits <small>(JSON format)</small> <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="session_details">Session Details <small>(JSON format)</small> <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="created_date">Created Date <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="updated_date">Updated Date <i class="fa fa-sort"></i></th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-body">
                                        @foreach($data as $item)
                                            <tr>
                                                <td>{{ $item->id }}</td>
                                                <td>{{ $item->title }}</td>
                                                <td>{{ $item->trainer_id }}</td>
                                                <td>{{ $item->calories }}</td>
                                                <td>{{ $item->equipment }}</td>
                                                <td>{{ collect(json_decode($item->session_details, true))->sum('duration') }}</td>
                                                <td>{{ $item->benefits }}</td>
                                                <td>{{ $item->session_details }}</td>
                                                <td>{{ $item->created_at }}</td>
                                                <td>{{ $item->updated_at }}</td>
                                                <td>
                                                    <div class="d-flex">
                                                        <!--<div class="action-button"><a href="{{ route('admin.motivational-videos.view', $item->id) }}" title="View"><i class="fa-solid fa-eye"></i></a></div>-->
                                                        <div class="action-button"><a href="{{ route('admin.workout-categories.edit', $item->id) }}" title="Edit"><i class="fa-solid fa-pencil text-primary"></i></a></div>
                                                        <div class="action-button">
                                                            <!--<form action="{{ route('admin.workout-categories.delete') }}" method="POST" style="display: inline;">-->
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
                                            <div class="modal fade" id="deleteModal-{{ $item->id }}" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="rejectModalLabel">Are you sure you want to delete ({{ $item->title }})?</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('admin.workout-categories.delete') }}" method="POST" id="main-form-{{ $item->id }}">
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
                                                                <button class="btn btn-danger" type="submit" id="submitButton-{{ $item->id }}">
                                                                    <span id="loader-{{ $item->id }}" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                                                    Submit
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <script>
                                                document.getElementById('main-form-{{ $item->id }}').addEventListener('submit', function(e) {
                                                    const submitButton = document.getElementById('submitButton-{{ $item->id }}');
                                                    const loader = document.getElementById('loader-{{ $item->id }}');
                                        
                                                    // Disable the button and show loader
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