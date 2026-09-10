import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
import { finalize } from 'rxjs/operators';
declare let alertify;
import * as XLSX from 'xlsx';
@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

 
  constructor(private service: DataAccessService, private router: Router) { }

 
  ngOnInit() {
    this.getApproveTestingLog();
    this.emp_id = localStorage.getItem('emp_id');
   }
 

  emp_id = localStorage.getItem('emp_id');

  results;
  material_type = 'Raw Material';
  showAuditTrail = false;
  auditLoading = false;
  auditResults: any[] = [];
  from_date = '';
  to_date = '';
  auditSearch = '';
  pageSizeOptions: number[] = [10, 20, 30, 50, 100];
  pageSize = 20;
  currentPage = 1;

  initAuditDates(): void {
    if (this.from_date && this.to_date) {
      return;
    }
    const now = new Date();
    this.to_date = now.toISOString().slice(0, 10);
    const fromObj = new Date();
    fromObj.setDate(fromObj.getDate() - 30);
    this.from_date = fromObj.toISOString().slice(0, 10);
  }

  openAuditTrail(): void {
    this.showAuditTrail = true;
    this.initAuditDates();
    this.getTestingAuditTrail();
  }

  closeAuditTrail(): void {
    this.showAuditTrail = false;
  }

  getTestingAuditTrail(): void {
    this.auditLoading = true;
    this.service.get('qc/testing/raw.php?type=getTestingRawAuditTrail&from_date=' + encodeURIComponent(this.from_date) + '&to_date=' + encodeURIComponent(this.to_date))
      .subscribe((response: any) => {
        this.auditResults = Array.isArray(response) ? response : [];
        this.auditLoading = false;
        this.currentPage = 1;
      }, () => {
        this.auditResults = [];
        this.auditLoading = false;
      });
  }

  onAuditPageSizeChange(value: any): void {
    const size = Number(value);
    this.pageSize = Number.isFinite(size) && size > 0 ? size : 20;
    this.currentPage = 1;
  }

  getAuditSrNo(index: number): number {
    return (this.currentPage - 1) * this.pageSize + index + 1;
  }

  get filteredAuditRows(): any[] {
    if (!this.auditSearch || this.auditSearch.trim() === '') {
      return this.auditResults || [];
    }
    const q = this.auditSearch.toLowerCase().trim();
    return (this.auditResults || []).filter((row: any) =>
      Object.values(row || {}).some((v) => v != null && String(v).toLowerCase().includes(q))
    );
  }
  getApproveTestingLog() {
    this.service.get('qc/testing/raw.php?type=getApproveTestingLog&material_type='+this.material_type).subscribe(response => {
      this.results = response;
    });
  }
 

  isTestView = false; 
  isView = false; 
  selectedTesting = {};
  tests;
 
  viewTesting(data) {
    this.loading = true;
    this.loadingMessage = 'Please Wait Tests Are Loading......';
    setTimeout(() => {
      this.getTestByTestingNO(data['testing_no']);
    }, 300);
    this.selectedTesting = data;
    this.isView = true;
    this.isTestView = false;
  }
 
  loading = false;
  loadingMessage = '';
  
  getTestByTestingNO(testing_no: string) {
    this.service
      .get(
        'qc/testing/raw.php?type=getTestByTestingNOForApproval&testing_no=' +
          encodeURIComponent(testing_no)
      )
      .pipe(
        finalize(() => {
          this.loading = false;   // ALWAYS stop loader
        })
      ).subscribe({
        next: (response) => {
          this.tests = response;
          this.allNotChecked = this.tests.every(test => test.status === 'Checked' );
        },
        error: (err) => {
          console.error('API error:', err);
        }
      });
  }

  allNotChecked = false;

  selectedTest = {};

  viewTest(data){
    this.selectedTest = data;
    this.isView = false;
    this.isTestView = true;
  }
  
downloadTestingLog() {
    this.service.open('qc/testing/raw.php?type=downloadTestingLog&material_type=' + this.material_type);
  }

exportToExcel(): void {
  const fileName = 'Approved_Testing_Log.xlsx';

  /* ------------------ Headers ------------------ */
  const header = [
    'Sr. No',
    'Testing No.',
    'Sampling No.',
    'Material Name',
    'Material Code',
    'Medicap Lot No',
    'Receiving no',
    'Spec. No.',
    'Status',
    'Allocated By / Date',
    'Checked By / Date',
    'Approved By / Date'
  ];

  /* ------------------ Data ------------------ */
  const data = [
    header,
    ...this.filteredMaterials.map((testing, index) => [
      index + 1,
      testing.testing_no,
      testing.sampling_no,
      testing.material_name,
      testing.material_code,
      testing.batch_no,
      testing.grn_no,
      testing.specification_no,
      testing.status,
      `${testing.allocationBy} / ${testing.allocationOn}`,
      `${testing.check_by} / ${testing.check_date}`,
      `${testing.approve_by} / ${testing.approve_date}`
    ])
  ];

  const ws: XLSX.WorkSheet = XLSX.utils.aoa_to_sheet(data);

  /* ------------------ Range ------------------ */
  const range = XLSX.utils.decode_range(ws['!ref']!);

  /* ------------------ Header Colors ------------------ */
  const headerColors = [
    '2F5597', '2F5597', '2F5597',   // Identification (Blue)
    '1F7A8C', '1F7A8C', '1F7A8C',   // Material Info (Teal)
    '7030A0', '7030A0', '7030A0',   // Reference No. (Purple)
    '548235',                     // Status (Green)
    '595959', '595959', '595959'  // Approval Flow (Gray)
  ];

  /* ------------------ Apply Styles ------------------ */
  for (let R = range.s.r; R <= range.e.r; ++R) {
    for (let C = range.s.c; C <= range.e.c; ++C) {
      const cellRef = XLSX.utils.encode_cell({ r: R, c: C });
      if (!ws[cellRef]) continue;

      ws[cellRef].s = {
        font: {
          bold: R === 0,
          color: { rgb: R === 0 ? 'FFFFFF' : '000000' }
        },
        alignment: {
          horizontal: R === 0 ? 'center' : 'left',
          vertical: 'center',
          wrapText: true
        },
        fill: R === 0
          ? { fgColor: { rgb: headerColors[C] } }
          : undefined,
        border: {
          top: { style: 'thin' },
          bottom: { style: 'thin' },
          left: { style: 'thin' },
          right: { style: 'thin' }
        }
      };
    }
  }

  /* ------------------ Column Widths ------------------ */
  ws['!cols'] = [
    { wch: 6 },   // Sr. No
    { wch: 14 },
    { wch: 14 },
    { wch: 22 },
    { wch: 18 },
    { wch: 14 },
    { wch: 12 },
    { wch: 12 },
    { wch: 14 },
    { wch: 12 },
    { wch: 22 },
    { wch: 22 },
    { wch: 22 }
  ];

  /* ------------------ Freeze Header ------------------ */
  ws['!freeze'] = { xSplit: 0, ySplit: 1 };

  /* ------------------ Auto Filter ------------------ */
  ws['!autofilter'] = { ref: ws['!ref']! };

  /* ------------------ Workbook ------------------ */
  const wb: XLSX.WorkBook = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'Approved Testing Log');

  XLSX.writeFile(wb, fileName);
}





 
    searchQuery;
 
    get filteredMaterials(): any[] {
      if (!this.searchQuery || this.searchQuery.trim() === '') {
        return this.results; // If search query is empty or whitespace, return all materials
      }
  
      const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace
  
      return this.results.filter((material) => {
        // Check if any field of the material contains the search query
        return Object.entries(material).some(([key, value]) => {
          if (key === 'entry_date') {
            // Convert the value to a Date object if it's not already
            const dateValue = typeof value === 'string' ? new Date(value) : value;
            // Check if the date value is valid and includes the search query
            return (
              dateValue instanceof Date &&
              dateValue.toISOString().slice(0, 10).includes(query)
            );
          } else {
            // Convert field value to lowercase and check if it includes the search query
            return value && value.toString().toLowerCase().includes(query);
          }
        });
      });
    }

 

}
