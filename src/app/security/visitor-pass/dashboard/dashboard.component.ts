import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
import * as XLSX from 'xlsx';
declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers: [DatePipe]
})
export class DashboardComponent implements OnInit {

  from_date = '';
  to_date = '';
  /** Calendar month value 'yyyy-MM' for input type="month" */
  selectedMonthYear = '';

  constructor(private service: DataAccessService, private datepipe: DatePipe) {
    const now = new Date();
    this.selectedMonthYear = this.datepipe.transform(now, 'yyyy-MM') || '';
    this.setDateRange();
  }

  ngOnInit() {
    this.setDateRange();
    this.getGatepassDetails();
    this.get_rights();
    // Load departments for assign meeting modal
    this.service.observableDepartment.subscribe({
      next: (response: any) => {
        this.departments = Array.isArray(response) ? response : [];
      },
      error: () => {
        this.departments = [];
      }
    });
  }

  onMonthYearChange() {
    this.setDateRange();
    this.getGatepassDetails();
  }

  setDateRange() {
    if (!this.selectedMonthYear || this.selectedMonthYear.length < 7) {
      const now = new Date();
      this.selectedMonthYear = this.datepipe.transform(now, 'yyyy-MM') || '';
    }
    const [y, m] = this.selectedMonthYear.split('-').map(Number);
    this.from_date = this.datepipe.transform(new Date(y, m - 1, 1), 'yyyy-MM-dd') || '';
    this.to_date = this.datepipe.transform(new Date(y, m, 0), 'yyyy-MM-dd') || '';
  }

  /** Department from localStorage (sent as deptName to backend) */
  get deptName(): string {
    return (typeof localStorage !== 'undefined' && localStorage.getItem('department')) || '';
  }

  results: any[] = [];
  assignMeetingModalOpen = false;
  selectedVisitor: any = null;
  employees: any[] = [];
  selectedMeetingWith = '';
  selectedDepartment = '';
  departments: any[] = [];
  loading = false;
  plantDetailsModalOpen = false;
  plantDetails: { saftyInstruction?: string; GMPInstruction?: string; visitVideoLink?: string } = {};
  plantDetailsLoading = false;
  private processingInIds = new Set<string>();
  private processingOutIds = new Set<string>();

  getCurrentSystemTime(): string {
    return this.datepipe.transform(new Date(), 'yyyy-MM-dd HH:mm:ss') || '';
  }

  isProcessingIn(id: string | number): boolean {
    return this.processingInIds.has(String(id));
  }

  isProcessingOut(id: string | number): boolean {
    return this.processingOutIds.has(String(id));
  }

  private updateVisitorTime(visitor: any, field: 'in_time' | 'out_time', value: string, status: string): void {
    const id = String(visitor.id);
    const row = this.results.find((r) => String(r.id) === id);
    if (row) {
      row[field] = value;
      row.status = status;
    }
    visitor[field] = value;
    visitor.status = status;
  }

  getGatepassDetails() {
    const plantId = localStorage.getItem('plant_id') || '1';
    const params = 'type=getGatepassDetailsForSecurityLog&from_date=' + encodeURIComponent(this.from_date) + '&to_date=' + encodeURIComponent(this.to_date) + '&plant_id=' + encodeURIComponent(plantId);
    const url = 'security/gatepass.php?' + params;
    this.service.get(url).subscribe((response: any) => {
      this.results = Array.isArray(response) ? response : [];
    });
  }

  openAssignMeetingModal(visitor: any) {
    this.selectedVisitor = visitor;
    this.selectedMeetingWith = '';
    this.selectedDepartment = visitor?.department_name || '';
    this.employees = [];
    
    // Load employees for the selected department
    if (this.selectedDepartment) {
      this.loadEmployeesByDepartment(this.selectedDepartment);
    }
    
    this.assignMeetingModalOpen = true;
  }

  loadEmployeesByDepartment(department: string): void {
    if (!department) {
      this.employees = [];
      return;
    }
    this.service.get('employee.php?type=getDeptEmployees&department_name=' + encodeURIComponent(department)).subscribe({
      next: (response: any) => {
        this.employees = Array.isArray(response) ? response : [];
        // Clear selected meeting person if department changed
        if (this.selectedDepartment !== department) {
          this.selectedMeetingWith = '';
        }
      },
      error: () => {
        this.employees = [];
      }
    });
  }

  onDepartmentChange(): void {
    this.selectedMeetingWith = ''; // Clear selected employee when department changes
    this.loadEmployeesByDepartment(this.selectedDepartment);
  }

  assignMeetingWith() {
    if (!this.selectedVisitor || !this.selectedMeetingWith || !this.selectedDepartment) {
      if (typeof alertify !== 'undefined') alertify.error('Please select department and meeting person');
      return;
    }
    const id = this.selectedVisitor.id;
    const plantId = localStorage.getItem('plant_id') || '1';
    const url = 'security/gatepass.php?type=assignMeetingWith&id=' + encodeURIComponent(String(id)) 
      + '&meetingwith=' + encodeURIComponent(this.selectedMeetingWith)
      + '&department_name=' + encodeURIComponent(this.selectedDepartment)
      + '&plant_id=' + encodeURIComponent(plantId);
    this.service.get(url).subscribe({
      next: (response: any) => {
        if (response && response.status === 'success') {
          if (typeof alertify !== 'undefined') alertify.success('Meeting person assigned successfully');
          this.assignMeetingModalOpen = false;
          this.selectedVisitor = null;
          this.selectedMeetingWith = '';
          this.selectedDepartment = '';
          this.employees = [];
          this.getGatepassDetails();
        } else {
          if (typeof alertify !== 'undefined') alertify.error(response?.message || 'Failed to assign meeting person');
        }
      },
      error: () => {
        if (typeof alertify !== 'undefined') alertify.error('An error occurred');
      }
    });
  }

  hasInTime(result: any): boolean {
    const t = result.in_time || result.inTime;
    return t != null && t !== '';
  }

  hasOutTime(result: any): boolean {
    const t = result.out_time || result.outTime;
    return t != null && t !== '';
  }

  hasMeetingWith(result: any): boolean {
    const meetingwith = result.meetingwith || result.meeting_with;
    return meetingwith != null && meetingwith !== '';
  }

  printGatepass(id: string | number): void {
    if (id == null) return;
    this.service.open('security/gatepass.php?type=printGatepassById&id=' + id);
  }

  visitorIn(visitor: any): void {
    if (!visitor || visitor.id == null || this.hasInTime(visitor)) return;
    const id = String(visitor.id);
    if (this.processingInIds.has(id)) return;

    const inTime = this.getCurrentSystemTime();
    this.processingInIds.add(id);
    this.updateVisitorTime(visitor, 'in_time', inTime, 'VISITOR_IN');

    const url = 'security/gatepass.php?type=visitorIn&id=' + encodeURIComponent(id)
      + '&in_time=' + encodeURIComponent(inTime);
    this.service.get(url).subscribe({
      next: (res: any) => {
        this.processingInIds.delete(id);
        if (res && res.status === 'success') {
          if (typeof alertify !== 'undefined') alertify.success('Visitor In time recorded: ' + this.datepipe.transform(inTime, 'HH:mm'));
          this.getGatepassDetails();
        } else {
          if (typeof alertify !== 'undefined') alertify.error(res?.message || 'Failed');
          this.getGatepassDetails();
        }
      },
      error: () => {
        this.processingInIds.delete(id);
        if (typeof alertify !== 'undefined') alertify.error('Failed');
        this.getGatepassDetails();
      }
    });
  }

  exitVisitor(visitor: any): void {
    if (!visitor || visitor.id == null || !this.hasInTime(visitor) || this.hasOutTime(visitor)) return;
    const id = String(visitor.id);
    if (this.processingOutIds.has(id)) return;

    const outTime = this.getCurrentSystemTime();
    this.processingOutIds.add(id);
    this.updateVisitorTime(visitor, 'out_time', outTime, 'EXIT');

    const url = 'security/gatepass.php?type=exitvisitor&id=' + encodeURIComponent(id)
      + '&out_time=' + encodeURIComponent(outTime);
    this.service.get(url).subscribe({
      next: (res: any) => {
        this.processingOutIds.delete(id);
        if (res && res.status === 'success') {
          if (typeof alertify !== 'undefined') alertify.success('Visitor Out time recorded: ' + this.datepipe.transform(outTime, 'HH:mm'));
          this.getGatepassDetails();
        } else {
          if (typeof alertify !== 'undefined') alertify.error('Failed');
          this.getGatepassDetails();
        }
      },
      error: () => {
        this.processingOutIds.delete(id);
        if (typeof alertify !== 'undefined') alertify.error('Failed');
        this.getGatepassDetails();
      }
    });
  }

  openPlantDetailsModal(): void {
    this.plantDetails = {};
    this.loadPlantDetailsIntoForm(() => {
      this.plantDetailsModalOpen = true;
    });
  }

  private loadPlantDetailsIntoForm(done?: () => void): void {
    const plantId = localStorage.getItem('plant_id') || '1';
    this.service.get('security/gatepass.php?type=getPlantDetails&plant_id=' + encodeURIComponent(plantId)).subscribe({
      next: (res: any) => {
        this.plantDetails = res && typeof res === 'object' ? res : {};
        if (done) done();
      },
      error: () => {
        this.plantDetails = {};
        if (done) done();
      }
    });
  }

  updatePlantDetails(): void {
    const plantId = localStorage.getItem('plant_id') || '1';
    this.plantDetailsLoading = true;
    const body = {
      plant_id: plantId,
      saftyInstruction: this.plantDetails.saftyInstruction || '',
      GMPInstruction: this.plantDetails.GMPInstruction || '',
      visitVideoLink: this.plantDetails.visitVideoLink || ''
    };
    this.service.post('security/gatepass.php?type=updatePlantDetails', JSON.stringify(body)).subscribe({
      next: (res: any) => {
        this.plantDetailsLoading = false;
        if (res && res.status === 'success') {
          if (typeof alertify !== 'undefined') alertify.success('Plant details updated');
          this.loadPlantDetailsIntoForm();
        } else {
          if (typeof alertify !== 'undefined') alertify.error(res?.message || 'Update failed');
        }
      },
      error: () => {
        this.plantDetailsLoading = false;
        if (typeof alertify !== 'undefined') alertify.error('Update failed');
      }
    });
  }

  generateQRCode(): void {
    const plantId = localStorage.getItem('plant_id') || '1';
    // Get the server base path from DataAccessService domain variable
    // Example: https://paperlessgmp.in/phpWonder/php/phpDevelopWonder/
    const serverBasePath = this.service.url;
    // Construct visitor form URL using server base path
    // Result: https://paperlessgmp.in/phpWonder/php/phpDevelopWonder/visitor-form.php?plant_id=181
    const visitorFormUrl = serverBasePath + 'visitor-form.php?plant_id=' + encodeURIComponent(plantId);
    // Pass form_url to PHP API - use &amp; to avoid conflicts with open() method's &plant_id= append
    const url = 'security/gatepass.php?type=generateQRCode&plant_id=' + encodeURIComponent(plantId) + '&form_url=' + encodeURIComponent(visitorFormUrl);
    console.log('QR Code - Server Base Path:', serverBasePath);
    console.log('QR Code - Visitor Form URL:', visitorFormUrl);
    console.log('QR Code - Full API URL:', this.service.url + url);
    this.service.open(url);
  }

  isuser = 'No';
  
  get_rights() {this.service.get('hr/employee.php?type=getrights&emp_id=' 
    +localStorage.getItem('emp_id') +'&dep_name=' + localStorage.getItem('department') ).subscribe((response) => {
        let rights = response;
        this.isuser = rights[0].isuser;
      });
  }


  searchQuery;
 
  get filteredMaterials(): any[] {
    if (!this.results || this.results.length === 0) return [];
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results;
    }

    const query = this.searchQuery.toLowerCase().trim();

    return this.results.filter((material) => {
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          return (
            dateValue instanceof Date &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        } else {
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }

  exportToExcel(): void {
    /* Same columns as table: #, Visit Date, Contact, Name, Email, Category, Company, Department, Meeting With, Purpose, Country, State, City, Entry By/On */
    const headers = [
      '#', 'Visit Date', 'Contact', 'Name', 'Category/Purpose', 
      'Department',  'Country', 'State', 'City',
      'Entry By/On'
    ];

    // const headers = [
    //   '#', 'Visit Date', 'Contact', 'Name', 'Email', 'Category', 'Company',
    //   'Department', 'Meeting With', 'Purpose', 'Country', 'State', 'City',
    //   'Entry By/On'
    // ];

    const data = [
      headers,
      ...this.filteredMaterials.map((r: any, i: number) => {
        const visitDate = r.visitDate || r.in_time;
        const entryOn = r.entryOn || r.in_time;
        const entryByOn = (r.entryBy || '') + ' / ' + (entryOn ? this.datepipe.transform(entryOn, 'dd-MM-yyyy HH:mm') : '-');
        return [
          i + 1,
          visitDate ? this.datepipe.transform(visitDate, 'dd-MM-yyyy') : '-',
          r.phoneNumber || r.mobile || '',
          r.visitorName || r.name || '',
          r.email || '',
          r.category || '',
          r.company || '',
          r.department_name || r.department || '',
          r.meetingwithName || r.meetingWithName || r.firstname || '',
          r.purpose || '',
          r.country || '',
          r.state || '',
          r.city || '',
          entryByOn
        ];
      })
    ];

    const ws: XLSX.WorkSheet = XLSX.utils.aoa_to_sheet(data);
    ws['!cols'] = [
      { wch: 4 }, { wch: 12 }, { wch: 12 }, { wch: 18 }, { wch: 22 }, { wch: 12 },
      { wch: 18 }, { wch: 16 }, { wch: 18 }, { wch: 14 }, { wch: 14 }, { wch: 14 },
      { wch: 14 }, { wch: 22 }
    ];

    const wb: XLSX.WorkBook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Visitor Pass Log');
    const fileName = `Visitor_Pass_Log_${this.selectedMonthYear || this.datepipe.transform(Date.now(), 'yyyy-MM')}.xlsx`;
    XLSX.writeFile(wb, fileName);
    if (typeof alertify !== 'undefined') alertify.success('Excel exported successfully');
  }
}
