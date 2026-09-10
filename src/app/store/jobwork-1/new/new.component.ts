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

  isNew = false;
  results;
  materials;
  vendors;
  products;
  material_type='';
  material_subtype = '';
  units;
  mfg_date='';
  exp_date='';
  batches = [];
  selectedMaterial=[];
  selectedProduct=[];
  isMaterial = false;
  constructor(private service: DataAccessService,private router:Router,private datePipe : DatePipe) {
   }

  ngOnInit() {
    this.getVendors();
    this.service.observableUnit.subscribe(response=>{
      this.units=response;
    });
   
  }

  getVendors(){
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }

  getMaterials(value) {
    this.service.get('common.php?type=getMaterialsByType&material_subtype='+value).subscribe(response => {
      this.materials = response;
      this.isMaterial=true;
    });
  }

  getProducts(value) {
    this.service.get('common.php?type=getProductsByDosage&product_type='+value).subscribe(response => {
      this.products = response;
    });
  }

  saveOpeningStock(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp=data.value;
    // temp['grn_no']=this.selectedMaterial['grn_no'];
    this.service.post('store/jobwork.php?type=saveJobwork', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Record Saved Successfully');
        this.router.navigate(['/store']);

      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

  



 

}
