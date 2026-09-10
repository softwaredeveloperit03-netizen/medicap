import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  isView = false;
  results;

  selectedResult = [];
  remark = '';
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getInprocessResponsibilities();
  }

  getInprocessResponsibilities() {
    this.service.get('hr/responsibility.php?type=getInprocessResponsibilities').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  update(status) {
    let temp = this.selectedResult['responsibilities'];
    temp['remark'] = this.remark;
    temp['status'] = status;
    this.service.post('hr/responsibility.php?type=approveResponsibilities&emp_code='+ this.selectedResult['emp_id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.remark = '';
        alert('Record Updated Successfully');
        this.isView = false;
        this.getInprocessResponsibilities();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
