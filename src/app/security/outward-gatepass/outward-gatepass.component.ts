import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
import * as XLSX from 'xlsx';
declare let alertify;

@Component({
  selector: 'app-outward-gatepass',
  templateUrl: './outward-gatepass.component.html',
  styleUrls: ['./outward-gatepass.component.css'],
  providers: [DatePipe]
})
export class OutwardGatepassComponent implements OnInit {

  from_date = '';
  to_date = '';
  results: any[] = [];
  searchQuery = '';
  loading = false;

  constructor(private service: DataAccessService, private datepipe: DatePipe) {
    const today = new Date();
    this.from_date = this.datepipe.transform(today, 'yyyy-MM-dd') || '';
    this.to_date = this.datepipe.transform(today, 'yyyy-MM-dd') || '';
  }

  ngOnInit() {
    this.loadData();
  }

  onDateChange() {
    this.loadData();
  }

  loadData() {
    this.loading = true;
    const params = 'type=getOutpassForSecurity&from_date=' + encodeURIComponent(this.from_date) + '&to_date=' + encodeURIComponent(this.to_date);
    this.service.get('security/outpass_api.php?' + params).subscribe((response: any) => {
      this.results = Array.isArray(response) ? response : [];
      this.loading = false;
    }, () => { this.loading = false; });
  }

  get filteredResults(): any[] {
    if (!this.results || this.results.length === 0) return [];
    if (!this.searchQuery || this.searchQuery.trim() === '') return this.results;
    const q = this.searchQuery.toLowerCase().trim();
    return this.results.filter(r =>
      Object.values(r).some(v => v && String(v).toLowerCase().includes(q))
    );
  }

  isPendingExit(r: any): boolean {
    return r.status === 'PENDING_SECURITY_EXIT';
  }

  isExitDone(r: any): boolean {
    return r.status === 'EXIT';
  }

  getStatusLabel(status: string): string {
    const map: Record<string, string> = {
      PENDING_SECURITY_EXIT: 'Awaiting Exit',
      EXIT: 'Completed',
      PENDING_DEPT_HEAD: 'Pending Dept Head',
      PENDING_HR_HEAD: 'Pending HR Head',
      PENDING_PLANT_HEAD: 'Pending Plant Head',
      REJECTED_DEPT_HEAD: 'Rejected (Dept)',
      REJECTED_HR_HEAD: 'Rejected (HR)',
      REJECTED_PLANT_HEAD: 'Rejected (Plant)'
    };
    return map[status] || status;
  }

  exitOutpass(id: string | number) {
    if (id == null) return;
    this.service.get('security/outpass_api.php?type=securityExitOutpass&id=' + encodeURIComponent(String(id))).subscribe((res: any) => {
      if (res && res.status === 'success') {
        if (typeof alertify !== 'undefined') alertify.success('Exit recorded');
        this.loadData();
      } else {
        if (typeof alertify !== 'undefined') alertify.error(res?.message || 'Failed');
      }
    });
  }

  exportToExcel() {
    const params = 'type=getOutpassLogForExport&from_date=' + encodeURIComponent(this.from_date) + '&to_date=' + encodeURIComponent(this.to_date);
    this.service.get('security/outpass_api.php?' + params).subscribe((response: any) => {
      const data = Array.isArray(response) ? response : [];
      const headers = [
        '#', 'Pass No', 'Emp ID', 'Emp Name', 'Department', 'Reason', 'Reason Details',
        'Status', 'Created On', 'Dept Head Approved', 'HR Head Approved', 'Plant Head Approved', 'Security Exit On'
      ];
      const rows = data.map((r: any, i: number) => [
        i + 1,
        r.pass_no || '',
        r.emp_id || '',
        r.emp_name || '',
        r.department || '',
        r.reason || '',
        r.reason_details || '',
        r.status || '',
        r.createdOn ? this.datepipe.transform(r.createdOn, 'dd-MM-yyyy HH:mm') : '-',
        r.deptHeadApprovalOn ? this.datepipe.transform(r.deptHeadApprovalOn, 'dd-MM-yyyy HH:mm') : '-',
        r.hrHeadApprovalOn ? this.datepipe.transform(r.hrHeadApprovalOn, 'dd-MM-yyyy HH:mm') : '-',
        r.plantHeadApprovalOn ? this.datepipe.transform(r.plantHeadApprovalOn, 'dd-MM-yyyy HH:mm') : '-',
        r.securityExitOn ? this.datepipe.transform(r.securityExitOn, 'dd-MM-yyyy HH:mm') : '-'
      ]);
      const ws: XLSX.WorkSheet = XLSX.utils.aoa_to_sheet([headers, ...rows]);
      ws['!cols'] = [
        { wch: 4 }, { wch: 16 }, { wch: 10 }, { wch: 20 }, { wch: 16 }, { wch: 16 }, { wch: 18 },
        { wch: 22 }, { wch: 18 }, { wch: 18 }, { wch: 18 }, { wch: 18 }, { wch: 18 }
      ];
      const wb: XLSX.WorkBook = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, 'Outpass Log');
      const fileName = `Outpass_Log_${this.from_date}_to_${this.to_date}.xlsx`;
      XLSX.writeFile(wb, fileName);
      if (typeof alertify !== 'undefined') alertify.success('Excel exported successfully');
    });
  }
}
