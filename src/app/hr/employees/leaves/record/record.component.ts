import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
import * as XLSX from 'xlsx';
@Component({
  selector: 'app-record',
  templateUrl: './record.component.html',
  styleUrls: ['./record.component.css']
})
export class RecordComponent implements OnInit {
  selectedResult= [];
  isView = false;
  results: any = [];
  searchText = '';

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getApprovedLeave();
    this.getLeaveData();
  }

  getApprovedLeave() {
    this.service.get('hr/leaveForm.php?type=getLeaveLog').subscribe(response => {
      this.results = response;
    });
  }

  last_7_days =0;
  last_month =0;
  current_day =0;

  statusLabel(status: string): string {
    if (status === 'approve') {
      return 'Approved';
    }
    if (status === 'Rejected') {
      return 'Rejected';
    }
    if (status === 'Pending_Dept_Head_Approval') {
      return 'Pending Dept Head';
    }
    return status || '';
  }



  getLeaveData() { 
    this.service.get('hr/leaveForm.php?type=getLeaveData').subscribe(response => {
      this.last_month = response['last_month'];
      this.current_day = response['current_day'];
      this.last_7_days = response['last_7_days'];

      console.log(this.last_month);
      console.log(this.current_day);
      console.log(this.last_7_days);
    });
  }

  pending
    view(index) {
      this.selectedResult = this.results[index];
      this.pending = JSON.parse(this.selectedResult['pendingList']);
      this.isView = true;
    }

    download(){
      this.service.open('hr/leaveForm.php?type=downloadLeaveLog');
    }
    exportToExcel(): void {
      // Map the data for the Excel file
      const formattedData = this.results.map((user, index) => ({
        'Sr No.': index + 1,
        'Department': user.department_name,
        'Employee Name': `${user.fnempname} ${user.lnempname}`,
        'Leave From': this.formatDate(user.leave_from),
        'Leave To': this.formatDate(user.leave_to),
        'Leave Type': user.leave_type,
        'Status': user.status
      }));
  
      // Create a worksheet
      const worksheet: XLSX.WorkSheet = XLSX.utils.json_to_sheet(formattedData);
  
      // Create a workbook and add the worksheet
      const workbook: XLSX.WorkBook = {
        Sheets: { 'data': worksheet },
        SheetNames: ['data']
      };
  
      // Save the workbook to a file
      XLSX.writeFile(workbook, 'LeaveRequests.xlsx');
    }
  
    formatDate(dateString: string): string {
      const [year, month, day] = dateString.split('-');
      return `${day}-${month}-${year}`;
    }
}
