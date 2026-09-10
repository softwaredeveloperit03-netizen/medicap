import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';
import { Router } from '@angular/router';
@Component({
  selector: 'app-opening',
  templateUrl: './opening.component.html',
  styleUrls: ['./opening.component.css'],
  providers:[DatePipe]
})
export class OpeningComponent implements OnInit {

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
    let temp = data.value;
    // temp['vendor_no'] = temp['vendor_no'].vendor_no;

    if(this.material_type=='Raw Material'|| this.material_type=='Packing Material'){
      temp['material_code'] = temp['material_code'].material_code;
    }
    if(this.material_type=='Finish Goods'){
      temp['product_code'] = temp['product_code'].product_code;
    }
    
    temp['batches'] = this.batches;
    console.log('ve', temp['vendor_no'] );
    this.service.post('store/opening.php?type=saveStock', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Record Saved Successfully');
        this.router.navigate(['/store']);

      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

  add(data) {
    if (!data.valid) {
      alert('All fields are required!');
      return;
    }
    this.batches[this.batches.length] = data.value;
    data.reset();
  }

  deleteBatch(index){
    this.batches.splice(index,1);
  }



  /* saveOpeningStock(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.service.post('store/opening.php?type=saveDirectStock', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Saved Successfully');
        this.router.navigate(['/store']);

      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  } */


}
