# Manufacturing Lines Booking & Parking System - Flowchart

## Complete System Flow

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    LINE BOOKING & PARKING SYSTEM FLOW                    │
└─────────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────────┐
│ 1. FORPLAN COMPONENT - Work Order Line Selection                         │
└──────────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
        ┌─────────────────────────────────────┐
        │  Get Verified Work Orders           │
        │  (Status: "Sent for Batch           │
        │   Allocation")                      │
        └─────────────────────────────────────┘
                      │
                      ▼
        ┌─────────────────────────────────────┐
        │  Display Work Orders in Table       │
        │  - Product Details                  │
        │  - Plan Quantity                    │
        │  - Production Dates/Times            │
        │  - Responsible Person               │
        └─────────────────────────────────────┘
                      │
                      ▼
        ┌─────────────────────────────────────┐
        │  User Clicks "View" Button          │
        │  (Select Line)                     │
        └─────────────────────────────────────┘
                      │
                      ▼
        ┌─────────────────────────────────────┐
        │  Load Available Lines               │
        │  API: getAvailableLines              │
        │  - Filter by Product Dosage Form    │
        │  - Check Line Availability          │
        │  - Get Equipment List               │
        └─────────────────────────────────────┘
                      │
                      ▼
        ┌─────────────────────────────────────┐
        │  Display Lines in Modal             │
        │  - Line No, Name, Section           │
        │  - Capacities (Mfg/Filling)         │
        │  - Equipment Count                  │
        │  - Stages                          │
        └─────────────────────────────────────┘
                      │
        ┌─────────────┴─────────────┐
        │                           │
        ▼                           ▼
┌───────────────┐          ┌───────────────┐
│ Option 1:     │          │ Option 2:     │
│ Select Lines  │          │ Book Line     │
│ (Planning)    │          │ (Direct)      │
└───────────────┘          └───────────────┘
        │                           │
        ▼                           ▼
┌───────────────┐          ┌───────────────┐
│ Add to       │          │ Check         │
│ lineList     │          │ Availability  │
│ (Temporary)   │          │ (Conflict     │
└───────────────┘          │  Detection)   │
        │                   └───────────────┘
        │                           │
        │                           ▼
        │                  ┌───────────────┐
        │                  │ Open Booking  │
        │                  │ Modal         │
        │                  │ - Select      │
        │                  │   Equipment   │
        │                  │ - Set Dates   │
        │                  │ - Assign      │
        │                  │   Responsible │
        │                  │   Person      │
        │                  └───────────────┘
        │                           │
        │                           ▼
        │                  ┌───────────────┐
        │                  │ Book Line     │
        │                  │ API: bookLine │
        │                  │ Status:       │
        │                  │ "Booked"      │
        │                  └───────────────┘
        │                           │
        └───────────┬───────────────┘
                    │
                    ▼
        ┌─────────────────────────────────────┐
        │  User Clicks "Save Selected Lines"   │
        │  (SaveLine Function)                  │
        └─────────────────────────────────────┘
                    │
                    ▼
        ┌─────────────────────────────────────┐
        │  Step 1: Save to Work_order_materials│
        │  API: update_SelectedLines           │
        │  - Update selectedLines column       │
        │  - Store as JSON                     │
        └─────────────────────────────────────┘
                    │
                    ▼
        ┌─────────────────────────────────────┐
        │  Step 2: Park Lines in mfglines     │
        │  API: parkLinesForMfg                │
        │  - Insert/Update line_booking        │
        │  - Status: "Parked"                  │
        │  - Store all line details            │
        │  - Store equipment list               │
        │  - Store booking dates                │
        └─────────────────────────────────────┘
                    │
                    ▼
        ┌─────────────────────────────────────┐
        │  Success Response                    │
        │  - Clear lineList                    │
        │  - Close Modal                       │
        │  - Refresh Work Orders               │
        └─────────────────────────────────────┘


┌──────────────────────────────────────────────────────────────────────────┐
│ 2. MFGLINES COMPONENT - Display Parked Lines                            │
└──────────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
        ┌─────────────────────────────────────┐
        │  Load All Lines from linemaster     │
        │  API: stageLinemasterLog            │
        │  - Get all line configurations       │
        │  - Include equipment, stages         │
        └─────────────────────────────────────┘
                      │
                      ▼
        ┌─────────────────────────────────────┐
        │  Display Lines as Cards              │
        │  - Line Name/Number                  │
        │  - Section, Type, Group              │
        │  - Equipment Count                   │
        │  - Capacity Info                      │
        └─────────────────────────────────────┘
                      │
                      ▼
        ┌─────────────────────────────────────┐
        │  User Clicks on a Line Card          │
        └─────────────────────────────────────┘
                      │
                      ▼
        ┌─────────────────────────────────────┐
        │  Load Work Orders for Selected Line  │
        │  API: getBookingHistory              │
        │  - Filter by linemaster_id           │
        │  - Status: Booked, In Progress,     │
        │    Parked                            │
        └─────────────────────────────────────┘
                      │
                      ▼
        ┌─────────────────────────────────────┐
        │  Display Dynamic Tabs                │
        │  - One Tab per Work Order            │
        │  - Tab Label: Work Order Number      │
        └─────────────────────────────────────┘
                      │
                      ▼
        ┌─────────────────────────────────────┐
        │  User Clicks on a Tab                │
        │  (Select Work Order)                 │
        └─────────────────────────────────────┘
                      │
                      ▼
        ┌─────────────────────────────────────┐
        │  Display Work Order Details          │
        │  - Work Order Info                   │
        │  - Product Details                   │
        │  - Booking Dates/Times               │
        │  - Responsible Person                │
        │  - Status Badge                      │
        │  - Selected Equipment Table          │
        └─────────────────────────────────────┘


┌──────────────────────────────────────────────────────────────────────────┐
│ 3. DATA FLOW DIAGRAM                                                      │
└──────────────────────────────────────────────────────────────────────────┘

    ┌──────────────┐
    │ Line Master  │
    │ (linemaster) │
    └──────┬───────┘
           │
           │ Creates Lines
           ▼
    ┌──────────────┐
    │ FORPLAN      │
    │ Component    │
    └──────┬───────┘
           │
           │ Selects & Saves Lines
           │
           ├─────────────────┬─────────────────┐
           │                 │                 │
           ▼                 ▼                 ▼
    ┌──────────┐    ┌──────────────┐   ┌──────────────┐
    │Work_order│    │line_booking  │   │line_booking  │
    │_materials│    │Status: Booked │   │Status: Parked│
    │(JSON)    │    │(Direct Book) │   │(From Save)   │
    └──────────┘    └──────────────┘   └──────┬───────┘
                                              │
                                              │ Displays
                                              ▼
                                       ┌──────────────┐
                                       │ MFGLINES     │
                                       │ Component    │
                                       │ (Dynamic     │
                                       │  Tabs)       │
                                       └──────────────┘


┌──────────────────────────────────────────────────────────────────────────┐
│ 4. STATUS FLOW                                                            │
└──────────────────────────────────────────────────────────────────────────┘

    Work Order Status Flow:
    
    "Sent for Batch Allocation"
              │
              ▼
    ┌─────────────────────┐
    │ FORPLAN Component    │
    │ - Select Lines       │
    │ - Book/Park Lines    │
    └─────────────────────┘
              │
              ├─────────────────┬─────────────────┐
              │                 │                 │
              ▼                 ▼                 ▼
    ┌──────────────┐   ┌──────────────┐   ┌──────────────┐
    │ Status:      │   │ Status:      │   │ Status:      │
    │ "Booked"     │   │ "Parked"     │   │ "In Progress"│
    │ (Direct      │   │ (From Save   │   │ (Production  │
    │  Booking)    │   │  Button)     │   │  Started)    │
    └──────────────┘   └──────────────┘   └──────────────┘
              │                 │                 │
              └─────────────────┴─────────────────┘
                                │
                                ▼
                       ┌──────────────┐
                       │ MFGLINES     │
                       │ Shows All    │
                       │ (Booked,     │
                       │  Parked,     │
                       │  In Progress)│
                       └──────────────┘


┌──────────────────────────────────────────────────────────────────────────┐
│ 5. API ENDPOINTS FLOW                                                     │
└──────────────────────────────────────────────────────────────────────────┘

    FORPLAN Component APIs:
    ├── getVerifiedWorkOrders
    │   └── Returns: Work orders with status "Sent for Batch Allocation"
    │
    ├── getAvailableLines
    │   └── Returns: Available lines for product (filtered by dosage form)
    │
    ├── checkLineAvailability
    │   └── Returns: Availability check with conflict detection
    │
    ├── bookLine
    │   └── Creates: line_booking entry with status "Booked"
    │
    ├── update_SelectedLines (marketing/po.php)
    │   └── Updates: Work_order_materials.selectedLines (JSON)
    │
    └── parkLinesForMfg
        └── Creates/Updates: line_booking entries with status "Parked"

    MFGLINES Component APIs:
    ├── stageLinemasterLog (bmr/process.php)
    │   └── Returns: All lines from linemaster with equipment & stages
    │
    └── getBookingHistory
        └── Returns: Work orders booked for selected line
            (Status: Booked, In Progress, Parked)


┌──────────────────────────────────────────────────────────────────────────┐
│ 6. DATABASE TABLES INVOLVED                                               │
└──────────────────────────────────────────────────────────────────────────┘

    linemaster
    ├── id (PK)
    ├── line_no
    ├── line_name
    ├── Section
    ├── lineType
    ├── group_name
    ├── MfgLineMinCapacity
    ├── MfgLineMaxCapacity
    ├── FillingLineMinCapacity
    ├── FillingLineMaxCapacity
    └── ... (other fields)

    linemaster_mapped_Equipment
    ├── id (PK)
    ├── linemaster_id (FK → linemaster.id)
    ├── equipment_name
    ├── equipment_code
    ├── capacity
    ├── from_range
    ├── to_range
    ├── unit
    └── equipment_lineType

    linemaster_groups_stages
    ├── id (PK)
    ├── linemaster_id (FK → linemaster.id)
    ├── dosage_form
    └── stage

    Work_order_materials
    ├── id (PK)
    ├── workorder_no
    ├── selectedLines (JSON) ← Saved from FORPLAN
    └── ... (other fields)

    line_booking
    ├── id (PK)
    ├── workorder_no
    ├── linemaster_id (FK → linemaster.id)
    ├── line_no
    ├── product_code
    ├── product_name
    ├── booking_start_date
    ├── booking_start_time
    ├── booking_end_date
    ├── booking_end_time
    ├── responsible_person
    ├── selected_equipments (JSON)
    ├── capacity_required
    ├── no_of_hours_required
    ├── status (Booked/In Progress/Parked/Completed/Cancelled)
    └── ... (audit fields)


┌──────────────────────────────────────────────────────────────────────────┐
│ 7. USER INTERACTION FLOW                                                  │
└──────────────────────────────────────────────────────────────────────────┘

    PLANNING USER (FORPLAN):
    1. View verified work orders
    2. Click "View" to see available lines
    3. Select lines (adds to temporary list)
    4. Option A: Click "Book Line" → Direct booking (Status: "Booked")
    5. Option B: Click "Save Selected Lines" → Parks lines (Status: "Parked")
    6. Lines are now available in MFGLINES

    PRODUCTION USER (MFGLINES):
    1. View all manufacturing lines as cards
    2. Click on a line card
    3. See all work orders (Booked/Parked/In Progress) as tabs
    4. Click on a tab to view work order details
    5. See equipment, dates, responsible person, etc.


┌──────────────────────────────────────────────────────────────────────────┐
│ 8. KEY FEATURES                                                           │
└──────────────────────────────────────────────────────────────────────────┘

    ✓ Conflict Detection: Prevents double booking
    ✓ Status Management: Booked → Parked → In Progress → Completed
    ✓ Equipment Tracking: Stores selected equipment per booking
    ✓ Date/Time Booking: Full scheduling support
    ✓ Responsible Person: Assignment tracking
    ✓ Dynamic Tabs: One tab per work order in MFGLINES
    ✓ Real-time Updates: Changes reflect immediately
    ✓ Data Integrity: Foreign keys and constraints
