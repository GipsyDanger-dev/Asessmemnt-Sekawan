$ErrorActionPreference = 'Stop'
$base = 'http://127.0.0.1:8080/api'

function Login([string]$email) {
  $body = @{ email = $email; password = 'Password123!' } | ConvertTo-Json -Compress
  return (Invoke-RestMethod -Uri "$base/auth/login" -Method Post -ContentType 'application/json' -Body $body).token
}
function Headers([string]$token) { return @{ Authorization = "Bearer $token" } }

$admin = Login 'admin@vehicle.test'
$headers = Headers $admin
$region = (Invoke-RestMethod -Uri "$base/regions" -Headers $headers).data[0]
$vehicle = (Invoke-RestMethod -Uri "$base/vehicles" -Headers $headers).data | Where-Object category -eq 'PASSENGER' | Select-Object -First 1
$driver = (Invoke-RestMethod -Uri "$base/drivers" -Headers $headers).data[0]
$approvers = (Invoke-RestMethod -Uri "$base/approvers" -Headers $headers).data
$l1 = $approvers | Where-Object approval_level -eq 1 | Select-Object -First 1
$l2 = $approvers | Where-Object approval_level -eq 2 | Select-Object -First 1
$dashboard = (Invoke-RestMethod -Uri "$base/dashboard/summary?from=2026-12-01&to=2026-12-31&region_id=$($region.id)&vehicle_type=$($vehicle.vehicle_type)" -Headers $headers).data
if ($null -eq $dashboard.statuses -or $null -eq $dashboard.attention) { throw 'Dashboard filtered summary is incomplete.' }
$suffix = Get-Random -Minimum 10000 -Maximum 99999
$bookingPayload = @{ requester_name = "E2E Requester $suffix"; requester_nik = "NIK$suffix"; department = 'QA'; region_id = $region.id; vehicle_id = $vehicle.id; driver_id = $driver.id; purpose = 'End-to-end verification'; destination = 'Jakarta Office'; start_at = '2026-12-20 09:00:00'; end_at = '2026-12-20 17:00:00'; passenger_count = 2; requested_vehicle_category = 'PASSENGER'; approver_level_1_id = $l1.id; approver_level_2_id = $l2.id; notes = 'Automated E2E verification' } | ConvertTo-Json -Compress
$booking = (Invoke-RestMethod -Uri "$base/bookings" -Method Post -Headers $headers -ContentType 'application/json' -Body $bookingPayload).data
if ($booking.status -ne 'PENDING_LEVEL_1') { throw 'Booking did not enter PENDING_LEVEL_1.' }
$updatedPayload = $bookingPayload | ConvertFrom-Json
$updatedPayload.destination = 'Jakarta QA Office'
$booking = (Invoke-RestMethod -Uri "$base/bookings/$($booking.id)" -Method Put -Headers $headers -ContentType 'application/json' -Body ($updatedPayload | ConvertTo-Json -Compress)).data
if ($booking.destination -ne 'Jakarta QA Office' -or $booking.status -ne 'PENDING_LEVEL_1') { throw 'Booking update did not save or restart Level 1 approval.' }
try {
  Invoke-RestMethod -Uri "$base/bookings" -Method Post -Headers $headers -ContentType 'application/json' -Body $bookingPayload | Out-Null
  throw 'Overlapping booking was unexpectedly accepted.'
} catch {
  if ($_.Exception.Message -match 'unexpectedly accepted') { throw }
}

$l1Token = Login 'manager@vehicle.test'; $l1Headers = Headers $l1Token
$inboxL1 = (Invoke-RestMethod -Uri "$base/approvals/inbox" -Headers $l1Headers).data
if (-not ($inboxL1.booking_id -contains $booking.id)) { throw 'Booking missing from Level 1 inbox.' }
$afterL1 = (Invoke-RestMethod -Uri "$base/bookings/$($booking.id)/approve" -Method Post -Headers $l1Headers -ContentType 'application/json' -Body '{}').data
if ($afterL1.status -ne 'PENDING_LEVEL_2') { throw 'Level 1 approval did not advance booking.' }

$l2Token = Login 'director@vehicle.test'; $l2Headers = Headers $l2Token
$inboxL2 = (Invoke-RestMethod -Uri "$base/approvals/inbox" -Headers $l2Headers).data
if (-not ($inboxL2.booking_id -contains $booking.id)) { throw 'Booking missing from Level 2 inbox.' }
$afterL2 = (Invoke-RestMethod -Uri "$base/bookings/$($booking.id)/approve" -Method Post -Headers $l2Headers -ContentType 'application/json' -Body '{}').data
if ($afterL2.status -ne 'APPROVED') { throw 'Level 2 approval did not approve booking.' }

$completed = (Invoke-RestMethod -Uri "$base/bookings/$($booking.id)/complete" -Method Post -Headers $headers -ContentType 'application/json' -Body '{}').data
if ($completed.status -ne 'COMPLETED') { throw 'Completion did not update booking.' }
Write-Output "E2E PASS booking_id=$($booking.id) booking_number=$($booking.booking_number)"
