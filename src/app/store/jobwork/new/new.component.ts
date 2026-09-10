import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
  providers:[DatePipe]
})
export class NewComponent implements OnInit {

  
  constructor(private service: DataAccessService,private router:Router,private datePipe : DatePipe) {
   }

  ngOnInit() {
      
   this.getMaterialSubType();
   this.getApprovedProducts();
  }

  subTypes;
  materials;
  material_type = 'Finish Goods';
  material_subtype = '';
  material_code = '';
  batchData;
  products;

  getMaterialSubType() {
    this.service.get('store/jobwork.php?type=getMaterialSubType&material_type=Raw Material').subscribe(response => {
      this.subTypes = response;
     });
  }

  getMaterialForJobWork() {
    this.service.get('store/jobwork.php?type=getMaterialBySubType&material_subtype='+this.material_subtype).subscribe(response => {
      this.materials = response;
     });
  }
  getApprovedProducts() {
    this.service.get('store/jobwork.php?type=getApprovedProducts').subscribe(response => {
      this.products = response;
     });
  }


  getBatchData() {
    this.selectedBatch = [];
    this.service.get('store/jobwork.php?type=getBatchData&material_code='+this.material_code).subscribe(response => {
      this.batchData = response;
     });
  }
  selectedProduct =[];

  getBatchDataFg(index) {
    this.selectedProduct = [];
    this.selectedBatch = [];
    this.selectedProduct = this.products[index];

    this.service.get('store/jobwork.php?type=getBatchDataFg&material_code='+this.material_code).subscribe(response => {
      this.batchData = response;
     });
  }

  selectedBatch = [];

  selectedBatchNo(index){
    this.selectedBatch = this.batchData[index-1];
  }

  jobWorkData = [];

  addData(data){
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp=data.value;
    temp['product_name'] = this.selectedProduct['product_name'];
    temp['pack_size'] = this.selectedBatch['pack_size'];
    temp['pack_size_unit'] = this.selectedBatch['pack_size_unit'];
    this.jobWorkData.push(temp);
    data.reset();
    this.selectedBatch = [];
    this.selectedProduct = [];
    this.material_type = 'Finish Goods';
  }

  delData(i){
    this.jobWorkData.splice(i,1);
  }
 

  saveOpeningStock(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp=data.value;
    temp['jobWorkData'] = this.jobWorkData;
     this.service.post('store/jobwork.php?type=saveJobwork', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Record Saved Successfully');
        this.router.navigate(['/store/job_work']);
        this.jobWorkData =[];
  

      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

  



 

}




 