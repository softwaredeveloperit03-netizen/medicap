import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  results;
  dosages;
  batches;
  dosage_form = '';
  product_name = '';
  product_code='';
  grade = '';
  grades;
  products;
  materials = [];
  number_of_batches = 0;
  selectedBatch = [];
  required_for='';
  requirement='';
  isShortage = false;
  units
  unit='';
  bom_no='';
  plan_for='';
  shortege: any[] = [
    {
      "message":  "Reduce No. of Batches or Make Indend",
    
    },
  ]
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.getDosageForms();
    this.getUnits();
  } 

  getDosageForms() {
    this.service.get('common.php?type=getDosages').subscribe(response => {
      this.dosages = response;
    });
  }
  public getColor(qty: number): string{
    return qty >  0 ? "red" : "black"  ; /* 
    return balance > 0 ? "message" :  "Reduce No. of Batches or Make Indend"; */
   
 }
 getClass(priority){
  
  return {'message': " Reduce No. of Batches or Make Indend",
          }

}
  getProducts() {
    this.service.get('production/shortage.php?type=getProducts&dosage_form='+this.dosage_form).subscribe(response => {
      this.products = response;
    });
  }

  getUnits() {
    this.service.get('management/unit.php?type=getUnits').subscribe(response => {
      this.units = response;
    });
  }

  getProductGrades() {
    this.service.get('production.php?type=getProductGrades&dosage_form=' + this.dosage_form + '&product_name=' + this.product_name).subscribe(response => {
      this.grades = response;
    });
  }

  getBatchSizes(index) {
    index = index - 1;
    if(index != -1) {
      let batches = this.products[index];
      this.batches = batches['batches'];
    }
  }
  
  getMaterial(index) {
    for(let i = 0; i < this.batches.length; i++) {
      this.selectedBatch = this.batches[index];
      this.materials = this.batches[index].materials;
    }
  }

  getPlanQty() {
   
    this.isShortage = false;
    if(this.number_of_batches < 0)
    {
      alertify.error("Plese Enter Valid No")
    }
    else
    {
      for (let i = 0; i < this.materials.length; i++) {
        let material = this.materials[i];
        material['plan_qty'] = +material['batch_qty'] * +this.number_of_batches;
        if (+material['lod_per'] != 0) {
          material['lod_qty'] = +parseFloat(((+material['plan_qty'] * +material['lod_per']) / 100) + '').toFixed(2);
        } else {
          material['lod_qty'] = 0;
        }
        console.log(material);
        material['required_qty'] = +material['plan_qty'] + +material['lod_qty'];
        material['shortage_qty'] = +material['required_qty'] - +material['available_qty'];
        if (+material['shortage_qty'] <= 0) {
          material['shortage_qty'] = 0;
        } else {
          this.isShortage = true;
        }
        this.materials[i] = material;
      }
    }
  
  }
  checkValue(event) {
    if (event.target.value < 0) {
      event.target.value = 0;
    }
  }

  saveBatchPlan(data){
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    if(this.number_of_batches < 0)
    {
      alertify.error("Plese Enter Valid No")
    }
    else{
      let temp=data.value;
      temp['materials'] = this.materials;
      temp['batch_size'] = this.selectedBatch['batch_size'];
      temp['stages'] = this.selectedBatch['stages'];
      temp['raw_materials'] = this.selectedBatch['raw_materials'];
      temp['packing_materials'] = this.selectedBatch['packing_materials'];
      this.service.post('planning/plan.php?type=savePlan', JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
          data.resetForm();
          alertify.success('Saved Successfully');
          this.router.navigate(['/planning/plan']);
        } else {
          alertify.error('An error occured, Please try again');
        }
      });
    }
 
  }

}
