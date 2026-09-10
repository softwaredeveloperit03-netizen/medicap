import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-purchase-matrix',
  templateUrl: './purchase-matrix.component.html',
  styleUrls: ['./purchase-matrix.component.css']
})
export class PurchaseMatrixComponent implements OnInit {

  constructor(public service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getPurchaseApprovalMatrix();
  }


  matrixList;
  getPurchaseApprovalMatrix() {
    this.service.get('master/master.php?type=getPurchaseApprovalMatrix').subscribe((response) => {
      this.matrixList = response;
    });
  }
 



  firstApprover = '';
  secAppReq = 'NO';
  secondtApprover = '';

 
  savePurchaseApprovalMatrix(data) {

    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }

    let temp = data.value;

    this.service.post('master/master.php?type=savePurchaseApprovalMatrix', JSON.stringify(temp)).subscribe((response) => {
      if (response['status'] == 'success') {
        data.resetForm();
        this.getPurchaseApprovalMatrix();
        alertify.success('Matrix Saved Successfully Its Get Apply When Its Approved.....');
      } else {
        alertify.error(response['status']);
      }
    });
  }


  approvePurchaseApprovalMatrix(data) {

    this.service.post('master/master.php?type=approvePurchaseApprovalMatrix', JSON.stringify(data)).subscribe((response) => {
      if (response['status'] == 'success') {
        this.getPurchaseApprovalMatrix();
        alertify.success('Matrix Saved Successfully Its Get Apply When Its Approved.....');
      } else {
        alertify.error(response['status']);
      }
    });
  }

 
  
}
