import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
import { finalize } from 'rxjs/operators';
declare let alertify;
import * as XLSX from 'xlsx';

@Component({
  selector: 'app-report',
  templateUrl: './report.component.html',
  styleUrls: ['./report.component.css']
})
export class ReportComponent implements OnInit {
 
 
  constructor(private service: DataAccessService, private router: Router) { }

 
  ngOnInit() {
    this.getOutsideTestingLog();
    this.emp_id = localStorage.getItem('emp_id');
   }
 

  emp_id = localStorage.getItem('emp_id');

  results;
  material_type = 'Raw Material';
  getOutsideTestingLog() {
    this.service.get('qc/testing/raw.php?type=getOutsideTestingLog&material_type='+this.material_type).subscribe(response => {
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
        'qc/testing/raw.php?type=getTestByTestingNOOutside&testing_no=' +
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
  
exportToExcel(): void {
  const fileName = 'Outside_Testing_Log.xlsx';

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
  XLSX.utils.book_append_sheet(wb, ws, 'Outside Testing Log');

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
