import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-deviation-management',
  templateUrl: './deviation-management.component.html',
  styleUrls: ['./deviation-management.component.css']
})
export class DeviationManagementComponent implements OnInit {

  isUser = false;
  isChecker = false;
  isApprover = false;

  results;
  isView = false;
  selectedDev = [];
  constructor(private service: DataAccessService) {
    this.isUser = Boolean(JSON.parse(localStorage.getItem('user')));
    this.isChecker = Boolean(JSON.parse(localStorage.getItem('checker')));
    this.isApprover = Boolean(JSON.parse(localStorage.getItem('approver')));
  }

  ngOnInit(): void {
    this.getDeviations();
  }

  getDeviations() {
    this.service.get('qaDepartment.php?type=getDeviations').subscribe(response => {
      this.results = response;
    });
  }

  viewDeviation(index) {
    this.selectedDev = this.results[index];
    this.isView = true;
  }

  updateDeviation(dev_no, status) {
    this.service.get('qaDepartment.php?type=updateDeviation&dev_no=' + dev_no + '&status=' + status).subscribe(response => {
      if (response['status'] == 'success') {
        this.getDeviations();
        alert('Updated Successfully');
        this.isView = false;
      }
    });
  }

}
