import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  result;
  selectedMethod=[];
  isView=false;
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPendingTestMethods();
  }
  view(index) {
    this.selectedMethod = this.result[index];
    this.isView = true;
  }

  getPendingTestMethods(){
    this.service.get('qc/method.php?type=getPendingTestMethods').subscribe(response=>{
      this.result=response;
    });
  }

  updateTestMethod(status) {     
     this.service.get('qc/method.php?type=updateTestMethod&status=' + status + '&id=' + this.selectedMethod['id'] + '&specification_no=' + this.selectedMethod['specification_no']).subscribe(response => {
      if (response['status']) {
        alertify.success('Methods Updated Successfully');
        this.isView = false;
        this.getPendingTestMethods();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  };
  
}
