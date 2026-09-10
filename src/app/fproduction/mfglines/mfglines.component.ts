import { Component, OnInit } from '@angular/core';
import { forkJoin, of } from 'rxjs';
import { map, catchError } from 'rxjs/operators';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-mfglines',
  templateUrl: './mfglines.component.html',
  styleUrls: ['./mfglines.component.css']
})
export class MfglinesComponent implements OnInit {
  
  constructor(private service: DataAccessService) { }

  loading: boolean = false;
  lines: any[] = [];
  selectedLine: any = null;
  workOrders: any[] = [];
  activeTabIndex: number = 0;
  activeSubTabIndex: { [key: string]: number } = {}; // Track active sub-tab for each work order
  activeMainTab: { [key: string]: number } = {}; // Track active main tab for each work order
  lineStages: any[] = []; // Stages for the selected line
  stageDates: { [key: string]: { 
    tentative_start_date: string, 
    tentative_start_time: string,
    actual_start_date: string, 
    actual_start_time: string,
    tentative_date: string,
    tentative_time: string,
    actual_date: string,
    actual_time: string,
    stage_status: string,
    duration_hours: number | null
  } } = {}; // Store stage dates by workorder_no-stage key
  
  // Cache for product stage days (from product_stage_days / BMR) for tentative date calculation
  productStageDaysCache: { [productCode: string]: any[] } = {};
  
  // QA Approved Batches properties
  qaApprovedBatches: { [key: string]: any[] } = {}; // Store QA approved batches for each work order
  qaLoading: { [key: string]: boolean } = {}; // Loading state for each work order
  product_name: string = '';
  from_date: string = '';
  to_date: string = '';
  today: string = new Date().toISOString().split('T')[0];
  
  // Dispensing Checking properties
  dispensingCheckingData: any[] = []; // Store all dispensing checking materials
  dispensingCheckingLoading: boolean = false;
  
  // Received Dispensing properties (similar to receiving component)
  receivedDispensingData: any[] = []; // Store received dispensing requests
  receivedDispensingLoading: boolean = false;
  selectedReceivedResult: any = null;
  selectedReceivedIndex: number = -1;
  isReceivedView: boolean = false;
  isReceivedStart: boolean = false;
  isReceivedViewDispensing: boolean = false;
  receivedDispensingCompleted: string = 'No';
  isReceivedDispensingCompleted: boolean = false;
  receivedRemarks: string = '';
  
  // Dispensing Log properties (similar to log component)
  dispensingLogData: any[] = []; // Store dispensing log data
  dispensingLogLoading: boolean = false;
  selectedLogResult: any = null;
  selectedLogIndex: number = -1;
  isLogView: boolean = false;
  isLogStart: boolean = false;
  isLogViewDispensing: boolean = false;
  logDispensingCompleted: string = 'No';
  isLogDispensingCompleted: boolean = false;
  
  // Manpower Allocation properties
  manpowerAllocationData: any[] = []; // Store allocated manpower data
  manpowerAllocationLoading: boolean = false;
  availableEmployees: any[] = []; // Available employees for allocation
  selectedEmployeeId: string = '';
  selectedEmployeeName: string = '';
  employeeRole: string = ''; // e.g., 'Operator', 'Supervisor', 'Helper'
  allocationRemarks: string = '';

  // Modal state management
  showQaApprovedBatchesModal: boolean = false;
  showStartProductionModal: boolean = false;
  showStatusLogModal: boolean = false;
  showDispensingActivitySubTabsModal: boolean = false;
  showManufacturingAllocationSubTabsModal: boolean = false;
  showEmbrSubTabsModal: boolean = false;
  showContentModal: boolean = false;
  showMaterialDetailsModal: boolean = false;
  activeContentModalType: string = ''; // 'dispensing', 'manufacturing', 'embr'
  activeSubTabForModal: number = 0;
  currentWorkOrderNo: string = ''; // Track current work order for modals
  selectedMaterial: any = null;
  selectedMaterialIndex: number = -1;
  
  // Dispensing activity properties (similar to approval component)
  available_ars_data: any[] = [];
  available_ars: any[] = [];
  balance_qty: number = 0;
  fifo_method: string = '';
  selectedResult: any = null;

  // Booking Tab Properties
  showBookingTab: boolean = false;
  showBookingView: boolean = false; // Show booking view from home screen
  showLineSelectionModal: boolean = false; // Show line selection when booking from home
  availableWorkOrders: any[] = [];
  bookingLoading: boolean = false;
  lineBookings: any[] = [];
  currentLineBooking: any = null;
  allLineBookings: any[] = []; // All bookings across all lines
  availableLinesForBooking: any[] = []; // Available lines for selected work order

  // Booking Modal Properties
  showBookingModal: boolean = false;
  showStageDateModal: boolean = false;
  currentStageDateEdit: {
    workorder_no: string,
    dosage_form: string,
    stage: string,
    date_type: string, // 'tentative_start', 'actual_start', 'tentative_completion', 'actual_completion'
    field_label: string
  } | null = null;
  stageDateForm: {
    date: string,
    time: string
  } = { date: '', time: '' };
  selectedWOForBooking: any = null;
  bookingFormData: {
    workorder_no: string;
    linemaster_id: number;
    line_no: string;
    product_code: string;
    product_name: string;
    booking_start_date: string;
    booking_start_time: string;
    booking_end_date: string;
    booking_end_time: string;
    responsible_person: string;
    selected_equipments: any[];
    capacity_required: string;
    remarks: string;
  } = {
    workorder_no: '',
    linemaster_id: 0,
    line_no: '',
    product_code: '',
    product_name: '',
    booking_start_date: '',
    booking_start_time: '08:00:00',
    booking_end_date: '',
    booking_end_time: '17:00:00',
    responsible_person: '',
    selected_equipments: [],
    capacity_required: '',
    remarks: ''
  };
  bookingHoursExceedMax: boolean = false;
  maxWorkingHours: number = 0;
  availabilityCheckResult: any = null;

  // Update Actual Dates Modal Properties
  showUpdateDatesModal: boolean = false;
  selectedBookingForUpdate: any = null;
  actualDatesForm: {
    booking_id: number;
    actual_start_date: string;
    actual_start_time: string;
    actual_end_date: string;
    actual_end_time: string;
  } = {
    booking_id: 0,
    actual_start_date: '',
    actual_start_time: '',
    actual_end_date: '',
    actual_end_time: ''
  };

  ngOnInit() {
    this.getLines();
    // Load all bookings if booking view is needed
  }

  // Open Booking View from Home Screen
  openBookingView() {
    this.showBookingView = true;
    this.loadAllLineBookings();
    this.loadAvailableWorkOrders();
  }

  // Load available lines for a work order (when booking from home screen)
  loadAvailableLinesForWO(wo: any) {
    if (!wo.product_code) {
      alert('Product code not found for this work order');
      return;
    }
    
    this.service.get(
      `bmr/line_booking.php?type=getAvailableLines&product_code=${wo.product_code}&workorder_no=${wo.workorder_no}`
    ).subscribe(
      (response: any) => {
        this.availableLinesForBooking = (response || []).filter((line: any) => line.isAvailable);
        if (this.availableLinesForBooking.length === 0) {
          alert('No available lines found for this product');
        }
      },
      (error) => {
        console.error('Error loading available lines:', error);
        alert('Error loading available lines');
      }
    );
  }

  // Close Booking View
  closeBookingView() {
    this.showBookingView = false;
    this.allLineBookings = [];
  }

  // Select line from booking view
  selectLineForBooking(booking: any) {
    // Find the line from lines array
    const line = this.lines.find(l => l.id === booking.linemaster_id || l.line_no === booking.line_no);
    if (line) {
      this.closeBookingView();
      this.selectLine(line);
      // Optionally scroll to booking tab or highlight the booking
    } else {
      alert('Line not found. Please refresh the page.');
    }
  }

  // Load all bookings across all lines
  loadAllLineBookings() {
    this.bookingLoading = true;
    const statusParam = encodeURIComponent('Booked,In Progress,Parked,Completed');
    this.service.get(`bmr/line_booking.php?type=getBookingHistory&status=${statusParam}`).subscribe(
      (response: any) => {
        this.allLineBookings = response || [];
        this.bookingLoading = false;
      },
      (error) => {
        console.error('Error loading all bookings:', error);
        this.bookingLoading = false;
      }
    );
  }

  // Get all lines from linemaster
  getLines() {
    this.loading = true;
    this.service.get('bmr/process.php?type=stageLinemasterLog').subscribe(
      (response: any) => {
        this.lines = response || [];
        this.loading = false;
        // Load bookings for all lines to show booked period on home page
        this.loadBookingsForAllLines();
      },
      (error) => {
        console.error('Error fetching lines:', error);
        this.loading = false;
      }
    );
  }

  // Load bookings for all lines to display booked period on home page
  loadBookingsForAllLines() {
    const statusParam = encodeURIComponent('Booked,In Progress,Parked');
    this.service.get(`bmr/line_booking.php?type=getBookingHistory&status=${statusParam}`).subscribe(
      (response: any) => {
        const allBookings = response || [];
        // Attach current booking to each line
        this.lines.forEach(line => {
          const lineBooking = allBookings.find((b: any) => 
            (b.linemaster_id == line.id || b.line_no === line.line_no) &&
            (b.status === 'In Progress' || b.status === 'Booked' || b.status === 'Parked') &&
            (!b.actual_end_date || b.actual_end_date === '')
          );
          line.currentBooking = lineBooking || null;
        });
      },
      (error) => {
        console.error('Error loading bookings for all lines:', error);
      }
    );
  }

  // Get booked period display for a line
  getBookedPeriodDisplay(line: any): string {
    if (!line.currentBooking) {
      return 'Available';
    }
    const startDate = line.currentBooking.booking_start_date || '';
    const endDate = line.currentBooking.booking_end_date || '';
    if (startDate && endDate) {
      return `${startDate} to ${endDate}`;
    }
    return 'Booked';
  }

  // Calculate total hours from tentative start to end
  calculateBookedHours(booking: any): number {
    if (!booking || !booking.booking_start_date || !booking.booking_end_date) {
      return 0;
    }
    
    try {
      const startDateStr = booking.booking_start_date;
      const startTimeStr = booking.booking_start_time || '00:00:00';
      const endDateStr = booking.booking_end_date;
      const endTimeStr = booking.booking_end_time || '00:00:00';
      
      const startDateTime = new Date(`${startDateStr}T${startTimeStr}`);
      const endDateTime = new Date(`${endDateStr}T${endTimeStr}`);
      
      if (isNaN(startDateTime.getTime()) || isNaN(endDateTime.getTime())) {
        return 0;
      }
      
      const diffMs = endDateTime.getTime() - startDateTime.getTime();
      const diffHours = diffMs / (1000 * 60 * 60); // Convert milliseconds to hours
      
      return Math.round(diffHours * 100) / 100; // Round to 2 decimal places
    } catch (e) {
      console.error('Error calculating booked hours:', e);
      return 0;
    }
  }

  // Calculate total hours from booking form data (tentative start to end)
  calculateBookingFormHours(): number {
    if (!this.bookingFormData.booking_start_date || !this.bookingFormData.booking_end_date) {
      return 0;
    }
    
    try {
      const startDateStr = this.bookingFormData.booking_start_date;
      const startTimeStr = this.bookingFormData.booking_start_time || '00:00:00';
      const endDateStr = this.bookingFormData.booking_end_date;
      const endTimeStr = this.bookingFormData.booking_end_time || '00:00:00';
      
      const startDateTime = new Date(`${startDateStr}T${startTimeStr}`);
      const endDateTime = new Date(`${endDateStr}T${endTimeStr}`);
      
      if (isNaN(startDateTime.getTime()) || isNaN(endDateTime.getTime())) {
        return 0;
      }
      
      const diffMs = endDateTime.getTime() - startDateTime.getTime();
      const diffHours = diffMs / (1000 * 60 * 60); // Convert milliseconds to hours
      
      return Math.round(diffHours * 100) / 100; // Round to 2 decimal places
    } catch (e) {
      console.error('Error calculating booking form hours:', e);
      return 0;
    }
  }

  // Get MaxWorkingHours for a line by linemaster_id
  getMaxWorkingHours(linemasterId: number): number {
    const line = this.lines.find(l => l.id == linemasterId);
    if (line && line.MaxWorkingHours) {
      return parseFloat(line.MaxWorkingHours) || 0;
    }
    return 0;
  }

  // Validate booking hours against MaxWorkingHours (called from HTML on change)
  validateBookingHours() {
    if (!this.bookingFormData.linemaster_id || !this.bookingFormData.booking_start_date || !this.bookingFormData.booking_end_date) {
      this.bookingHoursExceedMax = false;
      this.maxWorkingHours = 0;
      return;
    }
    
    const bookedHours = this.calculateBookingFormHours();
    this.maxWorkingHours = this.getMaxWorkingHours(this.bookingFormData.linemaster_id);
    this.bookingHoursExceedMax = this.maxWorkingHours > 0 && bookedHours > this.maxWorkingHours;
  }

  // Select a line and load its work orders
  selectLine(line: any) {
    this.selectedLine = line;
    this.activeTabIndex = 0;
    this.activeSubTabIndex = {}; // Reset sub-tab indices
    this.activeMainTab = {}; // Reset main tab indices
    this.qaApprovedBatches = {}; // Reset QA batches
    this.qaLoading = {}; // Reset loading states
    this.workOrders = []; // Clear previous work orders
    
    // Load booking data
    this.loadLineBookings();
    this.loadAvailableWorkOrders();
    
    this.getWorkOrdersForLine(line.id, line.line_no);
    // Stages will be loaded after work orders are fetched (from getWorkOrdersForLine)
  }

  // Handle work order tab change
  onWorkOrderTabClick(index: number, workorderNo: string, event?: Event) {
    if (event) {
      event.preventDefault();
      event.stopPropagation();
      event.stopImmediatePropagation();
    }
    
    // Prevent multiple rapid clicks - check if already active
    if (this.activeTabIndex === index) {
      return;
    }
    
    // Validate index is within bounds
    if (index < 0 || index >= this.workOrders.length) {
      console.error('Invalid tab index:', index);
      return;
    }
    
    // Set active tab index immediately - use Object.assign to trigger change detection
    const previousIndex = this.activeTabIndex;
    this.activeTabIndex = index;
    
    // Force Angular change detection
    if (typeof (window as any).ng !== 'undefined') {
      // Angular is available
    }
    
    // Load QA Approved Batches when work order tab is selected
    if (workorderNo && this.workOrders[index]?.workorder_no === workorderNo) {
      // Use requestAnimationFrame for smoother transition
      requestAnimationFrame(() => {
        setTimeout(() => {
          this.getQaApprovedBatches(workorderNo);
        }, 10);
      });
    }
  }

  // Load QA Approved Batches when work order tab is selected
  onWorkOrderTabChange(workorderNo: string) {
    if (workorderNo) {
      // Always reload to get latest data
      this.getQaApprovedBatches(workorderNo);
    }
  }

  // Set active main tab for a work order
  setActiveMainTab(workorderNo: string, mainTabIndex: number, event?: Event) {
    if (event) {
      event.preventDefault();
      event.stopPropagation();
      event.stopImmediatePropagation();
    }
    this.activeMainTab[workorderNo] = mainTabIndex;
    // Reset sub-tab when main tab changes
    this.activeSubTabIndex[workorderNo] = 0;
    this.currentWorkOrderNo = workorderNo;
    
    // Open modals based on tab type
    switch(mainTabIndex) {
      case 0: // QA Approved Batches
        this.openQaApprovedBatchesModal(workorderNo);
        break;
      case 1: // Dispensing Activity (has sub-tabs)
        this.openSubTabsModal('dispensing', workorderNo);
        break;
      case 2: // Manufacturing Allocation (has sub-tabs)
        this.openSubTabsModal('manufacturing', workorderNo);
        break;
      case 3: // Start Production
        this.openStartProductionModal(workorderNo);
        break;
      case 4: // EMBR (has sub-tabs)
        this.openSubTabsModal('embr', workorderNo);
        break;
      case 5: // Status Log
        this.openStatusLogModal(workorderNo);
        break;
    }
  }
  
  // Open QA Approved Batches Modal
  openQaApprovedBatchesModal(woNo: string) {
    this.currentWorkOrderNo = woNo;
    this.getQaApprovedBatches(woNo);
    this.showQaApprovedBatchesModal = true;
  }
  
  // Open Start Production Modal
  openStartProductionModal(woNo: string) {
    this.currentWorkOrderNo = woNo;
    this.showStartProductionModal = true;
  }
  
  // Open Status Log Modal
  openStatusLogModal(woNo: string) {
    this.currentWorkOrderNo = woNo;
    this.showStatusLogModal = true;
  }
  
  // Open Sub-tabs Selection Modal
  openSubTabsModal(tabType: string, woNo: string) {
    this.currentWorkOrderNo = woNo;
    this.activeContentModalType = tabType;
    
    switch(tabType) {
      case 'dispensing':
        this.showDispensingActivitySubTabsModal = true;
        break;
      case 'manufacturing':
        this.showManufacturingAllocationSubTabsModal = true;
        break;
      case 'embr':
        this.showEmbrSubTabsModal = true;
        break;
    }
  }
  
  // Open Content Modal for Sub-tabs
  openContentModal(contentType: string, subTabIndex: number, woNo: string) {
    this.currentWorkOrderNo = woNo;
    this.activeContentModalType = contentType;
    this.activeSubTabForModal = subTabIndex;
    
    // Close sub-tabs selection modal
    this.showDispensingActivitySubTabsModal = false;
    this.showManufacturingAllocationSubTabsModal = false;
    this.showEmbrSubTabsModal = false;
    
    // Load received dispensing data if opening Received Dispensing tab
    if (contentType === 'dispensing' && subTabIndex === 1) {
      this.loadReceivedDispensingData();
    }
    
    // Load dispensing log data if opening Dispensing Log tab
    if (contentType === 'dispensing' && subTabIndex === 2) {
      this.loadDispensingLogData();
    }
    
    // Load manpower allocation data if opening Allocate Manpower tab
    if (contentType === 'manufacturing' && subTabIndex === 3) {
      this.loadManpowerAllocationData();
      this.loadAvailableEmployees();
    }
    
    // Load data if needed
    if (contentType === 'dispensing' && subTabIndex === 0) {
      this.getDispensingCheckingData();
    }
    
    // Open content modal
    this.showContentModal = true;
  }
  
  // Close all modals
  closeModal() {
    this.showQaApprovedBatchesModal = false;
    this.showStartProductionModal = false;
    this.showStatusLogModal = false;
    this.showContentModal = false;
  }
  
  // Close Sub-tabs Selection Modal
  closeSubTabsModal() {
    this.showDispensingActivitySubTabsModal = false;
    this.showManufacturingAllocationSubTabsModal = false;
    this.showEmbrSubTabsModal = false;
  }
  
  // Get active main tab for a work order
  getActiveMainTab(workorderNo: string): number {
    return this.activeMainTab[workorderNo] || 0;
  }
  
  // Set active sub-tab for a work order
  setActiveSubTab(workorderNo: string, subTabIndex: number, event?: Event) {
    if (event) {
      event.preventDefault();
      event.stopPropagation();
      event.stopImmediatePropagation();
    }
    // Update sub-tab index
    this.activeSubTabIndex[workorderNo] = subTabIndex;
  }

  // Get active sub-tab for a work order
  getActiveSubTab(workorderNo: string): number {
    return this.activeSubTabIndex[workorderNo] || 0;
  }

  // Get stages for the selected line from getProductStagesWithDays API
  getStagesForLine(linemasterId: number) {
    // Check if work orders are loaded
    if (this.workOrders.length === 0) {
      // If no work orders yet, wait a bit and try again
      setTimeout(() => this.getStagesForLine(linemasterId), 200);
      return;
    }

    // Get unique product codes from work orders
    const productCodes = [...new Set(
      this.workOrders
        .filter(wo => wo.product_code)
        .map(wo => wo.product_code)
    )] as string[];

    if (productCodes.length === 0) {
      console.warn('No product codes found in work orders');
      this.lineStages = [];
      return;
    }

    // Use the first product code (or could fetch for all products and merge)
    const productCode = productCodes[0];
    
    // Fetch stages from getProductStagesWithDays API
    this.service.get(`master/product.php?type=getProductStagesWithDays&product_code=${encodeURIComponent(productCode)}`).subscribe(
      (response: any) => {
        const stages = Array.isArray(response) ? response : (response?.data || []);
        
        // Map API response to lineStages format
        // API returns: {stage_name, days, sequence_order, ...}
        // lineStages needs: {stage, dosage_form, ...}
        this.lineStages = stages.map((apiStage: any) => {
          // Get dosage_form from work orders (use first work order's dosage_form)
          const wo = this.workOrders.find(w => w.product_code === productCode);
          const dosageForm = wo?.dosage_form || '';
          
          return {
            stage: apiStage.stage_name || apiStage.stages || '',
            dosage_form: dosageForm,
            stage_id: apiStage.stage_id || apiStage.id,
            days: apiStage.days,
            sequence_order: apiStage.sequence_order
          };
        });

        // Sort by sequence_order to ensure correct order
        this.lineStages.sort((a: any, b: any) => {
          const orderA = a.sequence_order !== undefined ? a.sequence_order : (a.stage_id || 0);
          const orderB = b.sequence_order !== undefined ? b.sequence_order : (b.stage_id || 0);
          return orderA - orderB;
        });

        // Cache the stages for date calculations
        this.productStageDaysCache[productCode] = stages;

        // Load existing stage dates after stages are loaded
        setTimeout(() => {
          if (this.workOrders.length > 0 && this.lineStages.length > 0) {
            this.loadStageDates();
          }
        }, 100);
      },
      (error) => {
        console.error('Error fetching stages from getProductStagesWithDays:', error);
        this.lineStages = [];
      }
    );
  }

  // Load stage dates for all work orders
  loadStageDates() {
    if (this.workOrders.length === 0 || this.lineStages.length === 0) return;
    
    const workorderNos = this.workOrders.map(wo => wo.workorder_no).join(',');
    this.service.get(`bmr/line_booking.php?type=getStageDates&workorder_nos=${workorderNos}`).subscribe(
      (response: any) => {
        // Initialize stageDates object with all date/time fields
        this.workOrders.forEach(wo => {
          this.lineStages.forEach(stage => {
            const key = `${wo.workorder_no}-${stage.dosage_form}-${stage.stage}`;
            const existing = (response || []).find((r: any) => 
              r.workorder_no === wo.workorder_no && 
              r.dosage_form === stage.dosage_form && 
              r.stage === stage.stage
            );
            this.stageDates[key] = {
              tentative_start_date: existing?.tentative_start_date || '',
              tentative_start_time: existing?.tentative_start_time || '',
              actual_start_date: existing?.actual_start_date || '',
              actual_start_time: existing?.actual_start_time || '',
              tentative_date: existing?.tentative_completion_date || '',
              tentative_time: existing?.tentative_completion_time || '',
              actual_date: existing?.actual_completion_date || '',
              actual_time: existing?.actual_completion_time || '',
              stage_status: existing?.stage_status || 'Not Started',
              duration_hours: existing?.duration_hours || null
            };
          });
        });
        // Fill tentative start/completion from product_stage_days when not already set
        this.calculateTentativeDatesFromStageDays();
      },
      (error) => {
        console.error('Error loading stage dates:', error);
      }
    );
  }

  /**
   * Fetch stage days from product_stage_days (master/product.php getProductStagesWithDays)
   * and calculate tentative start/completion per stage from booking_start_date.
   * Only fills when tentative is currently empty (does not overwrite saved values).
   */
  calculateTentativeDatesFromStageDays() {
    if (this.workOrders.length === 0 || this.lineStages.length === 0) return;
    const productCodes = [...new Set(
      this.workOrders
        .filter(wo => wo.product_code && wo.booking_start_date)
        .map(wo => wo.product_code)
    )] as string[];
    if (productCodes.length === 0) return;

    const toFetch = productCodes.filter(pc => !this.productStageDaysCache[pc]);
    const requests = toFetch.length
      ? toFetch.map(pc =>
          this.service.get('master/product.php?type=getProductStagesWithDays&product_code=' + encodeURIComponent(pc)).pipe(
            map((stages: any) => ({ pc, stages: Array.isArray(stages) ? stages : (stages?.data || []) })),
            catchError(() => of({ pc, stages: [] }))
          )
        )
      : [];

    if (requests.length === 0) {
      this.applyTentativeDatesFromCache();
      return;
    }
    forkJoin(requests).subscribe(results => {
      results.forEach(({ pc, stages }) => { this.productStageDaysCache[pc] = stages; });
      this.applyTentativeDatesFromCache();
    });
  }

  private applyTentativeDatesFromCache() {
    const pad = (n: number) => (n < 10 ? '0' : '') + n;
    const toDateStr = (d: Date) =>
      `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    const toTimeStr = (d: Date) =>
      `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;

    this.workOrders.forEach(wo => {
      if (!wo.product_code || !wo.booking_start_date) return;
      const stagesWithDays = this.productStageDaysCache[wo.product_code];
      if (!stagesWithDays || !Array.isArray(stagesWithDays) || stagesWithDays.length === 0) return;

      // Sort stages by sequence_order to ensure correct order
      const sortedStages = [...stagesWithDays].sort((a: any, b: any) => {
        const orderA = a.sequence_order || a.id || 0;
        const orderB = b.sequence_order || b.id || 0;
        return orderA - orderB;
      });

      const timeStr = (wo.booking_start_time || '08:00:00').trim();
      const [h, m, s] = timeStr.split(/[:\s]/).map(x => parseInt(x, 10) || 0);
      // Start from booking_start_date for the first stage
      let currentStartDate = new Date(wo.booking_start_date + 'T00:00:00');
      currentStartDate.setHours(h, m, s, 0);

      // Process stages in sequence order
      sortedStages.forEach((apiStage: any, apiIndex: number) => {
        const stageName = (apiStage.stage_name || apiStage.stages || '').toString().trim();
        
        // Find matching lineStage by name
        const matchingLineStage = this.lineStages.find((lineStage: any) => {
          const lineStageName = (lineStage.stage || '').toString().trim();
          return lineStageName.toLowerCase() === stageName.toLowerCase();
        });

        // If no match by name, try by index (fallback)
        const lineStage = matchingLineStage || this.lineStages[apiIndex];
        if (!lineStage) {
          console.warn(`No matching lineStage found for API stage: ${stageName} at index ${apiIndex}`);
          return;
        }

        const key = this.getStageKey(wo.workorder_no, lineStage.dosage_form || '', lineStage.stage || '');
        const existing = this.stageDates[key];
        
        // Get days from API stage data (convert to number if string)
        const days = apiStage.days ? (typeof apiStage.days === 'string' ? parseFloat(apiStage.days) : apiStage.days) : 0;

        // Calculate start and end dates
        // Start date = previous stage's completion date (or booking_start_date for first stage)
        const startDate = new Date(currentStartDate.getTime());
        // End date = start date + days from API
        const endDate = new Date(currentStartDate.getTime());
        endDate.setDate(endDate.getDate() + days);

        console.log(`Stage ${apiIndex + 1} (${stageName}): Start=${toDateStr(startDate)}, Days=${days}, End=${toDateStr(endDate)}`);

        // Always recalculate tentative dates based on sequence (will overwrite if dates don't follow sequence)
        // This ensures dates are sequential: stage 0 completion = stage 1 start, etc.
        if (existing) {
          // Update existing entry with calculated sequential dates
          this.stageDates[key] = {
            ...existing,
            tentative_start_date: toDateStr(startDate),
            tentative_start_time: toTimeStr(startDate),
            tentative_date: existing.tentative_date || toDateStr(endDate),
            tentative_time: existing.tentative_time || toTimeStr(endDate)
          };
        } else {
          // Create new entry if it doesn't exist
          this.stageDates[key] = {
            tentative_start_date: toDateStr(startDate),
            tentative_start_time: toTimeStr(startDate),
            actual_start_date: '',
            actual_start_time: '',
            tentative_date: toDateStr(endDate),
            tentative_time: toTimeStr(endDate),
            actual_date: '',
            actual_time: '',
            stage_status: 'Not Started',
            duration_hours: null
          };
        }

        // Set next stage's start date = current stage's completion date
        // This ensures sequential chaining: stage N completion = stage N+1 start
        currentStartDate = new Date(endDate.getTime());
      });
    });
  }

  // Get stage date key
  getStageKey(workorderNo: string, dosageForm: string, stage: string): string {
    return `${workorderNo}-${dosageForm}-${stage}`;
  }

  // Check if tentative date can be set for this stage
  canSetTentativeDate(workorderNo: string, dosageForm: string, stage: string, selectedDate?: string): { allowed: boolean; reason: string } {
    // Find current work order index
    const currentIndex = this.workOrders.findIndex(wo => wo.workorder_no === workorderNo);
    if (currentIndex === -1 || currentIndex === 0) {
      // First work order or not found - always allowed
      return { allowed: true, reason: '' };
    }
    
    const currentWO = this.workOrders[currentIndex];
    const lineName = this.selectedLine?.line_name || this.selectedLine?.line_no || 'N/A';
    
    // Check all previous work orders (earlier tabs) for booking conflicts
    for (let i = 0; i < currentIndex; i++) {
      const prevWO = this.workOrders[i];
      
      // Check if previous work order is completed
      const isCompleted = prevWO.status === 'Completed';
      if (isCompleted) {
        continue; // Skip completed work orders
      }
      
      // Check if previous work order has booking dates
      if (prevWO.booking_start_date && prevWO.booking_end_date) {
        const prevStart = new Date(prevWO.booking_start_date);
        const prevEnd = new Date(prevWO.booking_end_date);
        
        // If a date is being selected, check if it falls within previous booking period
        if (selectedDate) {
          const selected = new Date(selectedDate);
          if (selected >= prevStart && selected <= prevEnd) {
            return {
              allowed: false,
              reason: `Cannot set tentative date. Line "${lineName}" is already booked for work order ${prevWO.workorder_no} (Product: ${prevWO.product_name || prevWO.product_code || 'N/A'}) from ${prevWO.booking_start_date} to ${prevWO.booking_end_date}. Please select a date outside this period or wait for the previous work order to complete.`
            };
          }
        }
        
        // Also check if current work order's booking dates overlap with previous (even without selected date)
        if (currentWO.booking_start_date && currentWO.booking_end_date) {
          const currentStart = new Date(currentWO.booking_start_date);
          const currentEnd = new Date(currentWO.booking_end_date);
          
          // Check for date overlap
          if ((currentStart >= prevStart && currentStart <= prevEnd) || 
              (currentEnd >= prevStart && currentEnd <= prevEnd) ||
              (currentStart <= prevStart && currentEnd >= prevEnd)) {
            return {
              allowed: false,
              reason: `Cannot set tentative date. Line "${lineName}" is already booked for work order ${prevWO.workorder_no} (Product: ${prevWO.product_name || prevWO.product_code || 'N/A'}) from ${prevWO.booking_start_date} to ${prevWO.booking_end_date}. Current work order booking period overlaps with previous booking.`
            };
          }
        }
      }
      
      // Check if previous work order has this stage but no actual completion date
      const prevKey = this.getStageKey(prevWO.workorder_no, dosageForm, stage);
      const prevStageData = this.stageDates[prevKey];
      
      if (prevStageData) {
        const hasTentative = prevStageData.tentative_date && prevStageData.tentative_date !== '';
        const hasActual = prevStageData.actual_date && prevStageData.actual_date !== '';
        
        // If previous work order has tentative date but no actual date, block tentative date
        if (hasTentative && !hasActual) {
          return { 
            allowed: false, 
            reason: `Cannot set tentative date. Previous work order ${prevWO.workorder_no} (Product: ${prevWO.product_name || prevWO.product_code || 'N/A'}, Line: ${lineName}) has this stage (${stage}) but actual completion date is not filled yet. Please complete the previous work order's stage first.` 
          };
        }
      }
    }
    
    return { allowed: true, reason: '' };
  }

  // Update tentative completion date
  updateTentativeDate(workorderNo: string, dosageForm: string, stage: string, date: string) {
    // Check if allowed (pass the selected date for validation)
    const validation = this.canSetTentativeDate(workorderNo, dosageForm, stage, date);
    if (!validation.allowed) {
      alert(validation.reason);
      // Reset the input value to previous value
      const key = this.getStageKey(workorderNo, dosageForm, stage);
      const currentValue = this.stageDates[key]?.tentative_date || '';
      // Use setTimeout to reset after Angular change detection
      setTimeout(() => {
        const input = document.querySelector(`input[data-wo="${workorderNo}"][data-stage="${stage}"]`) as HTMLInputElement;
        if (input) {
          input.value = currentValue;
        }
      }, 0);
      return;
    }
    
    const key = this.getStageKey(workorderNo, dosageForm, stage);
    if (!this.stageDates[key]) {
      this.stageDates[key] = { 
        tentative_start_date: '', 
        tentative_start_time: '',
        actual_start_date: '', 
        actual_start_time: '',
        tentative_date: '', 
        tentative_time: '',
        actual_date: '',
        actual_time: '',
        stage_status: 'Not Started',
        duration_hours: null
      };
    }
    this.stageDates[key].tentative_date = date;
    const time = this.stageDates[key].tentative_time || '';
    this.saveStageDate(workorderNo, dosageForm, stage, 'tentative_completion', date, time);
  }

  // Update tentative completion time
  updateTentativeTime(workorderNo: string, dosageForm: string, stage: string, time: string) {
    const key = this.getStageKey(workorderNo, dosageForm, stage);
    if (!this.stageDates[key]) {
      this.stageDates[key] = { 
        tentative_start_date: '', 
        tentative_start_time: '',
        actual_start_date: '', 
        actual_start_time: '',
        tentative_date: '', 
        tentative_time: '',
        actual_date: '',
        actual_time: '',
        stage_status: 'Not Started',
        duration_hours: null
      };
    }
    this.stageDates[key].tentative_time = time;
    const date = this.stageDates[key].tentative_date || '';
    this.saveStageDate(workorderNo, dosageForm, stage, 'tentative_completion', date, time);
  }

  // Update actual completion date
  updateActualDate(workorderNo: string, dosageForm: string, stage: string, date: string) {
    const key = this.getStageKey(workorderNo, dosageForm, stage);
    if (!this.stageDates[key]) {
      this.stageDates[key] = { 
        tentative_start_date: '', 
        tentative_start_time: '',
        actual_start_date: '', 
        actual_start_time: '',
        tentative_date: '', 
        tentative_time: '',
        actual_date: '',
        actual_time: '',
        stage_status: 'Not Started',
        duration_hours: null
      };
    }
    this.stageDates[key].actual_date = date;
    const time = this.stageDates[key].actual_time || '';
    this.saveStageDate(workorderNo, dosageForm, stage, 'actual_completion', date, time);
    
    // Auto-update status to "Completed"
    this.stageDates[key].stage_status = 'Completed';
    
    // Auto-enable next stage if exists
    this.enableNextStage(workorderNo, dosageForm, stage);
  }

  // Update actual completion time
  updateActualTime(workorderNo: string, dosageForm: string, stage: string, time: string) {
    const key = this.getStageKey(workorderNo, dosageForm, stage);
    if (!this.stageDates[key]) {
      this.stageDates[key] = { 
        tentative_start_date: '', 
        tentative_start_time: '',
        actual_start_date: '', 
        actual_start_time: '',
        tentative_date: '', 
        tentative_time: '',
        actual_date: '',
        actual_time: '',
        stage_status: 'Not Started',
        duration_hours: null
      };
    }
    this.stageDates[key].actual_time = time;
    const date = this.stageDates[key].actual_date || '';
    this.saveStageDate(workorderNo, dosageForm, stage, 'actual_completion', date, time);
  }

  // Enable next stage after current stage is completed
  enableNextStage(workorderNo: string, dosageForm: string, currentStage: string) {
    const currentIndex = this.lineStages.findIndex(s => s.stage === currentStage && s.dosage_form === dosageForm);
    if (currentIndex >= 0 && currentIndex < this.lineStages.length - 1) {
      const nextStage = this.lineStages[currentIndex + 1];
      // Next stage can now be started
      // This is handled by canSetTentativeDate validation
    }
  }

  // Update tentative start date
  updateTentativeStartDate(workorderNo: string, dosageForm: string, stage: string, date: string) {
    const key = this.getStageKey(workorderNo, dosageForm, stage);
    if (!this.stageDates[key]) {
      this.stageDates[key] = { 
        tentative_start_date: '', 
        tentative_start_time: '',
        actual_start_date: '', 
        actual_start_time: '',
        tentative_date: '', 
        tentative_time: '',
        actual_date: '',
        actual_time: '',
        stage_status: 'Not Started',
        duration_hours: null
      };
    }
    this.stageDates[key].tentative_start_date = date;
    this.saveStageDate(workorderNo, dosageForm, stage, 'tentative_start', date, '');
    
    // Recalculate subsequent stages' tentative start dates based on sequence
    this.recalculateSequentialTentativeStartDates(workorderNo, dosageForm, stage, date);
  }

  /**
   * Recalculate tentative start dates for subsequent stages based on:
   * - The manually set tentative start date for a stage
   * - The days property from API response for each stage
   * - Sequential calculation: next stage start = current stage start + current stage days
   */
  recalculateSequentialTentativeStartDates(workorderNo: string, dosageForm: string, updatedStage: string, updatedDate: string) {
    const wo = this.workOrders.find(w => w.workorder_no === workorderNo);
    if (!wo || !wo.product_code) return;

    const stagesWithDays = this.productStageDaysCache[wo.product_code];
    if (!stagesWithDays || !Array.isArray(stagesWithDays) || stagesWithDays.length === 0) return;

    // Create a map of API stages by stage_name for quick lookup
    const apiStageMap: { [key: string]: any } = {};
    stagesWithDays.forEach((apiStage: any) => {
      const stageName = (apiStage.stage_name || apiStage.stages || '').toString().trim().toLowerCase();
      apiStageMap[stageName] = apiStage;
    });

    const pad = (n: number) => (n < 10 ? '0' : '') + n;
    const toDateStr = (d: Date) =>
      `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    const toTimeStr = (d: Date) =>
      `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;

    // Find the first stage in lineStages that has a tentative start date set
    let firstStageIndex = -1;
    let firstStageStartDate: Date | null = null;
    let firstStageTime = '08:00:00';

    // Iterate through lineStages in the order they appear in the UI
    for (let i = 0; i < this.lineStages.length; i++) {
      const lineStage = this.lineStages[i];
      const key = this.getStageKey(workorderNo, lineStage.dosage_form || '', lineStage.stage || '');
      const existing = this.stageDates[key];
      
      if (existing && existing.tentative_start_date) {
        firstStageIndex = i;
        firstStageStartDate = new Date(existing.tentative_start_date + 'T00:00:00');
        firstStageTime = existing.tentative_start_time || '08:00:00';
        const [h, m, s] = firstStageTime.split(/[:\s]/).map(x => parseInt(x, 10) || 0);
        firstStageStartDate.setHours(h, m, s, 0);
        break;
      }
    }

    // If no stage has a tentative start date, use the updated date as the base
    if (firstStageIndex === -1) {
      firstStageIndex = this.lineStages.findIndex((lineStage: any) => {
        const lineStageName = (lineStage.stage || '').toString().trim();
        return lineStageName.toLowerCase() === updatedStage.toLowerCase();
      });
      if (firstStageIndex === -1) return;
      
      firstStageStartDate = new Date(updatedDate + 'T00:00:00');
      const key = this.getStageKey(workorderNo, dosageForm, updatedStage);
      const existing = this.stageDates[key];
      firstStageTime = existing?.tentative_start_time || '08:00:00';
      const [h, m, s] = firstStageTime.split(/[:\s]/).map(x => parseInt(x, 10) || 0);
      firstStageStartDate.setHours(h, m, s, 0);
    }

    // Start calculating from the first stage with a tentative start date
    let currentStartDate = new Date(firstStageStartDate!.getTime());

    // Process all lineStages starting from the first stage with tentative start
    // This ensures we follow the UI order, not the API sequence_order
    for (let i = firstStageIndex; i < this.lineStages.length; i++) {
      const lineStage = this.lineStages[i];
      const stageName = (lineStage.stage || '').toString().trim().toLowerCase();
      
      // Find matching API stage
      const apiStage = apiStageMap[stageName];
      if (!apiStage) {
        console.warn(`No API stage found for lineStage: ${lineStage.stage}`);
        // If no API stage found, skip but continue with next stage
        // Use the current start date for next iteration (no days to add)
        continue;
      }

      const key = this.getStageKey(workorderNo, lineStage.dosage_form || '', lineStage.stage || '');
      const existing = this.stageDates[key];

      // Get days from API stage data
      const days = apiStage.days ? (typeof apiStage.days === 'string' ? parseFloat(apiStage.days) : apiStage.days) : 0;

      // For the first stage (i === firstStageIndex), use the existing tentative start date
      // For subsequent stages (i > firstStageIndex), start = previous stage's completion date
      // (which is previous start + previous days)
      if (i > firstStageIndex) {
        // For subsequent stages: start = previous stage's completion date
        // currentStartDate already contains the previous stage's completion date
        // No need to add days here, as currentStartDate is already the end date from previous iteration
      }

      // Calculate completion date (start + current stage days)
      const completionDate = new Date(currentStartDate.getTime());
      completionDate.setDate(completionDate.getDate() + days);

      // Update or create stage date entry
      if (existing) {
        // Always update to ensure sequential calculation
        this.stageDates[key] = {
          ...existing,
          tentative_start_date: toDateStr(currentStartDate),
          tentative_start_time: toTimeStr(currentStartDate),
          tentative_date: toDateStr(completionDate),
          tentative_time: toTimeStr(completionDate)
        };
      } else {
        this.stageDates[key] = {
          tentative_start_date: toDateStr(currentStartDate),
          tentative_start_time: toTimeStr(currentStartDate),
          actual_start_date: '',
          actual_start_time: '',
          tentative_date: toDateStr(completionDate),
          tentative_time: toTimeStr(completionDate),
          actual_date: '',
          actual_time: '',
          stage_status: 'Not Started',
          duration_hours: null
        };
      }

      // Set next iteration's start date to current stage's completion date
      // This ensures: next stage start = current stage completion
      currentStartDate = new Date(completionDate.getTime());
    }
  }

  // Update tentative start time
  updateTentativeStartTime(workorderNo: string, dosageForm: string, stage: string, time: string) {
    const key = this.getStageKey(workorderNo, dosageForm, stage);
    if (!this.stageDates[key]) {
      this.stageDates[key] = { 
        tentative_start_date: '', 
        tentative_start_time: '',
        actual_start_date: '', 
        actual_start_time: '',
        tentative_date: '', 
        tentative_time: '',
        actual_date: '',
        actual_time: '',
        stage_status: 'Not Started',
        duration_hours: null
      };
    }
    this.stageDates[key].tentative_start_time = time;
    const date = this.stageDates[key].tentative_start_date || '';
    this.saveStageDate(workorderNo, dosageForm, stage, 'tentative_start', date, time);
  }

  // Update actual start date
  updateActualStartDate(workorderNo: string, dosageForm: string, stage: string, date: string) {
    const key = this.getStageKey(workorderNo, dosageForm, stage);
    if (!this.stageDates[key]) {
      this.stageDates[key] = { 
        tentative_start_date: '', 
        tentative_start_time: '',
        actual_start_date: '', 
        actual_start_time: '',
        tentative_date: '', 
        tentative_time: '',
        actual_date: '',
        actual_time: '',
        stage_status: 'Not Started',
        duration_hours: null
      };
    }
    this.stageDates[key].actual_start_date = date;
    const time = this.stageDates[key].actual_start_time || '';
    this.saveStageDate(workorderNo, dosageForm, stage, 'actual_start', date, time);
    
    // Auto-update status to "In Progress" if not already completed
    if (this.stageDates[key].stage_status !== 'Completed') {
      this.stageDates[key].stage_status = 'In Progress';
    }
  }

  // Update actual start time
  updateActualStartTime(workorderNo: string, dosageForm: string, stage: string, time: string) {
    const key = this.getStageKey(workorderNo, dosageForm, stage);
    if (!this.stageDates[key]) {
      this.stageDates[key] = { 
        tentative_start_date: '', 
        tentative_start_time: '',
        actual_start_date: '', 
        actual_start_time: '',
        tentative_date: '', 
        tentative_time: '',
        actual_date: '',
        actual_time: '',
        stage_status: 'Not Started',
        duration_hours: null
      };
    }
    this.stageDates[key].actual_start_time = time;
    const date = this.stageDates[key].actual_start_date || '';
    this.saveStageDate(workorderNo, dosageForm, stage, 'actual_start', date, time);
  }

  // Save stage date to database (with time support)
  saveStageDate(workorderNo: string, dosageForm: string, stage: string, dateType: string, date: string, time: string = '') {
    const data = {
      workorder_no: workorderNo,
      dosage_form: dosageForm,
      stage: stage,
      date_type: dateType, // 'tentative_start', 'actual_start', 'tentative_completion', 'actual_completion'
      date_value: date,
      time_value: time
    };
    
    this.service.post('bmr/line_booking.php?type=saveStageDate', JSON.stringify(data)).subscribe(
      (response: any) => {
        if (response.status === 'success') {
          // Update local stage status and duration if returned
          const key = this.getStageKey(workorderNo, dosageForm, stage);
          if (response.stage_status && this.stageDates[key]) {
            this.stageDates[key].stage_status = response.stage_status;
          }
          if (response.duration_hours !== undefined && this.stageDates[key]) {
            // Convert to number if it's a string
            const duration = typeof response.duration_hours === 'string' 
              ? parseFloat(response.duration_hours) 
              : response.duration_hours;
            this.stageDates[key].duration_hours = isNaN(duration) ? null : duration;
          }
        } else {
          console.error('Error saving stage date:', response.message);
          alert('Error saving stage date: ' + (response.message || 'Unknown error'));
        }
      },
      (error) => {
        console.error('Error saving stage date:', error);
        alert('Error saving stage date. Please try again.');
      }
    );
  }

  // Get stage status badge class
  getStageStatusClass(status: string): string {
    switch(status) {
      case 'Completed':
        return 'badge-success';
      case 'In Progress':
        return 'badge-warning';
      case 'On Hold':
        return 'badge-danger';
      default:
        return 'badge-secondary';
    }
  }

  // Format duration for display
  formatDuration(hours: number | null | undefined | string): string {
    // Convert to number if it's a string or handle null/undefined
    if (hours === null || hours === undefined || hours === '') return '-';
    
    const numHours = typeof hours === 'string' ? parseFloat(hours) : hours;
    
    // Check if conversion resulted in a valid number
    if (isNaN(numHours) || numHours === 0) return '-';
    
    if (numHours < 24) {
      return `${numHours.toFixed(1)} hrs`;
    }
    const days = Math.floor(numHours / 24);
    const remainingHours = numHours % 24;
    return `${days}d ${remainingHours.toFixed(1)}h`;
  }

  // Quick action: Start stage
  startStage(workorderNo: string, dosageForm: string, stage: string) {
    const today = new Date().toISOString().split('T')[0];
    const now = new Date().toTimeString().split(' ')[0].substring(0, 5);
    this.updateActualStartDate(workorderNo, dosageForm, stage, today);
    this.updateActualStartTime(workorderNo, dosageForm, stage, now);
  }

  // Quick action: Complete stage
  completeStage(workorderNo: string, dosageForm: string, stage: string) {
    const today = new Date().toISOString().split('T')[0];
    const now = new Date().toTimeString().split(' ')[0].substring(0, 5);
    this.updateActualDate(workorderNo, dosageForm, stage, today);
    this.updateActualTime(workorderNo, dosageForm, stage, now);
  }

  // Check if stage is completed (cannot edit)
  isStageCompleted(workorderNo: string, dosageForm: string, stage: string): boolean {
    const key = this.getStageKey(workorderNo, dosageForm, stage);
    const stageData = this.stageDates[key];
    return stageData?.stage_status === 'Completed';
  }

  // Open stage date/time modal
  openStageDateModal(workorderNo: string, dosageForm: string, stage: string, dateType: string, fieldLabel: string) {
    // Don't allow editing if stage is completed
    if (this.isStageCompleted(workorderNo, dosageForm, stage)) {
      alert('This stage is already completed and cannot be edited.');
      return;
    }

    const key = this.getStageKey(workorderNo, dosageForm, stage);
    const stageData = this.stageDates[key] || {
      tentative_start_date: '', tentative_start_time: '',
      actual_start_date: '', actual_start_time: '',
      tentative_date: '', tentative_time: '',
      actual_date: '', actual_time: '',
      stage_status: 'Not Started', duration_hours: null
    };

    // Get current values based on dateType
    let currentDate = '';
    let currentTime = '';
    switch(dateType) {
      case 'tentative_start':
        currentDate = stageData.tentative_start_date || '';
        currentTime = stageData.tentative_start_time || '';
        break;
      case 'actual_start':
        currentDate = stageData.actual_start_date || '';
        currentTime = stageData.actual_start_time || '';
        break;
      case 'tentative_completion':
        currentDate = stageData.tentative_date || '';
        currentTime = stageData.tentative_time || '';
        break;
      case 'actual_completion':
        currentDate = stageData.actual_date || '';
        currentTime = stageData.actual_time || '';
        break;
    }

    this.currentStageDateEdit = {
      workorder_no: workorderNo,
      dosage_form: dosageForm,
      stage: stage,
      date_type: dateType,
      field_label: fieldLabel
    };
    this.stageDateForm = {
      date: currentDate,
      time: currentTime
    };
    this.showStageDateModal = true;
  }

  // Close stage date/time modal
  closeStageDateModal() {
    this.showStageDateModal = false;
    this.currentStageDateEdit = null;
    this.stageDateForm = { date: '', time: '' };
  }

  // Save stage date/time from modal
  saveStageDateFromModal() {
    if (!this.currentStageDateEdit) return;

    const { workorder_no, dosage_form, stage, date_type } = this.currentStageDateEdit;
    const { date, time } = this.stageDateForm;

    // Update based on date_type
    switch(date_type) {
      case 'tentative_start':
        this.updateTentativeStartDate(workorder_no, dosage_form, stage, date);
        if (time) this.updateTentativeStartTime(workorder_no, dosage_form, stage, time);
        break;
      case 'actual_start':
        this.updateActualStartDate(workorder_no, dosage_form, stage, date);
        if (time) this.updateActualStartTime(workorder_no, dosage_form, stage, time);
        break;
      case 'tentative_completion':
        this.updateTentativeDate(workorder_no, dosage_form, stage, date);
        if (time) this.updateTentativeTime(workorder_no, dosage_form, stage, time);
        break;
      case 'actual_completion':
        this.updateActualDate(workorder_no, dosage_form, stage, date);
        if (time) this.updateActualTime(workorder_no, dosage_form, stage, time);
        break;
    }

    this.closeStageDateModal();
  }

  // Format date for display
  formatDateForDisplay(dateStr: string): string {
    if (!dateStr) return 'dd/mm';
    const date = new Date(dateStr + 'T00:00:00');
    const day = date.getDate().toString().padStart(2, '0');
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    return `${day}/${month}`;
  }

  // Format time for display
  formatTimeForDisplay(timeStr: string): string {
    if (!timeStr) return '--:--';
    return timeStr.substring(0, 5);
  }

  // Get work orders booked for this line (including parked entries)
  getWorkOrdersForLine(linemasterId: number, lineNo: string) {
    this.loading = true;
    const statusParam = encodeURIComponent('Booked,In Progress,Parked');
    this.service.get(`bmr/line_booking.php?type=getBookingHistory&linemaster_id=${linemasterId}&line_no=${lineNo}&status=${statusParam}`).subscribe(
      (response: any) => {
        this.workOrders = response || [];
        // Load stages from getProductStagesWithDays after work orders are loaded
        this.getStagesForLine(linemasterId);
        // Load dispensing checking data if tab is already open
        if (this.dispensingCheckingData.length > 0 || this.activeSubTabIndex[this.workOrders[0]?.workorder_no] === 0) {
          this.getDispensingCheckingData();
        }
        this.loading = false;
      },
      (error) => {
        console.error('Error fetching work orders:', error);
        alert('Error loading work orders. Please try again.');
        this.loading = false;
      }
    );
  }

  // Close line details
  closeLineDetails() {
    this.selectedLine = null;
    this.workOrders = [];
    this.activeTabIndex = 0;
    this.activeSubTabIndex = {};
    this.activeMainTab = {};
    this.lineStages = [];
    this.stageDates = {};
    this.dispensingCheckingData = [];
    
    // Reset booking data
    this.lineBookings = [];
    this.currentLineBooking = null;
    this.availableWorkOrders = [];
    this.showBookingModal = false;
    this.showUpdateDatesModal = false;
    
    // Close all modals
    this.closeModal();
    this.closeSubTabsModal();
  }
  
  // Get Dispensing Checking Data for all work orders
  getDispensingCheckingData() {
    if (this.workOrders.length === 0) {
      this.dispensingCheckingData = [];
      return;
    }
    
    this.dispensingCheckingLoading = true;
    const plantId = localStorage.getItem('plant_id') || '';
    
    if (!plantId) {
      console.error('Plant ID not found');
      alert('Plant ID not found. Please ensure you are logged in.');
      this.dispensingCheckingLoading = false;
      return;
    }
    
    // Get dispensing requests for production checking
    this.service.get(`store/dispensing.php?type=get_Dispensing_Requests_prod_checking&material_type=Raw Material`).subscribe(
      (response: any) => {
        // Filter by work orders in the current line and combine materials with work order info
        const allMaterials: any[] = [];
        
        (response || []).forEach((request: any) => {
          // Check if this request belongs to any work order in the current line
          const matchingWO = this.workOrders.find(wo => 
            wo.workorder_no === request.work_order_no || 
            wo.workorder_no === request.workorder_no ||
            request.plan_no === wo.workorder_no
          );
          
          if (matchingWO && request.materials && Array.isArray(request.materials)) {
            // Add work order, order, and product info to each material
            request.materials.forEach((material: any) => {
              allMaterials.push({
                ...material,
                workorder_no: matchingWO.workorder_no,
                order_no: matchingWO.order_no || request.order_no || '-',
                product_code: request.product_code || matchingWO.product_code || '-',
                product_name: request.product_name || matchingWO.product_name || '-',
                batch_number: request.batch_number || '-',
                plan_no: request.plan_no || '-',
                dispense_request_sent_on: request.dispense_request_sent_on || '-',
                lc_status: request.lc_status || '-',
                request_id: request.id || request.request_id || '-'
              });
            });
          }
        });
        
        this.dispensingCheckingData = allMaterials;
        this.dispensingCheckingLoading = false;
      },
      (error) => {
        console.error('Error fetching dispensing checking data:', error);
        alert('Error loading dispensing checking data. Please try again.');
        this.dispensingCheckingData = [];
        this.dispensingCheckingLoading = false;
      }
    );
  }

  // Get status badge class
  getStatusClass(status: string): string {
    if (!status) return 'badge-secondary';
    
    const statusLower = status.toLowerCase();
    
    // Completed statuses
    if (statusLower.includes('completed') || statusLower === 'completed') {
      return 'badge-success';
    }
    
    // In progress/active statuses
    if (statusLower.includes('at ') || statusLower.includes('in progress') || 
        statusLower.includes('manufacturing') || statusLower.includes('dispensing') ||
        statusLower.includes('receiving') || statusLower.includes('line booking')) {
      return 'badge-warning';
    }
    
    // Pending/waiting statuses
    if (statusLower.includes('pending') || statusLower.includes('ready for')) {
      return 'badge-info';
    }
    
    // Parked statuses
    if (statusLower.includes('parked')) {
      return 'badge-primary';
    }
    
    // Cancelled statuses
    if (statusLower.includes('cancelled') || statusLower.includes('cancel')) {
      return 'badge-danger';
    }
    
    // Legacy statuses
    switch(status) {
      case 'Booked': return 'badge-info';
      case 'In Progress': return 'badge-warning';
      case 'Parked': return 'badge-primary';
      case 'Completed': return 'badge-success';
      case 'Cancelled': return 'badge-danger';
      default: return 'badge-secondary';
    }
  }

  // QA Approved Batches Methods
  getQaApprovedBatches(workorderNo: string) {
    if (!workorderNo) return;
    
    // Find the work order to get plant_id
    const workOrder = this.workOrders.find(wo => wo.workorder_no === workorderNo);
    if (!workOrder) {
      console.error('Work order not found:', workorderNo);
      return;
    }
    
    this.qaLoading[workorderNo] = true;
    const plantId = workOrder.plant_id || (workOrder as any).plantId || localStorage.getItem('plant_id') || '';
    
    if (!plantId) {
      console.error('Plant ID not found for work order:', workorderNo);
      alert('Plant ID not found. Please ensure work order has plant_id.');
      this.qaLoading[workorderNo] = false;
      return;
    }
    
    // Get QA approved batches filtered by work order number
    this.service.get(`production/workorder.php?type=get_qa_approved_work_orders_WO_BMR&material_type=RM&plant_id=${plantId}`).subscribe(
      (response: any) => {
        // Filter by work order number
        const filtered = (response || []).filter((item: any) => 
          item.work_order_no === workorderNo || item.workorder_no === workorderNo
        );
        this.qaApprovedBatches[workorderNo] = filtered;
        this.qaLoading[workorderNo] = false;
      },
      (error) => {
        console.error('Error fetching QA approved batches:', error);
        alert('Error loading QA approved batches. Please try again.');
        this.qaApprovedBatches[workorderNo] = [];
        this.qaLoading[workorderNo] = false;
      }
    );
  }

  // Search QA Approved Batches
  searchQaApprovedBatches(workorderNo: string) {
    if (!workorderNo) return;
    
    // Find the work order to get plant_id
    const workOrder = this.workOrders.find(wo => wo.workorder_no === workorderNo);
    if (!workOrder) {
      console.error('Work order not found:', workorderNo);
      return;
    }
    
    this.qaLoading[workorderNo] = true;
    const plantId = workOrder.plant_id || (workOrder as any).plantId || localStorage.getItem('plant_id') || '';
    
    let url = `production/workorder.php?type=get_qa_approved_work_orders_from_to&material_type=RM&plant_id=${plantId}`;
    
    if (this.from_date) {
      url += `&from_date=${this.from_date}`;
    }
    if (this.to_date) {
      url += `&to_date=${this.to_date}`;
    }
    if (this.product_name) {
      url += `&product_name=${encodeURIComponent(this.product_name)}`;
    }
    
    this.service.get(url).subscribe(
      (response: any) => {
        // Filter by work order number
        const filtered = (response || []).filter((item: any) => 
          item.work_order_no === workorderNo || item.workorder_no === workorderNo
        );
        this.qaApprovedBatches[workorderNo] = filtered;
        this.qaLoading[workorderNo] = false;
      },
      (error) => {
        console.error('Error searching QA approved batches:', error);
        this.qaApprovedBatches[workorderNo] = [];
        this.qaLoading[workorderNo] = false;
      }
    );
  }

  // Send Dispensing Request
  sendDispenseRequest(workorderNo: string, index: number) {
    const batches = this.qaApprovedBatches[workorderNo];
    if (!batches || !batches[index]) return;
    
    const selectedResult = batches[index];
    this.service.get(`production/workorder.php?type=update_dispense_request&material_type=RM&id=${selectedResult['id']}`).subscribe(
      (response: any) => {
        if (response['status'] == "success") {
          alert('Work Order has been sent to Approval');
          this.getQaApprovedBatches(workorderNo);
        } else {
          alert('Failed: ' + response['status']);
        }
      },
      (error) => {
        console.error('Error sending dispensing request:', error);
        alert('Error sending dispensing request');
      }
    );
  }

  // Download QA Approved Batches
  downloadQaApprovedBatches(workorderNo: string) {
    const batches = this.qaApprovedBatches[workorderNo];
    if (!batches || batches.length === 0) {
      alert('No data to download');
      return;
    }
    
    // Open download URL in new window
    let url = `production/plan.php?type=downloadBatchPlans&work_order_no=${workorderNo}`;
    if (this.from_date) url += `&from_date=${this.from_date}`;
    if (this.to_date) url += `&to_date=${this.to_date}`;
    if (this.product_name) url += `&product_name=${encodeURIComponent(this.product_name)}`;
    
    // Use window.open for download
    window.open(url, '_blank');
  }
  
  // Helper methods for dispensing checking
  getUniqueWorkOrders(): string[] {
    const unique = new Set(this.dispensingCheckingData.map(m => m.workorder_no));
    return Array.from(unique);
  }
  
  getUniqueProducts(): string[] {
    const unique = new Set(this.dispensingCheckingData.map(m => m.product_code));
    return Array.from(unique);
  }
  
  // Filter dispensing checking data by current work order
  getFilteredDispensingCheckingData(): any[] {
    if (!this.currentWorkOrderNo) {
      return [];
    }
    return this.dispensingCheckingData.filter(m => m.workorder_no === this.currentWorkOrderNo);
  }
  
  // View material details (similar to approval component)
  viewMaterialDetails(material: any, index: number) {
    // Check if already approved
    if (material.prod_approved_by && material.prod_approved_by !== '0') {
      alert('This material has already been approved.');
      return;
    }
    
    // Store selected material for viewing
    this.selectedMaterial = material;
    this.selectedMaterialIndex = index;
    this.selectedResult = {
      product_name: material.product_name,
      batch_number: material.batch_number,
      grade: material.grade,
      batch_size: material.batch_qty,
      gross_total: 0,
      tare_total: 0,
      net_total: 0
    };
    
    // Initialize dispensing activity data (similar to approval component)
    this.available_ars_data = material.available_ars || [];
    this.available_ars = material.containers || [];
    this.fifo_method = material.fifo_method || '';
    
    // Set balance quantity
    if (material.material_code == 'RM-017') {
      this.balance_qty = 326;
    } else {
      this.balance_qty = +material.batch_qty || 0;
    }
    
    // Calculate totals from containers
    this.calculateTotals();
    
    // Open material details modal
    this.showMaterialDetailsModal = true;
    
    // Fetch additional material details if needed
    // this.getMaterialDetails(material.id);
  }
  
  // Close material details modal
  closeMaterialDetailsModal() {
    this.showMaterialDetailsModal = false;
    this.selectedMaterial = null;
    this.selectedMaterialIndex = -1;
    this.selectedResult = null;
    this.available_ars_data = [];
    this.available_ars = [];
    this.balance_qty = 0;
    this.fifo_method = '';
  }
  
  // Update dispensing status (Approve/Reject)
  updateDispensingStatus(status: string) {
    if (!this.selectedMaterial || !this.selectedMaterial.dispence_id) {
      alert('Invalid material selected.');
      return;
    }
    
    const url = `store/dispensing.php?type=update_prod_dispence_status&id=${this.selectedMaterial.dispence_id}&status=${status}`;
    
    this.service.post(url, null).subscribe(
      response => {
        if (response['status'] == 'success') {
          alert(`Dispensing Status Updated Successfully!`);
          // Refresh the dispensing checking data
          this.getDispensingCheckingData();
          // Close the modal
          this.closeMaterialDetailsModal();
        } else {
          alert('Failed: An error occurred, please try again!');
        }
      },
      error => {
        console.error('Error updating dispensing status:', error);
        alert('Error updating dispensing status');
      }
    );
  }
  
  // Calculate totals from available_ars containers
  calculateTotals() {
    if (!this.selectedResult) {
      this.selectedResult = { gross_total: 0, tare_total: 0, net_total: 0 };
    }
    
    let gross_total = 0;
    let tare_total = 0;
    let net_total = 0;
    
    if (this.available_ars && this.available_ars.length > 0) {
      this.available_ars.forEach(ar => {
        gross_total += Number(ar.gross_wt) || 0;
        tare_total += Number(ar.tare_wt) || 0;
        net_total += Number(ar.net_weight) || 0;
      });
    }
    
    this.selectedResult.gross_total = gross_total;
    this.selectedResult.tare_total = tare_total;
    this.selectedResult.net_total = net_total;
  }
  
  // Get material details from API (if needed)
  getMaterialDetails(materialId: string) {
    // Example API call - adjust based on your backend
    // this.service.get(`store/dispensing.php?type=getMaterialDetails&id=${materialId}`).subscribe(
    //   response => {
    //     this.selectedMaterial = { ...this.selectedMaterial, ...response };
    //     this.available_ars_data = response.available_ars || [];
    //     this.available_ars = response.containers || [];
    //     this.calculateTotals();
    //   }
    // );
  }
  
  // ========== RECEIVED DISPENSING METHODS (from receiving component) ==========
  
  // Load received dispensing data for current work order
  loadReceivedDispensingData() {
    if (!this.currentWorkOrderNo) {
      this.receivedDispensingData = [];
      return;
    }
    
    this.receivedDispensingLoading = true;
    const url = `production/workorder.php?type=get_dispensing_complted_requests_by_store&material_type=Raw Material&workorder_no=${this.currentWorkOrderNo}`;
    
    this.service.get(url).subscribe(
      response => {
        // Ensure response is an array
        let data: any[] = Array.isArray(response) ? response : [];
        
        // Sort by work order number
        data = this.sortByWorkOrderNo(data);
        
        // Filter by current work order
        data = data.filter(item => item.workorder_no === this.currentWorkOrderNo || item.work_order_no === this.currentWorkOrderNo);
        
        this.receivedDispensingData = data;
        this.receivedDispensingLoading = false;
        
        // If we have a selected index, restore the view
        if (this.selectedReceivedIndex !== -1 && this.receivedDispensingData.length > 0) {
          this.viewReceivedDispensing(this.selectedReceivedIndex);
        }
      },
      error => {
        console.error('Error loading received dispensing data:', error);
        this.receivedDispensingData = [];
        this.receivedDispensingLoading = false;
      }
    );
  }
  
  // Sort data by work order number (and then by date)
  sortByWorkOrderNo(data: any[]): any[] {
    return data.sort((a, b) => {
      // First sort by work order number
      const woA = (a.workorder_no || a.work_order_no || '').toString();
      const woB = (b.workorder_no || b.work_order_no || '').toString();
      const woCompare = woA.localeCompare(woB, undefined, { numeric: true, sensitivity: 'base' });
      
      if (woCompare !== 0) {
        return woCompare;
      }
      
      // If work order numbers are the same, sort by date (newest first)
      const dateA = new Date(a.dispense_request_sent_on || a.date || 0).getTime();
      const dateB = new Date(b.dispense_request_sent_on || b.date || 0).getTime();
      return dateB - dateA; // Descending order (newest first)
    });
  }
  
  // View received dispensing details
  viewReceivedDispensing(index: number) {
    if (!Array.isArray(this.receivedDispensingData) || !this.receivedDispensingData || index >= this.receivedDispensingData.length) {
      return;
    }
    
    this.selectedReceivedIndex = index;
    this.selectedReceivedResult = this.receivedDispensingData[index];
    this.isReceivedDispensingCompleted = this.selectedReceivedResult['rm_received_by'] && this.selectedReceivedResult['rm_received_by'].length > 0;
    
    let material = this.selectedReceivedResult['materials'] || [];
    let dispensingCompleted = 'Yes';
    
    for (let i = 0; i < material.length; i++) {
      if (!material[i]['checked_by'] || material[i]['checked_by'].length == 0) {
        dispensingCompleted = 'No';
        break;
      }
    }
    
    this.receivedDispensingCompleted = dispensingCompleted;
    this.isReceivedView = true;
  }
  
  // View received dispensing activity
  viewReceivedDispensingActivity(index: number) {
    if (!this.selectedReceivedResult || !this.selectedReceivedResult['materials']) {
      return;
    }
    
    let material = this.selectedReceivedResult['materials'];
    this.selectedMaterial = material[index];
    
    // Fetch dispensing activity details
    const url = `store/dispensing.php?type=get_dispensing_Activity_By_Id&id=${this.selectedMaterial["dispence_id"]}`;
    this.service.get(url).subscribe(
      response => {
        this.available_ars_data = response['ars'] || [];
        this.available_ars = response['containers'] || [];
        this.isReceivedStart = false;
        this.isReceivedView = false;
        this.isReceivedViewDispensing = true;
      },
      error => {
        console.error('Error loading dispensing activity:', error);
      }
    );
  }
  
  // Update receiving status for a product
  updateReceivedReceivingStatusForProduct(id: string, status: string) {
    const url = `production/dispensing.php?type=receive_material&id=${id}&status=${status}`;
    
    this.service.get(url).subscribe(
      response => {
        if (response['status'] == 'success') {
          alert('Dispensing Checking Status Updated Successfully!');
          this.isReceivedDispensingCompleted = true;
          this.isReceivedView = false;
          this.loadReceivedDispensingData();
        } else {
          alert('Failed: An error occurred, please try again!');
        }
      },
      error => {
        console.error('Error updating receiving status:', error);
        alert('Error updating receiving status');
      }
    );
  }
  
  // Update receiving status
  updateReceivedReceivingStatus(status: string) {
    if (!this.selectedReceivedResult) {
      return;
    }
    
    let obj = {
      "remarks": this.receivedRemarks,
      "rm_status": status
    };
    
    const url = `production/dispensing.php?type=update_material_receiving_status&id=${this.selectedReceivedResult['id']}`;
    
    this.service.post(url, JSON.stringify(obj)).subscribe(
      response => {
        if (response['status'] == 'success') {
          alert('Dispensing Checking Status Updated Successfully!');
          this.isReceivedDispensingCompleted = true;
          this.isReceivedView = false;
          this.loadReceivedDispensingData();
        } else {
          alert('Failed: An error occurred, please try again!');
        }
      },
      error => {
        console.error('Error updating receiving status:', error);
        alert('Error updating receiving status');
      }
    );
  }
  
  // Close received dispensing view
  closeReceivedDispensingView() {
    this.isReceivedView = false;
    this.isReceivedStart = false;
    this.isReceivedViewDispensing = false;
    this.selectedReceivedResult = null;
    this.selectedReceivedIndex = -1;
    this.receivedRemarks = '';
  }
  
  // ========== DISPENSING LOG METHODS (from log component) ==========
  
  // Load dispensing log data for current work order
  loadDispensingLogData() {
    if (!this.currentWorkOrderNo) {
      this.dispensingLogData = [];
      return;
    }
    
    this.dispensingLogLoading = true;
    const url = `store/dispensing.php?type=get_Dispensing_Requests_For_Inprocess_Activity_Formulation&material_type=Raw Material&rpt_type=Request&workorder_no=${this.currentWorkOrderNo}`;
    
    this.service.get(url).subscribe(
      response => {
        // Ensure response is an array
        let data: any[] = Array.isArray(response) ? response : [];
        
        // Filter by current work order
        data = data.filter(item => {
          const woNo = item.workorder_no || item.work_order_no || '';
          return woNo === this.currentWorkOrderNo;
        });
        
        // Sort by work order number and date
        data = this.sortByWorkOrderNo(data);
        
        this.dispensingLogData = data;
        this.dispensingLogLoading = false;
        
        // If we have a selected index, restore the view
        if (this.selectedLogIndex !== -1 && this.dispensingLogData.length > 0) {
          this.viewDispensingLog(this.selectedLogIndex);
        }
      },
      error => {
        console.error('Error loading dispensing log data:', error);
        this.dispensingLogData = [];
        this.dispensingLogLoading = false;
      }
    );
  }
  
  // View dispensing log details
  viewDispensingLog(index: number) {
    if (!Array.isArray(this.dispensingLogData) || !this.dispensingLogData || index >= this.dispensingLogData.length) {
      return;
    }
    
    this.selectedLogIndex = index;
    this.selectedLogResult = this.dispensingLogData[index];
    
    // Prepare materials data (similar to log component)
    if (this.selectedLogResult['materials']) {
      this.selectedLogResult['materials'] = this.prepareLogMaterialsData(this.selectedLogResult['materials']);
    }
    
    this.isLogDispensingCompleted = this.selectedLogResult['rm_disp_completed_by'] && this.selectedLogResult['rm_disp_completed_by'].length > 0;
    
    let material = this.selectedLogResult['materials'] || [];
    
    // Process materials to determine dispense status
    for (let i = 0; i < material.length; i++) {
      if ((material[i]['qa_checking'] == 'Yes' && material[i]['qa_status'] == 'Approve') && 
          (material[i]['prod_checking'] == 'Yes' && material[i]['prod_status'] == 'Approve')) {
        material[i]['dispense_status'] = 'Done';
      } else if (material[i]['qa_checking'] == 'Yes' && 
                 (material[i]['qa_status'] == 'Approve' && material[i]['prod_checking'] == 'No')) {
        material[i]['dispense_status'] = 'Done';
        material[i]['prod_status'] = 'N/A';
      } else if (material[i]['prod_checking'] == 'Yes' && 
                 (material[i]['prod_status'] == 'Approve' && material[i]['qa_checking'] == 'No')) {
        material[i]['dispense_status'] = 'Done';
        material[i]['qa_status'] = 'N/A';
      }
    }
    
    let dispensingCompleted = 'Yes';
    for (let i = 0; i < material.length; i++) {
      if (material[i]['dispense_status'] != 'Done') {
        dispensingCompleted = 'No';
        break;
      }
    }
    
    this.logDispensingCompleted = dispensingCompleted;
    this.isLogView = true;
  }
  
  // Prepare materials data with serial numbers (similar to log component)
  prepareLogMaterialsData(materials: any[]): any[] {
    const result = [];
    let serialNumber = 0;
    let previousLotNo = '';
  
    for (const comp of materials) {
      if (comp.lot_no !== previousLotNo) {
        serialNumber++;
      }
  
      const processedComp = {
        ...comp,
        serialNumber: serialNumber,
      };
  
      result.push(processedComp);
      previousLotNo = comp.lot_no;
    }
  
    return result;
  }
  
  // View dispensing log activity
  viewDispensingLogActivity(index: number) {
    if (!this.selectedLogResult || !this.selectedLogResult['materials']) {
      return;
    }
    
    let material = this.selectedLogResult['materials'];
    this.selectedMaterial = material[index];
    
    // Fetch dispensing activity details
    const url = `store/dispensing.php?type=get_dispensing_Activity_By_Id&id=${this.selectedMaterial["dispence_id"]}`;
    this.service.get(url).subscribe(
      response => {
        this.available_ars_data = response['ars'] || [];
        this.available_ars = response['containers'] || [];
        this.isLogStart = false;
        this.isLogView = false;
        this.isLogViewDispensing = true;
      },
      error => {
        console.error('Error loading dispensing activity:', error);
      }
    );
  }
  
  // Close dispensing log view
  closeDispensingLogView() {
    this.isLogView = false;
    this.isLogStart = false;
    this.isLogViewDispensing = false;
    this.selectedLogResult = null;
    this.selectedLogIndex = -1;
  }
  
  // ========== MANPOWER ALLOCATION METHODS ==========
  
  // Load manpower allocation data for current work order
  loadManpowerAllocationData() {
    if (!this.currentWorkOrderNo) {
      this.manpowerAllocationData = [];
      return;
    }
    
    this.manpowerAllocationLoading = true;
    const plantId = this.service.getPlantConfigFields ? this.service.getPlantConfigFields('plant_id') : localStorage.getItem('plant_id') || '181';
    const url = `production/manpower.php?type=getManpowerAllocation&work_order_no=${this.currentWorkOrderNo}&plant_id=${plantId}`;
    
    this.service.get(url).subscribe(
      response => {
        let data: any[] = Array.isArray(response) ? response : [];
        this.manpowerAllocationData = data;
        this.manpowerAllocationLoading = false;
      },
      error => {
        console.error('Error loading manpower allocation data:', error);
        this.manpowerAllocationData = [];
        this.manpowerAllocationLoading = false;
      }
    );
  }
  
  // Load available employees for allocation
  loadAvailableEmployees() {
    const url = `hr/employee.php?type=getEmployeesbydept&department_name=Production`;
    
    this.service.get(url).subscribe(
      response => {
        this.availableEmployees = Array.isArray(response) ? response : [];
        // Ensure employee names are formatted correctly
        this.availableEmployees = this.availableEmployees.map(emp => {
          if (!emp.emp_name && (emp.firstname || emp.lastname)) {
            emp.emp_name = `${emp.firstname || ''} ${emp.lastname || ''}`.trim();
          }
          return emp;
        });
      },
      error => {
        console.error('Error loading employees:', error);
        this.availableEmployees = [];
      }
    );
  }
  
  // Handle employee selection
  onEmployeeSelect(empId: string) {
    const selectedEmp = this.availableEmployees.find(emp => emp.emp_id === empId);
    if (selectedEmp) {
      this.selectedEmployeeId = empId;
      // Handle both emp_name and firstname/lastname formats
      if (selectedEmp.emp_name) {
        this.selectedEmployeeName = selectedEmp.emp_name;
      } else if (selectedEmp.firstname || selectedEmp.lastname) {
        this.selectedEmployeeName = `${selectedEmp.firstname || ''} ${selectedEmp.lastname || ''}`.trim();
      } else {
        this.selectedEmployeeName = '';
      }
    }
  }
  
  // Add manpower allocation
  addManpowerAllocation() {
    if (!this.selectedEmployeeId || !this.employeeRole) {
      alert('Please select an employee and specify their role.');
      return;
    }
    
    if (!this.currentWorkOrderNo) {
      alert('Work order number is missing.');
      return;
    }
    
    // Get full employee name from selected employee
    const selectedEmp = this.availableEmployees.find(emp => emp.emp_id === this.selectedEmployeeId);
    let fullEmpName = this.selectedEmployeeName;
    if (selectedEmp) {
      if (selectedEmp.emp_name) {
        fullEmpName = selectedEmp.emp_name;
      } else if (selectedEmp.firstname || selectedEmp.lastname) {
        fullEmpName = `${selectedEmp.firstname || ''} ${selectedEmp.lastname || ''}`.trim();
      }
    }
    
    const allocationData = {
      work_order_no: this.currentWorkOrderNo,
      emp_id: this.selectedEmployeeId,
      emp_name: fullEmpName,
      role: this.employeeRole,
      remarks: this.allocationRemarks || ''
    };
    
    const plantId = this.service.getPlantConfigFields ? this.service.getPlantConfigFields('plant_id') : localStorage.getItem('plant_id') || '181';
    const url = `production/manpower.php?type=addManpowerAllocation&emp_id=${localStorage.getItem('emp_id')}&plant_id=${plantId}`;
    
    this.service.post(url, JSON.stringify(allocationData)).subscribe(
      response => {
        if (response['status'] === 'success') {
          alert('Manpower allocated successfully!');
          // Reset form
          this.selectedEmployeeId = '';
          this.selectedEmployeeName = '';
          this.employeeRole = '';
          this.allocationRemarks = '';
          // Reload data
          this.loadManpowerAllocationData();
        } else {
          alert('Failed: ' + (response['message'] || 'An error occurred, please try again!'));
        }
      },
      error => {
        console.error('Error adding manpower allocation:', error);
        alert('Error adding manpower allocation');
      }
    );
  }
  
  // Remove manpower allocation
  removeManpowerAllocation(id: number) {
    if (!confirm('Are you sure you want to remove this manpower allocation?')) {
      return;
    }
    
    const plantId = this.service.getPlantConfigFields ? this.service.getPlantConfigFields('plant_id') : localStorage.getItem('plant_id') || '181';
    const url = `production/manpower.php?type=removeManpowerAllocation&id=${id}&emp_id=${localStorage.getItem('emp_id')}&plant_id=${plantId}`;
    
    this.service.get(url).subscribe(
      response => {
        if (response['status'] === 'success') {
          alert('Manpower allocation removed successfully!');
          this.loadManpowerAllocationData();
        } else {
          alert('Failed: ' + (response['message'] || 'An error occurred, please try again!'));
        }
      },
      error => {
        console.error('Error removing manpower allocation:', error);
        alert('Error removing manpower allocation');
      }
    );
  }

  // ============================================
  // BOOKING TAB METHODS
  // ============================================

  // Load available work orders for booking
  loadAvailableWorkOrders() {
    this.bookingLoading = true;
    this.service.get('bmr/line_booking.php?type=getVerifiedWorkOrders').subscribe(
      (response: any) => {
        if (this.selectedLine) {
          // Filter out work orders already booked to this specific line
          this.availableWorkOrders = (response || []).filter((wo: any) => {
            const isBookedToThisLine = wo.bookedLines?.some((line: any) => 
              line.linemaster_id === this.selectedLine.id && 
              line.status !== 'Cancelled' && 
              line.status !== 'Completed'
            );
            return !isBookedToThisLine;
          });
        } else {
          // From home screen - filter out work orders with active bookings
          this.availableWorkOrders = (response || []).filter((wo: any) => {
            // Exclude work orders that have active bookings (not Completed or Cancelled)
            if (wo.bookedLines && wo.bookedLines.length > 0) {
              const hasActiveBooking = wo.bookedLines.some((line: any) => 
                line.status !== 'Completed' && line.status !== 'Cancelled'
              );
              return !hasActiveBooking; // Only include if no active bookings
            }
            return true; // Include if no bookings at all
          });
        }
        this.bookingLoading = false;
      },
      (error) => {
        console.error('Error loading available work orders:', error);
        this.bookingLoading = false;
      }
    );
  }

  // Load current bookings for selected line
  loadLineBookings() {
    if (!this.selectedLine) return;
    
    const statusParam = encodeURIComponent('Booked,In Progress,Parked,Completed');
    this.service.get(
      `bmr/line_booking.php?type=getBookingHistory&linemaster_id=${this.selectedLine.id}&line_no=${this.selectedLine.line_no}&status=${statusParam}`
    ).subscribe(
      (response: any) => {
        this.lineBookings = response || [];
        
        // Get currently active booking
        // Priority: In Progress > Booked (with actual_start_date) > Booked
        this.currentLineBooking = this.lineBookings.find((b: any) => 
          b.status === 'In Progress'
        ) || this.lineBookings.find((b: any) => 
          b.status === 'Booked' && b.actual_start_date
        ) || this.lineBookings.find((b: any) => 
          b.status === 'Booked'
        ) || null;
        
        // If booking has actual_end_date but status is not Completed, mark as completed
        // (This handles cases where trigger didn't fire)
        const completedBooking = this.lineBookings.find((b: any) => 
          b.actual_end_date && b.status !== 'Completed' && b.status !== 'Cancelled'
        );
        if (completedBooking) {
          this.currentLineBooking = null;
        }
      },
      (error) => {
        console.error('Error loading line bookings:', error);
      }
    );
  }

  // Check if line is available
  isLineAvailable(): boolean {
    if (!this.currentLineBooking) return true;
    
    // Line is available only if:
    // 1. Status is Completed or Cancelled, OR
    // 2. Actual end date is set (even if status not updated yet)
    return this.currentLineBooking.status === 'Completed' || 
           this.currentLineBooking.status === 'Cancelled' ||
           (this.currentLineBooking.actual_end_date !== null && 
            this.currentLineBooking.actual_end_date !== '');
  }

  // Check if work order has active bookings
  hasActiveBooking(wo: any): boolean {
    if (!wo || !wo.bookedLines || wo.bookedLines.length === 0) {
      return false;
    }
    // Check if any booking is active (not Completed or Cancelled)
    return wo.bookedLines.some((line: any) => 
      line.status !== 'Completed' && line.status !== 'Cancelled'
    );
  }

  // Open booking modal
  openBookingModal(wo: any) {
    // If no line is selected (booking from home screen), show line selection first
    if (!this.selectedLine) {
      // Get available lines for this work order
      this.selectedWOForBooking = wo;
      this.loadAvailableLinesForWO(wo);
      this.showLineSelectionModal = true;
      return;
    }
    
    if (!this.isLineAvailable()) {
      alert('Line is currently booked. Please wait for current booking to complete.');
      return;
    }
    
    this.selectedWOForBooking = wo;
    this.bookingFormData = {
      workorder_no: wo.workorder_no,
      linemaster_id: this.selectedLine.id,
      line_no: this.selectedLine.line_no,
      product_code: wo.product_code || '',
      product_name: wo.product_name || '',
      booking_start_date: '',
      booking_start_time: '08:00:00',
      booking_end_date: '',
      booking_end_time: '17:00:00',
      responsible_person: '',
      selected_equipments: this.selectedLine.equipmentList || [],
      capacity_required: wo.batch_size || '',
      remarks: ''
    };
    this.availabilityCheckResult = null;
    this.bookingHoursExceedMax = false;
    this.maxWorkingHours = this.getMaxWorkingHours(this.selectedLine.id);
    this.showBookingModal = true;
  }

  // Select line for booking (from home screen)
  selectLineForBookingModal(line: any) {
    this.showLineSelectionModal = false;
    // Now open booking modal with selected line (don't set selectedLine to stay in booking view)
    if (this.selectedWOForBooking) {
      this.bookingFormData = {
        workorder_no: this.selectedWOForBooking.workorder_no,
        linemaster_id: line.id,
        line_no: line.line_no,
        product_code: this.selectedWOForBooking.product_code || '',
        product_name: this.selectedWOForBooking.product_name || '',
        booking_start_date: '',
        booking_start_time: '08:00:00',
        booking_end_date: '',
        booking_end_time: '17:00:00',
        responsible_person: '',
        selected_equipments: line.equipmentList || [],
        capacity_required: this.selectedWOForBooking.batch_size || '',
        remarks: ''
      };
      this.availabilityCheckResult = null;
      this.bookingHoursExceedMax = false;
      this.maxWorkingHours = this.getMaxWorkingHours(line.id);
      this.showBookingModal = true;
    }
  }

  // Close booking modal
  closeBookingModal() {
    this.showBookingModal = false;
    this.selectedWOForBooking = null;
    this.availabilityCheckResult = null;
    this.bookingHoursExceedMax = false;
    this.maxWorkingHours = 0;
  }

  // Check line availability
  checkAvailability() {
    if (!this.bookingFormData.booking_start_date || !this.bookingFormData.booking_end_date) {
      alert('Please select booking dates');
      return;
    }
    
    if (!this.bookingFormData.linemaster_id) {
      alert('Please select a line first');
      return;
    }
    
    // Check MaxWorkingHours validation
    const bookedHours = this.calculateBookingFormHours();
    const maxWorkingHours = this.getMaxWorkingHours(this.bookingFormData.linemaster_id);
    
    if (maxWorkingHours > 0 && bookedHours > maxWorkingHours) {
      alert(`⚠️ Booking hours (${bookedHours} hrs) exceed the maximum working hours (${maxWorkingHours} hrs) for this line.\n\nPlease adjust the tentative start and end times.`);
      return;
    }
    
    this.service.get(
      `bmr/line_booking.php?type=checkLineAvailability&linemaster_id=${this.bookingFormData.linemaster_id}&start_date=${this.bookingFormData.booking_start_date}&start_time=${this.bookingFormData.booking_start_time}&end_date=${this.bookingFormData.booking_end_date}&end_time=${this.bookingFormData.booking_end_time}`
    ).subscribe(
      (response: any) => {
        this.availabilityCheckResult = response;
        if (!response.isAvailable) {
          // Build detailed conflict message in the exact format requested
          let conflictMessage = '⚠️ Line is not available for the selected dates.\n\n';
          
          if (response.conflicts && response.conflicts.length > 0) {
            conflictMessage += 'Conflicting Bookings:\n';
            conflictMessage += '─────────────────────────────\n\n';
            
            response.conflicts.forEach((conflict: any, index: number) => {
              conflictMessage += `${index + 1}. Work Order: ${conflict.workorder_no || 'N/A'}\n`;
              conflictMessage += `   Status: ${conflict.status || 'N/A'}\n`;
              conflictMessage += `   Booked From: ${conflict.start || 'N/A'}\n`;
              conflictMessage += `   Booked Until: ${conflict.end || 'N/A'}\n`;
              if (index < response.conflicts.length - 1) {
                conflictMessage += '\n';
              }
            });
            
            conflictMessage += '\n─────────────────────────────\n';
            conflictMessage += 'Please select different dates or wait for the current booking to complete.';
          } else {
            conflictMessage += 'The line has an existing booking during this time period.';
          }
          
          alert(conflictMessage);
        } else {
          // Show booking hours info if available
          if (maxWorkingHours > 0) {
            alert(`✅ Line is available for the selected dates!\n\nBooking Duration: ${bookedHours} hrs\nMaximum Working Hours: ${maxWorkingHours} hrs`);
          } else {
            alert('✅ Line is available for the selected dates!');
          }
        }
      },
      (error) => {
        console.error('Error checking availability:', error);
        alert('Error checking line availability');
      }
    );
  }

  // Confirm booking
  confirmBooking() {
    if (!this.availabilityCheckResult?.isAvailable) {
      // Show detailed conflict message if trying to book without checking
      let conflictMessage = '⚠️ Line is not available for booking.\n\n';
      
      if (this.availabilityCheckResult?.conflicts && this.availabilityCheckResult.conflicts.length > 0) {
        conflictMessage += 'Conflicting Bookings:\n';
        conflictMessage += '─────────────────────────────\n\n';
        
        this.availabilityCheckResult.conflicts.forEach((conflict: any, index: number) => {
          conflictMessage += `${index + 1}. Work Order: ${conflict.workorder_no || 'N/A'}\n`;
          conflictMessage += `   Status: ${conflict.status || 'N/A'}\n`;
          conflictMessage += `   Booked From: ${conflict.start || 'N/A'}\n`;
          conflictMessage += `   Booked Until: ${conflict.end || 'N/A'}\n`;
          if (index < this.availabilityCheckResult.conflicts.length - 1) {
            conflictMessage += '\n';
          }
        });
        
        conflictMessage += '\n─────────────────────────────\n';
        conflictMessage += 'Please check availability first and select different dates.';
      } else {
        conflictMessage += 'Please check availability first before confirming the booking.';
      }
      
      alert(conflictMessage);
      return;
    }
    
    // Validate MaxWorkingHours before confirming
    const bookedHours = this.calculateBookingFormHours();
    const maxWorkingHours = this.getMaxWorkingHours(this.bookingFormData.linemaster_id);
    
    if (maxWorkingHours > 0 && bookedHours > maxWorkingHours) {
      alert(`⚠️ Cannot confirm booking!\n\nBooking hours (${bookedHours} hrs) exceed the maximum working hours (${maxWorkingHours} hrs) for this line.\n\nPlease adjust the tentative start and end times.`);
      return;
    }
    
    this.service.post(
      'bmr/line_booking.php?type=bookLine',
      JSON.stringify(this.bookingFormData)
    ).subscribe(
      (response: any) => {
        if (response.status === 'success') {
          alert('Line booked successfully!');
          if (response.warning) {
            alert(response.warning);
          }
          this.closeBookingModal();
          
          // Reload data based on context
          if (this.selectedLine) {
            // Booking from line details view
            this.loadLineBookings();
            this.getWorkOrdersForLine(this.selectedLine.id, this.selectedLine.line_no);
            this.loadAvailableWorkOrders();
          } else {
            // Booking from home screen
            this.loadAllLineBookings();
            this.loadAvailableWorkOrders();
          }
        } else {
          alert(response.message || 'Failed to book line');
        }
      },
      (error) => {
        console.error('Error booking line:', error);
        alert('Error booking line');
      }
    );
  }

  // Cancel booking
  cancelBooking(bookingId: number) {
    if (!confirm('Are you sure you want to cancel this booking?')) {
      return;
    }
    
    const cancelData = {
      booking_id: bookingId,
      cancellation_reason: 'Cancelled by user'
    };
    
    this.service.post(
      'bmr/line_booking.php?type=cancelBooking',
      JSON.stringify(cancelData)
    ).subscribe(
      (response: any) => {
        if (response.status === 'success') {
          alert('Booking cancelled successfully');
          this.loadLineBookings();
          this.getWorkOrdersForLine(this.selectedLine.id, this.selectedLine.line_no);
          this.loadAvailableWorkOrders();
        } else {
          alert(response.message || 'Failed to cancel booking');
        }
      },
      (error) => {
        console.error('Error cancelling booking:', error);
        alert('Error cancelling booking');
      }
    );
  }

  // Get booking status badge class
  getBookingStatusClass(status: string): string {
    const statusMap: { [key: string]: string } = {
      'Booked': 'badge-info',
      'In Progress': 'badge-warning',
      'Parked': 'badge-secondary',
      'Completed': 'badge-success',
      'Cancelled': 'badge-danger'
    };
    return statusMap[status] || 'badge-secondary';
  }

  // Open Update Dates Modal
  openUpdateDatesModal(booking: any) {
    this.selectedBookingForUpdate = booking;
    this.actualDatesForm = {
      booking_id: booking.id,
      actual_start_date: booking.actual_start_date || '',
      actual_start_time: booking.actual_start_time || '',
      actual_end_date: booking.actual_end_date || '',
      actual_end_time: booking.actual_end_time || ''
    };
    this.showUpdateDatesModal = true;
  }

  // Close Update Dates Modal
  closeUpdateDatesModal() {
    this.showUpdateDatesModal = false;
    this.selectedBookingForUpdate = null;
    this.actualDatesForm = {
      booking_id: 0,
      actual_start_date: '',
      actual_start_time: '',
      actual_end_date: '',
      actual_end_time: ''
    };
  }

  // Save Actual Dates
  saveActualDates() {
    if (!this.actualDatesForm.actual_start_date) {
      alert('Actual start date is required');
      return;
    }
    
    // Warn if setting end date (will complete booking)
    if (this.actualDatesForm.actual_end_date && 
        this.selectedBookingForUpdate?.status !== 'Completed') {
      if (!confirm('Setting actual end date will mark this booking as Completed and release the line. Continue?')) {
        return;
      }
    }
    
    this.service.post(
      'bmr/line_booking.php?type=updateActualDates',
      JSON.stringify(this.actualDatesForm)
    ).subscribe(
      (response: any) => {
        if (response.status === 'success') {
          alert(response.message);
          this.closeUpdateDatesModal();
          
          // Reload bookings
          this.loadLineBookings();
          this.getWorkOrdersForLine(this.selectedLine.id, this.selectedLine.line_no);
          this.loadAvailableWorkOrders();
        } else {
          alert(response.message || 'Failed to update actual dates');
        }
      },
      (error) => {
        console.error('Error updating actual dates:', error);
        alert('Error updating actual dates');
      }
    );
  }

  // Start Production - Set actual start date to current date/time
  startProduction(booking: any) {
    if (!confirm(`Start production for work order ${booking.workorder_no}? This will set the actual start date to now.`)) {
      return;
    }
    
    const now = new Date();
    const currentDate = now.toISOString().split('T')[0];
    const currentTime = now.toTimeString().split(' ')[0].substring(0, 5); // HH:MM format
    
    const updateData = {
      booking_id: booking.id,
      actual_start_date: currentDate,
      actual_start_time: currentTime
    };
    
    this.service.post(
      'bmr/line_booking.php?type=updateActualDates',
      JSON.stringify(updateData)
    ).subscribe(
      (response: any) => {
        if (response.status === 'success') {
          alert(`Production started successfully!\nActual Start: ${currentDate} ${currentTime}\nStatus changed to: In Progress`);
          
          // Reload bookings
          this.loadLineBookings();
          this.getWorkOrdersForLine(this.selectedLine.id, this.selectedLine.line_no);
          this.loadAvailableWorkOrders();
        } else {
          alert(response.message || 'Failed to start production');
        }
      },
      (error) => {
        console.error('Error starting production:', error);
        alert('Error starting production');
      }
    );
  }

  // End Production - Set actual end date to current date/time
  endProduction(booking: any) {
    if (!confirm(`End production for work order ${booking.workorder_no}? This will set the actual end date to now and mark the booking as Completed. The line will be released for new bookings.`)) {
      return;
    }
    
    const now = new Date();
    const currentDate = now.toISOString().split('T')[0];
    const currentTime = now.toTimeString().split(' ')[0].substring(0, 5); // HH:MM format
    
    const updateData: any = {
      booking_id: booking.id,
      actual_end_date: currentDate,
      actual_end_time: currentTime
    };
    
    // If actual_start_date is not set, set it as well
    if (!booking.actual_start_date) {
      updateData.actual_start_date = currentDate;
      updateData.actual_start_time = currentTime;
    }
    
    this.service.post(
      'bmr/line_booking.php?type=updateActualDates',
      JSON.stringify(updateData)
    ).subscribe(
      (response: any) => {
        if (response.status === 'success') {
          alert(`Production ended successfully!\nActual End: ${currentDate} ${currentTime}\nStatus changed to: Completed\nLine is now available for new bookings.`);
          
          // Reload bookings
          this.loadLineBookings();
          this.getWorkOrdersForLine(this.selectedLine.id, this.selectedLine.line_no);
          this.loadAvailableWorkOrders();
        } else {
          alert(response.message || 'Failed to end production');
        }
      },
      (error) => {
        console.error('Error ending production:', error);
        alert('Error ending production');
      }
    );
  }
}

