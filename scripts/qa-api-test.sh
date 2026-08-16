#!/usr/bin/env bash
# QA API Test Script — Vehicle Booking & Approval System
BASE="http://127.0.0.1:8080/api"
PASS=0; FAIL=0

say() { printf '%s\n' "$*"; }
pass() { PASS=$((PASS+1)); say "  ✅ PASS: $1"; }
fail() { FAIL=$((FAIL+1)); say "  ❌ FAIL: $1"; }
check() { if [ "$2" = "$3" ]; then pass "$1"; else fail "$1 (got: $2, expected: $3)"; fi }
check_contains() { case "$2" in *"$3"*) pass "$1";; *) fail "$1 (missing: $3 in: $(echo "$2" | head -c 200))";; esac }

say "=== QA API Test — $(date) ==="

get_token() {
  curl -s -X POST "$BASE/auth/login" -H "Content-Type: application/json" \
    -d "{\"email\":\"$1\",\"password\":\"Password123!\"}" \
    | sed -n 's/.*"token"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p'
}
# Ambil nilai field JSON (nilai bisa string atau angka): get_field <json> <field> <occurrence>
get_field() {
  echo "$1" | grep -o "\"$2\"[[:space:]]*:[[:space:]]*\"\?[^\",}]*" | sed -n "${3:-1}p" | sed "s/\"$2\"[[:space:]]*:[[:space:]]*\"\?//; s/\"$//"
}
H() { echo "Authorization: Bearer $1"; }

ADMIN_TOKEN=$(get_token "admin@vehicle.test")
L1_TOKEN=$(get_token "manager@vehicle.test")
L2_TOKEN=$(get_token "director@vehicle.test")

if [ -z "$ADMIN_TOKEN" ] || [ -z "$L1_TOKEN" ] || [ -z "$L2_TOKEN" ]; then
  fail "Login semua role (admin/manager/director) — token kosong"
  exit 1
fi
pass "Login admin, approver L1, approver L2 berhasil"

# ---------- 1. AUTH ----------
say "--- AUTH ---"
R=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE/auth/login" -H "Content-Type: application/json" -d '{"email":"admin@vehicle.test","password":"wrongpass"}')
check "Login password salah -> 401" "$R" "401"
R=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE/auth/login" -H "Content-Type: application/json" -d '{"email":"nobody@vehicle.test","password":"Password123!"}')
check "Login email tidak ada -> 401" "$R" "401"
R=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/auth/me" -H "$(H $ADMIN_TOKEN)")
check "GET /auth/me admin -> 200" "$R" "200"
R=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/auth/me" -H "Authorization: Bearer invalid.token.here")
check "Token invalid -> 401" "$R" "401"
R=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/auth/me")
check "Tanpa token -> 401" "$R" "401"

# ---------- 2. MASTER DATA ----------
say "--- MASTER DATA (admin) ---"
REGIONS=$(curl -s "$BASE/regions" -H "$(H $ADMIN_TOKEN)")
REGION_ID=$(get_field "$REGIONS" id 1)
check "GET /regions -> ada data" "$REGION_ID" "$( [ -n "$REGION_ID" ] && echo "$REGION_ID" )"
pass "Region id=$REGION_ID"

VEHICLES=$(curl -s "$BASE/vehicles" -H "$(H $ADMIN_TOKEN)")
VEHICLE_ID=$(get_field "$VEHICLES" id 1)
VEHICLE_CAT=$(get_field "$VEHICLES" category 1)
check "GET /vehicles -> ada data" "$VEHICLE_ID" "$( [ -n "$VEHICLE_ID" ] && echo "$VEHICLE_ID" )"
pass "Vehicle id=$VEHICLE_ID category=$VEHICLE_CAT"

DRIVERS=$(curl -s "$BASE/drivers" -H "$(H $ADMIN_TOKEN)")
DRIVER_ID=$(get_field "$DRIVERS" id 1)
check "GET /drivers -> ada data" "$DRIVER_ID" "$( [ -n "$DRIVER_ID" ] && echo "$DRIVER_ID" )"
pass "Driver id=$DRIVER_ID"

APPROVERS=$(curl -s "$BASE/approvers" -H "$(H $ADMIN_TOKEN)")
L1_ID=$(echo "$APPROVERS" | grep -B2 '"manager@vehicle.test"' | grep -o '"id": "[0-9]*"' | head -1 | sed 's/.*"\([0-9]*\)"/\1/')
L2_ID=$(echo "$APPROVERS" | grep -B2 '"director@vehicle.test"' | grep -o '"id": "[0-9]*"' | head -1 | sed 's/.*"\([0-9]*\)"/\1/')
if [ -n "$L1_ID" ]; then pass "Approver L1 ditemukan (id=$L1_ID)"; else fail "Approver L1 tidak ditemukan"; fi
if [ -n "$L2_ID" ]; then pass "Approver L2 ditemukan (id=$L2_ID)"; else fail "Approver L2 tidak ditemukan"; fi

# RBAC: approver akses master data
R=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/vehicles" -H "$(H $L1_TOKEN)")
check "Approver akses /vehicles -> 403" "$R" "403"
R=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/regions" -H "$(H $L1_TOKEN)")
check "Approver akses /regions -> 403" "$R" "403"

# ---------- 3. DASHBOARD ----------
say "--- DASHBOARD ---"
R=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/dashboard/summary" -H "$(H $ADMIN_TOKEN)")
check "GET /dashboard/summary -> 200" "$R" "200"
R=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/dashboard/summary?from=2026-12-01&to=2026-12-31" -H "$(H $ADMIN_TOKEN)")
check "Summary dengan filter periode -> 200" "$R" "200"
R=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/dashboard/booking-trend" -H "$(H $ADMIN_TOKEN)")
check "GET /dashboard/booking-trend -> 200" "$R" "200"
R=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/dashboard/vehicle-usage" -H "$(H $ADMIN_TOKEN)")
check "GET /dashboard/vehicle-usage -> 200" "$R" "200"
R=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/dashboard/summary" -H "$(H $L1_TOKEN)")
check "Approver akses dashboard -> 403" "$R" "403"

# ---------- 4. BOOKING WORKFLOW ----------
say "--- BOOKING WORKFLOW ---"
SUFFIX=$RANDOM
DAY1=$((1 + SUFFIX % 27))
DAY2=$((1 + (SUFFIX + 3) % 27))
START="2026-12-$DAY1 09:00:00"
END="2026-12-$DAY1 17:00:00"
BOOK_PAYLOAD="{\"requester_name\":\"QA Tester $SUFFIX\",\"requester_nik\":\"NIK$SUFFIX\",\"department\":\"QA\",\"region_id\":$REGION_ID,\"vehicle_id\":$VEHICLE_ID,\"driver_id\":$DRIVER_ID,\"purpose\":\"QA end-to-end test\",\"destination\":\"Site A\",\"start_at\":\"$START\",\"end_at\":\"$END\",\"passenger_count\":2,\"requested_vehicle_category\":\"$VEHICLE_CAT\",\"approver_level_1_id\":$L1_ID,\"approver_level_2_id\":$L2_ID,\"notes\":\"Created by QA script\"}"

# Validasi: end_at <= start_at
BAD_PAYLOAD="{\"requester_name\":\"QA Bad\",\"requester_nik\":\"NIKBAD\",\"department\":\"QA\",\"region_id\":$REGION_ID,\"vehicle_id\":$VEHICLE_ID,\"driver_id\":$DRIVER_ID,\"purpose\":\"Bad\",\"destination\":\"X\",\"start_at\":\"2026-12-20 17:00:00\",\"end_at\":\"2026-12-20 09:00:00\",\"passenger_count\":1,\"requested_vehicle_category\":\"$VEHICLE_CAT\",\"approver_level_1_id\":$L1_ID,\"approver_level_2_id\":$L2_ID}"
R=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE/bookings" -H "$(H $ADMIN_TOKEN)" -H "Content-Type: application/json" -d "$BAD_PAYLOAD")
case "$R" in 4*) pass "Booking end<start ditolak -> $R";; *) fail "Booking end<start seharusnya ditolak (got $R)";; esac

# Approver tidak boleh create booking
R=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE/bookings" -H "$(H $L1_TOKEN)" -H "Content-Type: application/json" -d "$BOOK_PAYLOAD")
check "Approver create booking -> 403" "$R" "403"

# Create booking
R=$(curl -s -X POST "$BASE/bookings" -H "$(H $ADMIN_TOKEN)" -H "Content-Type: application/json" -d "$BOOK_PAYLOAD")
BOOKING_ID=$(get_field "$R" id 1)
BOOKING_NO=$(get_field "$R" booking_number 1)
STATUS=$(get_field "$R" status 1)
check "Booking dibuat -> PENDING_LEVEL_1" "$STATUS" "PENDING_LEVEL_1"
pass "Booking number: $BOOKING_NO (id=$BOOKING_ID)"

# Booking bentrok (kendaraan sama, jadwal overlap)
R=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE/bookings" -H "$(H $ADMIN_TOKEN)" -H "Content-Type: application/json" -d "$BOOK_PAYLOAD")
case "$R" in 4*) pass "Booking kendaraan bentrok ditolak -> $R";; *) fail "Booking kendaraan bentrok seharusnya ditolak (got $R)";; esac

# Show booking
R=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/bookings/$BOOKING_ID" -H "$(H $ADMIN_TOKEN)")
check "GET /bookings/{id} admin -> 200" "$R" "200"
R=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/bookings/$BOOKING_ID" -H "$(H $L1_TOKEN)")
check "GET booking oleh approver L1 -> 200" "$R" "200"

# Update booking
UPD_PAYLOAD="{\"requester_name\":\"QA Tester $SUFFIX\",\"requester_nik\":\"NIK$SUFFIX\",\"department\":\"QA\",\"region_id\":$REGION_ID,\"vehicle_id\":$VEHICLE_ID,\"driver_id\":$DRIVER_ID,\"purpose\":\"QA end-to-end test\",\"destination\":\"Site B Updated\",\"start_at\":\"$START\",\"end_at\":\"$END\",\"passenger_count\":2,\"requested_vehicle_category\":\"$VEHICLE_CAT\",\"approver_level_1_id\":$L1_ID,\"approver_level_2_id\":$L2_ID,\"notes\":\"Updated by QA\"}"
R=$(curl -s -X PUT "$BASE/bookings/$BOOKING_ID" -H "$(H $ADMIN_TOKEN)" -H "Content-Type: application/json" -d "$UPD_PAYLOAD")
check_contains "Update booking tersimpan" "$R" "Site B Updated"

# ---------- 5. APPROVAL WORKFLOW ----------
say "--- APPROVAL WORKFLOW ---"
R=$(curl -s "$BASE/approvals/inbox" -H "$(H $L1_TOKEN)")
check_contains "L1 inbox memuat booking" "$R" "\"booking_id\": \"$BOOKING_ID\""
R=$(curl -s "$BASE/approvals/inbox" -H "$(H $L2_TOKEN)")
case "$R" in *"\"booking_id\":$BOOKING_ID"*) fail "L2 inbox berisi booking sebelum L1 approve";; *) pass "L2 inbox kosong sebelum L1 approve";; esac
R=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE/bookings/$BOOKING_ID/approve" -H "$(H $L2_TOKEN)" -H "Content-Type: application/json" -d '{}')
case "$R" in 4*) pass "L2 approve sebelum L1 ditolak -> $R";; *) fail "L2 approve sebelum L1 seharusnya ditolak (got $R)";; esac
R=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE/bookings/$BOOKING_ID/reject" -H "$(H $L1_TOKEN)" -H "Content-Type: application/json" -d '{}')
case "$R" in 4*) pass "Reject tanpa alasan ditolak -> $R";; *) fail "Reject tanpa alasan seharusnya ditolak (got $R)";; esac
R=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE/bookings/$BOOKING_ID/approve" -H "$(H $ADMIN_TOKEN)" -H "Content-Type: application/json" -d '{}')
check "Admin approve -> 403" "$R" "403"

# L1 approve
R=$(curl -s -X POST "$BASE/bookings/$BOOKING_ID/approve" -H "$(H $L1_TOKEN)" -H "Content-Type: application/json" -d '{}')
STATUS=$(get_field "$R" status 1)
check "Setelah L1 approve -> PENDING_LEVEL_2" "$STATUS" "PENDING_LEVEL_2"

# L2 approve
R=$(curl -s -X POST "$BASE/bookings/$BOOKING_ID/approve" -H "$(H $L2_TOKEN)" -H "Content-Type: application/json" -d '{}')
STATUS=$(get_field "$R" status 1)
check "Setelah L2 approve -> APPROVED" "$STATUS" "APPROVED"

# Approval history
R=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/bookings/$BOOKING_ID/approval-history" -H "$(H $ADMIN_TOKEN)")
check "GET approval-history -> 200" "$R" "200"

# Complete
R=$(curl -s -X POST "$BASE/bookings/$BOOKING_ID/complete" -H "$(H $ADMIN_TOKEN)" -H "Content-Type: application/json" -d '{}')
STATUS=$(get_field "$R" status 1)
check "Complete -> COMPLETED" "$STATUS" "COMPLETED"
R=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE/bookings/$BOOKING_ID/complete" -H "$(H $ADMIN_TOKEN)" -H "Content-Type: application/json" -d '{}')
case "$R" in 4*) pass "Double complete ditolak -> $R";; *) fail "Double complete seharusnya ditolak (got $R)";; esac

# ---------- 6. REJECT FLOW ----------
say "--- REJECT FLOW ---"
REJ_PAYLOAD="{\"requester_name\":\"QA Reject $SUFFIX\",\"requester_nik\":\"NIKR$SUFFIX\",\"department\":\"QA\",\"region_id\":$REGION_ID,\"vehicle_id\":$VEHICLE_ID,\"driver_id\":$DRIVER_ID,\"purpose\":\"Reject test\",\"destination\":\"Site C\",\"start_at\":\"2026-12-$DAY2 09:00:00\",\"end_at\":\"2026-12-$DAY2 17:00:00\",\"passenger_count\":1,\"requested_vehicle_category\":\"$VEHICLE_CAT\",\"approver_level_1_id\":$L1_ID,\"approver_level_2_id\":$L2_ID}"
R=$(curl -s -X POST "$BASE/bookings" -H "$(H $ADMIN_TOKEN)" -H "Content-Type: application/json" -d "$REJ_PAYLOAD")
REJ_ID=$(get_field "$R" id 1)
R=$(curl -s -X POST "$BASE/bookings/$REJ_ID/reject" -H "$(H $L1_TOKEN)" -H "Content-Type: application/json" -d '{"remarks":"Tidak sesuai budget"}')
STATUS=$(get_field "$R" status 1)
check "Reject L1 -> REJECTED" "$STATUS" "REJECTED"

# ---------- 7. REPORTS ----------
say "--- REPORTS ---"
R=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/reports/bookings?from=2026-12-01&to=2026-12-31" -H "$(H $ADMIN_TOKEN)")
check "GET /reports/bookings -> 200" "$R" "200"
R=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/reports/bookings?from=2026-12-01&to=2026-12-31&region_id=$REGION_ID&vehicle_id=$VEHICLE_ID&status=APPROVED&vehicle_type=PASSENGER" -H "$(H $ADMIN_TOKEN)")
check "Reports semua filter -> 200" "$R" "200"
R=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/reports/bookings/export?from=2026-12-01&to=2026-12-31" -H "$(H $ADMIN_TOKEN)")
check "Export Excel -> 200" "$R" "200"

# ---------- 8. ACTIVITY LOG ----------
say "--- ACTIVITY LOG ---"
R=$(curl -s "$BASE/activity-logs" -H "$(H $ADMIN_TOKEN)")
case "$R" in *"BOOKING"*) pass "Activity log mencatat aksi booking";; *) fail "Activity log tidak memuat aksi booking: $(echo "$R" | head -c 300)";; esac

# ---------- 9. FLEET MONITORING ----------
say "--- FLEET MONITORING ---"
F_PAYLOAD="{\"vehicle_id\":$VEHICLE_ID,\"logged_at\":\"2026-12-01 08:00:00\",\"odometer_km\":12500,\"liters\":45.5,\"price_per_liter\":10000,\"station_name\":\"QA Station\",\"notes\":\"QA fuel\"}"
R=$(curl -s -X POST "$BASE/fleet/fuel-logs" -H "$(H $ADMIN_TOKEN)" -H "Content-Type: application/json" -d "$F_PAYLOAD")
case "$R" in *455000*) pass "Fuel log dibuat dgn total_cost 455000";; *) fail "Fuel log total_cost salah: $(echo "$R" | head -c 300)";; esac
R=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/fleet/fuel-logs" -H "$(H $ADMIN_TOKEN)")
check "GET /fleet/fuel-logs -> 200" "$R" "200"
S_PAYLOAD="{\"vehicle_id\":$VEHICLE_ID,\"scheduled_at\":\"2026-12-05\",\"service_type\":\"Periodic service\",\"odometer_km\":12600,\"vendor_name\":\"QA Workshop\",\"cost\":750000,\"notes\":\"QA service\"}"
R=$(curl -s -X POST "$BASE/fleet/services" -H "$(H $ADMIN_TOKEN)" -H "Content-Type: application/json" -d "$S_PAYLOAD")
SVC_ID=$(get_field "$R" id 1)
STATUS=$(get_field "$R" status 1)
check "Service dibuat -> SCHEDULED" "$STATUS" "SCHEDULED"
R=$(curl -s -X POST "$BASE/fleet/services/$SVC_ID/complete" -H "$(H $ADMIN_TOKEN)" -H "Content-Type: application/json" -d '{}')
STATUS=$(get_field "$R" status 1)
check "Service complete -> COMPLETED" "$STATUS" "COMPLETED"
R=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/fleet/usage" -H "$(H $ADMIN_TOKEN)")
check "GET /fleet/usage -> 200" "$R" "200"

# ---------- 10. ADMIN MASTER CRUD ----------
say "--- MASTER CRUD ---"
CR_PAYLOAD="{\"name\":\"Region QA $SUFFIX\",\"code\":\"RQA$SUFFIX\"}"
R=$(curl -s -X POST "$BASE/regions" -H "$(H $ADMIN_TOKEN)" -H "Content-Type: application/json" -d "$CR_PAYLOAD")
CR_ID=$(get_field "$R" id 1)
if [ -n "$CR_ID" ]; then pass "Create region (id=$CR_ID)"; else fail "Create region tidak mengembalikan id: $R"; fi
R=$(curl -s -X PUT "$BASE/regions/$CR_ID" -H "$(H $ADMIN_TOKEN)" -H "Content-Type: application/json" -d '{"name":"Region QA Updated","code":"RQA2"}')
check_contains "Update region" "$R" "Region QA Updated"
R=$(curl -s -o /dev/null -w "%{http_code}" -X DELETE "$BASE/regions/$CR_ID" -H "$(H $ADMIN_TOKEN)")
check "Delete region -> 200" "$R" "200"

# ---------- SUMMARY ----------
say ""
say "=== HASIL QA API ==="
say "PASS: $PASS | FAIL: $FAIL"
[ "$FAIL" -eq 0 ] && say "✅ SEMUA API TEST LULUS" || say "❌ ADA $FAIL TEST GAGAL"
