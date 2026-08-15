# Physical Data Model

```mermaid
erDiagram
  roles ||--o{ users : assigns
  regions ||--o{ vehicles : contains
  regions ||--o{ drivers : contains
  regions ||--o{ vehicle_bookings : scopes
  users ||--o{ vehicle_bookings : creates
  vehicles ||--o{ vehicle_bookings : allocated
  drivers ||--o{ vehicle_bookings : assigned
  vehicle_bookings ||--o{ booking_approvals : requires
  users ||--o{ booking_approvals : decides
  vehicle_bookings ||--o| vehicle_usage_logs : records
  vehicles ||--o{ fuel_logs : consumes
  vehicles ||--o{ vehicle_services : maintains
  users ||--o{ fuel_logs : records
  users ||--o{ vehicle_services : schedules
  users ||--o{ activity_logs : performs
```

| Table | Purpose | Important constraints |
|---|---|---|
| `roles` | Admin/approver role master | unique `code` |
| `users` | Login identities and approval level | unique `email`; optional `approval_level` |
| `regions` | Operational location master | unique `code` |
| `vehicles` | Fleet master | unique vehicle code/plate; operational status |
| `drivers` | Driver master | unique license number; active flag |
| `vehicle_bookings` | Travel request and lifecycle | unique booking number; indexed vehicle/driver time range |
| `booking_approvals` | Generic sequential decisions | unique `(booking_id, approval_level)` |
| `vehicle_usage_logs` | Completed vehicle usage | one record per booking |
| `fuel_logs` | Fuel consumption monitoring | vehicle/date index; liters, odometer, and calculated total cost |
| `vehicle_services` | Service schedule and history | vehicle/date index; scheduled/completed lifecycle |
| `activity_logs` | Append-only audit trail | indexed module/entity pair |
