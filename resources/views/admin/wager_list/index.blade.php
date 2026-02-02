@extends('layouts.master')
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Wager List</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                        <li class="breadcrumb-item active">Wager List</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <!-- User Guide Section -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card card-primary collapsed-card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-book mr-2"></i>အသုံးပြုနည်း လမ်းညွှန် / User Guide
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="text-primary mb-3">
                                        <i class="fas fa-info-circle mr-2"></i>မြန်မာဘာသာ
                                    </h5>
                                    <div class="guide-content">
                                        <h6><strong>၁။ Wager List ရယူရန်</strong></h6>
                                        <ul>
                                            <li><strong>Start Date & Time:</strong> စတင်ရမည့် ရက်စွဲနှင့် အချိန်ကို ရွေးချယ်ပါ</li>
                                            <li><strong>End Date & Time:</strong> ပြီးဆုံးရမည့် ရက်စွဲနှင့် အချိန်ကို ရွေးချယ်ပါ</li>
                                            <li><strong>သတိပြုရန်:</strong> အချိန်ကွာခြားမှုသည် ၅ မိနစ်ထက် မပိုရပါ</li>
                                        </ul>

                                        <h6 class="mt-3"><strong>၂။ Optional Parameters</strong></h6>
                                        <ul>
                                            <li><strong>Offset:</strong> စတင်ရမည့် record အရေအတွက် (default: 0)</li>
                                            <li><strong>Size:</strong> ရယူမည့် record အရေအတွက် (default: 1000, အများဆုံး: 5000)</li>
                                        </ul>

                                        <h6 class="mt-3"><strong>၃။ အသုံးပြုနည်း</strong></h6>
                                        <ol>
                                            <li>ရက်စွဲနှင့် အချိန်များကို ဖြည့်သွင်းပါ</li>
                                            <li>"Fetch Wagers" ခလုတ်ကို နှိပ်ပါ</li>
                                            <li>သို့မဟုတ် "Last 5 Minutes" ခလုတ်ကို နှိပ်ပြီး နောက်ဆုံး ၅ မိနစ်အတွင်း wager များကို ရယူနိုင်ပါသည်</li>
                                            <li>ရလဒ်များကို table တွင် ကြည့်ရှုနိုင်ပါသည်</li>
                                        </ol>

                                        <h6 class="mt-3"><strong>၄။ Wager Details ကြည့်ရှုရန်</strong></h6>
                                        <ul>
                                            <li>Table တွင် "View" ခလုတ်ကို နှိပ်ပါ</li>
                                            <li>Wager ၏ အသေးစိတ် အချက်အလက်များကို ကြည့်ရှုနိုင်ပါသည်</li>
                                            <li>Game History ကိုလည်း ကြည့်ရှုနိုင်ပါသည်</li>
                                        </ul>

                                        <div class="alert alert-warning mt-3">
                                            <i class="fas fa-exclamation-triangle mr-2"></i>
                                            <strong>သတိပြုရန်:</strong> Time range သည် ၅ မိနစ်ထက် ပိုသွားပါက error ပေါ်လာမည်ဖြစ်ပါသည်။
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="text-primary mb-3">
                                        <i class="fas fa-info-circle mr-2"></i>English
                                    </h5>
                                    <div class="guide-content">
                                        <h6><strong>1. Fetching Wager List</strong></h6>
                                        <ul>
                                            <li><strong>Start Date & Time:</strong> Select the starting date and time</li>
                                            <li><strong>End Date & Time:</strong> Select the ending date and time</li>
                                            <li><strong>Note:</strong> Time range must not exceed 5 minutes</li>
                                        </ul>

                                        <h6 class="mt-3"><strong>2. Optional Parameters</strong></h6>
                                        <ul>
                                            <li><strong>Offset:</strong> Starting record number (default: 0)</li>
                                            <li><strong>Size:</strong> Number of records to fetch (default: 1000, max: 5000)</li>
                                        </ul>

                                        <h6 class="mt-3"><strong>3. How to Use</strong></h6>
                                        <ol>
                                            <li>Fill in the date and time fields</li>
                                            <li>Click "Fetch Wagers" button</li>
                                            <li>Or click "Last 5 Minutes" to quickly fetch wagers from the last 5 minutes</li>
                                            <li>View results in the table below</li>
                                        </ol>

                                        <h6 class="mt-3"><strong>4. Viewing Wager Details</strong></h6>
                                        <ul>
                                            <li>Click "View" button in the table</li>
                                            <li>View detailed information about the wager</li>
                                            <li>You can also view Game History</li>
                                        </ul>

                                        <div class="alert alert-warning mt-3">
                                            <i class="fas fa-exclamation-triangle mr-2"></i>
                                            <strong>Important:</strong> If the time range exceeds 5 minutes, an error will occur.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Search Wagers</h3>
                            <p class="text-muted mb-0">Enter date and time range (maximum 5 minutes)</p>
                            <div class="card-tools">
                                <!-- <a href="{{ route('admin.wager-list.wallet-balance') }}" class="btn btn-sm btn-success">
                                    <i class="fas fa-wallet mr-2"></i>Wallet Balance
                                </a> -->
                            </div>
                        </div>
                        <form action="{{ route('admin.wager-list.fetch') }}" method="POST">
                            @csrf
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="start_date">Start Date <span class="text-danger">*</span></label>
                                            <input type="date" 
                                                   class="form-control @error('start_date') is-invalid @enderror" 
                                                   id="start_date" 
                                                   name="start_date" 
                                                   value="{{ old('start_date', $start_date ?? date('Y-m-d')) }}" 
                                                   required>
                                            @error('start_date')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="start_time">Start Time <span class="text-danger">*</span></label>
                                            <input type="time" 
                                                   class="form-control @error('start_time') is-invalid @enderror" 
                                                   id="start_time" 
                                                   name="start_time" 
                                                   value="{{ old('start_time', $start_time ?? '00:00') }}" 
                                                   required>
                                            @error('start_time')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="end_date">End Date <span class="text-danger">*</span></label>
                                            <input type="date" 
                                                   class="form-control @error('end_date') is-invalid @enderror" 
                                                   id="end_date" 
                                                   name="end_date" 
                                                   value="{{ old('end_date', $end_date ?? date('Y-m-d')) }}" 
                                                   required>
                                            @error('end_date')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="end_time">End Time <span class="text-danger">*</span></label>
                                            <input type="time" 
                                                   class="form-control @error('end_time') is-invalid @enderror" 
                                                   id="end_time" 
                                                   name="end_time" 
                                                   value="{{ old('end_time', $end_time ?? '00:05') }}" 
                                                   required>
                                            @error('end_time')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                            <small class="form-text text-muted">Time range must be ≤ 5 minutes</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="offset">Offset (Optional)</label>
                                            <input type="number" 
                                                   class="form-control @error('offset') is-invalid @enderror" 
                                                   id="offset" 
                                                   name="offset" 
                                                   value="{{ old('offset', $offset ?? 0) }}" 
                                                   min="0">
                                            @error('offset')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                            <small class="form-text text-muted">Starting record number (default: 0)</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="size">Size (Optional)</label>
                                            <input type="number" 
                                                   class="form-control @error('size') is-invalid @enderror" 
                                                   id="size" 
                                                   name="size" 
                                                   value="{{ old('size', $size ?? 1000) }}" 
                                                   min="1" 
                                                   max="5000">
                                            @error('size')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                            <small class="form-text text-muted">Number of records (default: 1000, max: 5000)</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search mr-2"></i>Fetch Wagers
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="setLast5Minutes()">
                                    <i class="fas fa-clock mr-2"></i>Last 5 Minutes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            @if(isset($start_timestamp) && isset($end_timestamp))
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card card-info">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-info-circle mr-2"></i>Provider API Timestamps
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-info"><i class="fas fa-play"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Start Timestamp (milliseconds)</span>
                                                <span class="info-box-number">{{ number_format($start_timestamp, 0, '.', '') }}</span>
                                                <small class="text-muted">
                                                    {{ date('Y-m-d H:i:s', $start_timestamp / 1000) }} ({{ date('T', $start_timestamp / 1000) }})
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-success"><i class="fas fa-stop"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">End Timestamp (milliseconds)</span>
                                                <span class="info-box-number">{{ number_format($end_timestamp, 0, '.', '') }}</span>
                                                <small class="text-muted">
                                                    {{ date('Y-m-d H:i:s', $end_timestamp / 1000) }} ({{ date('T', $end_timestamp / 1000) }})
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-12">
                                        <div class="alert alert-info mb-0">
                                            <strong><i class="fas fa-code mr-2"></i>API Parameters:</strong>
                                            <code class="ml-2">start={{ number_format($start_timestamp, 0, '.', '') }}</code>
                                            <code class="ml-2">end={{ number_format($end_timestamp, 0, '.', '') }}</code>
                                            <br>
                                            <small class="mt-2 d-block">
                                                <strong>Time Range:</strong> {{ round(($end_timestamp - $start_timestamp) / 1000 / 60, 2) }} minutes
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if(isset($wagers) && count($wagers) > 0)
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Wager Results</h3>
                                @if(isset($pagination))
                                    <div class="card-tools">
                                        <span class="badge badge-info">Total: {{ $pagination['total'] ?? count($wagers) }}</span>
                                        <span class="badge badge-secondary ml-2">Size: {{ $pagination['size'] ?? count($wagers) }}</span>
                                    </div>
                                @endif
                            </div>
                            <div class="card-body table-responsive p-0">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Code</th>
                                            <th>Member Account</th>
                                            <th>Round ID</th>
                                            <th>Game Type</th>
                                            <th>Game Code</th>
                                            <th>Bet Amount</th>
                                            <th>Valid Bet</th>
                                            <th>Prize Amount</th>
                                            <th>Status</th>
                                            <th>Currency</th>
                                            <th>Created At</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($wagers as $index => $wager)
                                            <tr>
                                                <td>{{ $offset + $index + 1 }}</td>
                                                <td>
                                                    <small class="text-muted">{{ $wager['code'] ?? 'N/A' }}</small>
                                                </td>
                                                <td>{{ $wager['member_account'] ?? 'N/A' }}</td>
                                                <td>{{ $wager['round_id'] ?? 'N/A' }}</td>
                                                <td>
                                                    <span class="badge badge-info">{{ $wager['game_type'] ?? 'N/A' }}</span>
                                                </td>
                                                <td>{{ $wager['game_code'] ?? 'N/A' }}</td>
                                                <td class="text-right">
                                                    <strong>{{ number_format($wager['bet_amount'] ?? 0, 2) }}</strong>
                                                </td>
                                                <td class="text-right">
                                                    {{ number_format($wager['valid_bet_amount'] ?? 0, 2) }}
                                                </td>
                                                <td class="text-right">
                                                    <span class="text-success">
                                                        <strong>{{ number_format($wager['prize_amount'] ?? 0, 2) }}</strong>
                                                    </span>
                                                </td>
                                                <td>
                                                    @php
                                                        $status = $wager['status'] ?? 'N/A';
                                                        $badgeClass = match($status) {
                                                            'BET' => 'badge-warning',
                                                            'WIN' => 'badge-success',
                                                            'LOSE' => 'badge-danger',
                                                            'CANCEL' => 'badge-secondary',
                                                            default => 'badge-info'
                                                        };
                                                    @endphp
                                                    <span class="badge {{ $badgeClass }}">{{ $status }}</span>
                                                </td>
                                                <td>{{ $wager['currency'] ?? 'N/A' }}</td>
                                                <td>
                                                    @if(isset($wager['created_at']))
                                                        {{ date('Y-m-d H:i:s', $wager['created_at'] / 1000) }}
                                                    @else
                                                        N/A
                                                    @endif
                                                </td>
                                                <td>
                                                    @if(isset($wager['id']) || isset($wager['code']))
                                                        <a href="{{ route('admin.wager-list.show', $wager['id'] ?? $wager['code']) }}" 
                                                           class="btn btn-sm btn-info" 
                                                           title="View Details">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @elseif(isset($wagers) && count($wagers) === 0)
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            No wagers found for the selected time range.
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection

@section('scripts')
<script>
    function setLast5Minutes() {
        const now = new Date();
        const fiveMinutesAgo = new Date(now.getTime() - 5 * 60 * 1000);
        
        // Set end date and time (current)
        document.getElementById('end_date').value = now.toISOString().split('T')[0];
        document.getElementById('end_time').value = now.toTimeString().split(' ')[0].substring(0, 5);
        
        // Set start date and time (5 minutes ago)
        document.getElementById('start_date').value = fiveMinutesAgo.toISOString().split('T')[0];
        document.getElementById('start_time').value = fiveMinutesAgo.toTimeString().split(' ')[0].substring(0, 5);
        
        // Update timestamp preview if function exists
        if (typeof updateTimestampPreview === 'function') {
            updateTimestampPreview();
        }
    }

    function updateTimestampPreview() {
        const startDate = document.getElementById('start_date').value;
        const startTime = document.getElementById('start_time').value;
        const endDate = document.getElementById('end_date').value;
        const endTime = document.getElementById('end_time').value;

        if (startDate && startTime && endDate && endTime) {
            const startDateTime = new Date(startDate + 'T' + startTime);
            const endDateTime = new Date(endDate + 'T' + endTime);

            if (!isNaN(startDateTime.getTime()) && !isNaN(endDateTime.getTime())) {
                const startTimestamp = startDateTime.getTime();
                const endTimestamp = endDateTime.getTime();
                const timeDiffMinutes = (endTimestamp - startTimestamp) / 1000 / 60;

                // Update or create preview element
                let previewDiv = document.getElementById('timestamp-preview');
                if (!previewDiv) {
                    previewDiv = document.createElement('div');
                    previewDiv.id = 'timestamp-preview';
                    previewDiv.className = 'alert alert-secondary mt-3';
                    document.querySelector('.card-body').appendChild(previewDiv);
                }

                if (timeDiffMinutes > 5) {
                    previewDiv.className = 'alert alert-warning mt-3';
                    previewDiv.innerHTML = `
                        <strong><i class="fas fa-exclamation-triangle mr-2"></i>Warning:</strong> 
                        Time range is ${timeDiffMinutes.toFixed(2)} minutes (must be ≤ 5 minutes)
                        <br>
                        <strong>Start:</strong> ${startTimestamp} (${startDateTime.toLocaleString()})
                        <br>
                        <strong>End:</strong> ${endTimestamp} (${endDateTime.toLocaleString()})
                    `;
                } else if (timeDiffMinutes <= 0) {
                    previewDiv.className = 'alert alert-danger mt-3';
                    previewDiv.innerHTML = `
                        <strong><i class="fas fa-times-circle mr-2"></i>Error:</strong> 
                        End time must be greater than start time
                        <br>
                        <strong>Start:</strong> ${startTimestamp} (${startDateTime.toLocaleString()})
                        <br>
                        <strong>End:</strong> ${endTimestamp} (${endDateTime.toLocaleString()})
                    `;
                } else {
                    previewDiv.className = 'alert alert-info mt-3';
                    previewDiv.innerHTML = `
                        <strong><i class="fas fa-info-circle mr-2"></i>Provider Timestamps Preview:</strong>
                        <br>
                        <strong>Start:</strong> <code>${startTimestamp}</code> (${startDateTime.toLocaleString()})
                        <br>
                        <strong>End:</strong> <code>${endTimestamp}</code> (${endDateTime.toLocaleString()})
                        <br>
                        <strong>Range:</strong> ${timeDiffMinutes.toFixed(2)} minutes
                        <button class="btn btn-sm btn-outline-primary mt-2" onclick="copyTimestamps(${startTimestamp}, ${endTimestamp})">
                            <i class="fas fa-copy mr-1"></i>Copy Timestamps
                        </button>
                    `;
                }
            }
        }
    }

    function copyTimestamps(start, end) {
        const text = `start=${start}&end=${end}`;
        navigator.clipboard.writeText(text).then(function() {
            alert('Timestamps copied to clipboard!');
        }).catch(function(err) {
            console.error('Failed to copy:', err);
        });
    }

    // Add event listeners to update preview on input change
    document.addEventListener('DOMContentLoaded', function() {
        const dateInputs = ['start_date', 'start_time', 'end_date', 'end_time'];
        dateInputs.forEach(function(id) {
            const input = document.getElementById(id);
            if (input) {
                input.addEventListener('change', updateTimestampPreview);
                input.addEventListener('input', updateTimestampPreview);
            }
        });
        
        // Initial preview if values exist
        updateTimestampPreview();
    });
</script>
@endsection

