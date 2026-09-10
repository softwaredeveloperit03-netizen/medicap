
  import { Component, OnInit } from '@angular/core';
  import { DataAccessService } from 'src/app/data-access.service';
  import { DatePipe } from '@angular/common';
  import { Router } from '@angular/router';
  declare let alertify;
  
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css'],
  providers: [DatePipe] 
})
export class ApprovalComponent implements OnInit { 
  isView = false;
  specifications;

  selectedSpec = [];

  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPendingSpecifications();
  }

  getPendingSpecifications(){
    this.service.get('qc/specification/packing.php?type=getCheckedSpecifications').subscribe(response => {
      this.specifications = response;
    });
  }

  updatePackingMaterial(status) {
    this.service.get('qc/specification/packing.php?type=approveSpecification&id=' + this.selectedSpec['id'] + '&status='+ status).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.alert('Updated Successfully');
        this.isView = false;
        this.getPendingSpecifications();
      }else{
        alertify.alert('Failed: An error occured, please try again!');
      }
     
    });
  }

  viewSpecification(index) {
    this.isView = true;
    this.selectedSpec = this.specifications[index];
  }


  }
  