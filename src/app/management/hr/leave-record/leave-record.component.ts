import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
import * as XLSX from 'xlsx';
@Component({
  selector: 'app-leave-record',
  templateUrl: './leave-record.component.html',
  styleUrls: ['./leave-record.component.css'],
})
export class LeaveRecordComponent implements OnInit {
  selectedResult = [];
  isView = false;
  results;

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getApprovedLeave();
  }

  getApprovedLeave() {
    this.service
      .get('hr/leaveForm.php?type=getLeaveLog')
      .subscribe((response) => {
        this.results = response;
      });
  }

  pending;
  view(index) {
    this.selectedResult = this.results[index];
    this.pending = JSON.parse(this.selectedResult['pendingList']);
    this.isView = true;
  }

  download() {
    this.service.open('hr/leaveForm.php?type=downloadLeaveLog');
  }
  exportToExcel(): void {
    // Map the data for the Excel file
    const formattedData = this.results.map((user, index) => ({
      'Sr No.': index + 1,
      Department: user.department_name,
      'Employee Name': `${user.fnempname} ${user.lnempname}`,
      'Leave From': this.formatDate(user.leave_from),
      'Leave To': this.formatDate(user.leave_to),
      'Leave Type': user.leave_type,
      Status: user.status,
    }));

    // Create a worksheet
    const worksheet: XLSX.WorkSheet = XLSX.utils.json_to_sheet(formattedData);

    // Create a workbook and add the worksheet
    const workbook: XLSX.WorkBook = {
      Sheets: { data: worksheet },
      SheetNames: ['data'],
    };

    // Save the workbook to a file
    XLSX.writeFile(workbook, 'LeaveRequests.xlsx');
  }

  formatDate(dateString: string): string {
    const [year, month, day] = dateString.split('-');
    return `${day}-${month}-${year}`;
  }
}
