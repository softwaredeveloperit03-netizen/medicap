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

  getGatepassDetails() {
    const params = 'type=getGatepassDetails&from_date=' + encodeURIComponent(this.from_date) + '&to_date=' + encodeURIComponent(this.to_date);
    const dept = this.deptName;
    const url = 'security/gatepass.php?' + params + (dept ? '&deptName=' + encodeURIComponent(dept) : '');
    this.service.get(url).subscribe((response: any) => {
      this.results = Array.isArray(response) ? response : [];
    });
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
    /* Same columns as table: #, Visit Date, Contact, Name, Email, Category, Company, Department, Meeting With, Purpose, Country, State/Province, City, Entry By/On */
    const headers = [
      '#', 'Visit Date', 'Contact', 'Name', 'Email', 'Category', 'Company',
      'Department', 'Meeting With', 'Purpose', 'Country', 'State/Province', 'City',
      'Entry By/On'
    ];
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
