# Plan: Tentative Start/End from product_stage_days in MFG Lines

## Goal
In the **Production Stages** table (Tentative Start Date, Tentative Completion Date), **fetch from the new saved data** (`product_stage_days` + BMR stages) and **calculate** tentative start and completion per stage so they auto-fill instead of "Set Date".

## Current Flow
1. User selects a **line** → `getStagesForLine(linemasterId)` loads **lineStages** (from `bmr/line_booking.php?type=getLineStages`).
2. `getWorkOrdersForLine(linemasterId, lineNo)` loads **workOrders** (from `getBookingHistory`); each `wo` has `workorder_no`, `product_code`, `product_name`, and may have `booking_start_date`, `booking_end_date` from the line booking.
3. **loadStageDates()** fetches `getStageDates` and fills **stageDates** keyed by `workorder_no-dosage_form-stage`. Tentative start/completion are shown in the table; if empty, UI shows "Set Date".

## Data Sources
- **Stages and days (new saved data):** `master/product.php?type=getProductStagesWithDays&product_code=XXX` → returns `[{ stage_id, stages (name), days }, ...]` in process order (from `product_stage_days` + `manufacturing_process_stages`).
- **Line stages (existing):** `lineStages` from `getLineStages` → each has `stage`, `dosage_form` (stage names may match BMR stage names).
- **Batch start (existing):** For each work order, use **booking_start_date** (and optionally **booking_start_time**) as the overall batch start. If missing, fallback to today or leave tentative empty.

## Calculation Logic
1. **Batch start date:** For work order `wo`, set  
   `batchStart = wo.booking_start_date + (wo.booking_start_time || '08:00:00')`  
   (as a Date or date string). If `wo.booking_start_date` is missing, skip auto-fill for that WO or use a fallback (e.g. today).
2. **Fetch stage days:** For each `wo.product_code`, call `getProductStagesWithDays(product_code)` once per product (can cache by product_code to avoid duplicate calls when multiple WOs share the same product).
3. **Match line stages to API stages:**  
   - `lineStages` is in display order (Sr. 1, 2, 3, …).  
   - API returns stages in process order with `stages` (name) and `days`.  
   - Match by name: for each `lineStage` (e.g. `stage.stage === "Dispensing"`), find the item in API response where `apiStage.stages` or `apiStage.stage_name` equals `lineStage.stage`. Use that item’s `days` (if not found, use 0).
4. **Sequential dates:**  
   - Let `current = batchStart`.  
   - For each `lineStage` in order:  
     - **Tentative start** = `current`.  
     - **Tentative completion** = `current + days` (add `days` days to `current`).  
     - Then set `current = tentative completion` for the next stage.  
   - Format as date string `YYYY-MM-DD` and time string `HH:mm:ss` (or keep existing format used in `stageDates`).
5. **Merge into stageDates:**  
   - After **loadStageDates()** completes (so existing saved tentative dates from `getStageDates` are present), run the calculation.  
   - For each (wo, lineStage), set `stageDates[key].tentative_start_date`, `tentative_start_time`, `tentative_date` (completion), `tentative_time` from the calculation.  
   - Option: only fill when current `stageDates[key]` has no tentative start/completion, so saved values are not overwritten.

## Implementation Steps

### 1. Add a method: `calculateTentativeDatesFromStageDays()`
- Runs after `loadStageDates()` (e.g. called from inside the `loadStageDates` subscribe callback, or from `getStagesForLine`’s setTimeout after `loadStageDates`).
- For each work order in `workOrders`:
  - If no `product_code` or no `booking_start_date`, skip (or use fallback).
  - Call `master/product.php?type=getProductStagesWithDays&product_code=wo.product_code` (or use a cached map by product_code to avoid duplicate calls for same product).
- Build a map: for this WO, for each index in `lineStages`, get `days` by matching `lineStages[i].stage` to API stage name; then compute tentative start/completion sequentially from `wo.booking_start_date` + time.
- Write results into `stageDates` for keys `getStageKey(wo.workorder_no, lineStage.dosage_form, lineStage.stage)` (tentative_start_date, tentative_start_time, tentative_date, tentative_time).

### 2. Call the new method at the right time
- In **loadStageDates()** subscribe: after assigning `stageDates` from API response, call `this.calculateTentativeDatesFromStageDays()` so calculated dates are merged in (and optionally only where tentative is currently empty).
- Or: from **getStagesForLine** in the setTimeout, after `loadStageDates()` is triggered, ensure we run calculation after stage dates are loaded (e.g. call calculation inside `loadStageDates` at the end of its subscribe).

### 3. Optional: persist calculated dates
- If the backend `bmr/line_booking.php` has a type like `saveStageDates` or `updateStageDates`, after calculating we could POST tentative start/completion so they are “saved” and returned by `getStageDates` next time. Otherwise, calculation remains client-side and runs each time the line is selected.

### 4. Edge cases
- **Line stages vs BMR stages order:** If order differs, matching by name and using `lineStages` order for display keeps the table correct; we only need “days” per stage name from API.
- **Missing product_code / booking_start_date:** Skip auto-fill for that WO; leave "Set Date" as is.
- **API returns empty:** No change to stageDates; user continues to set dates manually.

## Files to Touch
- **`mfglines.component.ts`**: Add `calculateTentativeDatesFromStageDays()`, call `getProductStagesWithDays` per product (with cache), match stages by name, compute sequential dates, merge into `stageDates`. Call this after `loadStageDates()` in its subscribe.
- **`mfglines.component.html`**: No change required if we only fill `stageDates`; the existing bindings for tentative start/completion will show the calculated values.

## Summary
- **Fetch:** `getProductStagesWithDays(product_code)` for each WO’s product (cached by product_code).
- **Calculate:** For each WO, batch start = `booking_start_date` + time; for each lineStage in order, tentative start = current, tentative completion = current + days (from matched API stage), then advance current.
- **Apply:** Write into `stageDates` so the existing Production Stages table shows tentative start and tentative completion without user clicking "Set Date".
