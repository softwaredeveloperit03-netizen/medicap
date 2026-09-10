import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
 
  
  constructor(private service: DataAccessService, private router: Router,private cbr:ChangeDetectorRef) { }
  ngOnInit() {
    this.getRequisitionForPlantHeadLog()
    this.get_rights();
  }

  isapprover = 'No';
  rights;

  get_rights() {
    this.service.get('hr/employee.php?type=getrights&emp_id=' +localStorage.getItem('emp_id') +'&dep_name=' +localStorage.getItem('department')     ).subscribe((response) => {
        this.rights = response;
        this.isapprover = this.rights[0].isapprover;
    });
  }
 
  isView = false;
  selectedResult = [];
  view(data) {
    this.selectedResult = data;
    this.isView = true;
  }
 
  download() {
    this.service.open('hr/manpower.php?type=getRequisitionLogIndividual&id=' + this.selectedResult['id']);
  }


  requisitions;
 
  getRequisitionForPlantHeadLog() {
      this.service.get('hr/manpower.php?type=getRequisitionForPlantHeadLog').subscribe((response: any) => {
        this.requisitions = response;
      });
  }
 
  

  exportToExcel(): void {
    let csvContent = '';
  
    // Add column headers
    const headers = ['Date', 'Department', 'Grade', 'Designation', 'Description', 'No. of candidates', 'Req. Qualification', 'Entered By', 'Status', 'Approved By'];
    csvContent += headers.join(',') + '\n';
  
    // Add row data
    this.requisitions.forEach(user => {
      const row = [
        user.entry_date || 'NA',
        user.department || 'NA',
        user.type || 'NA',
        user.designation || 'NA',
        user.description ? user.description.split('\\n').join('\r\n') : 'NA',
        user.candidates || 'NA',
        user.qualification || 'NA',
        user.entry_by || 'NA',
        user.status == 'approve' ? 'Approved' :
        user.status == 'reject' ? 'Rejected' :
        user.status == 'revision' ? 'Revision' : 'NA'
      ];
      csvContent += row.join(',') + '\n';
    });
  
    // Create a Blob containing the CSV content
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  
    // Create a temporary anchor element to trigger the download
    const link = document.createElement('a');
    if (link.download !== undefined) {
      const url = URL.createObjectURL(blob);
      link.setAttribute('href', url);
      link.setAttribute('download', 'requisitions_report.csv');
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }
  }  

 
}