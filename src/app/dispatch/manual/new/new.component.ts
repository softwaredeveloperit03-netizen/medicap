import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
  providers:[DatePipe]
})
export class NewComponent implements OnInit {

  products;
  product_code;
  curr_date;
  stock_type;
  ar_no;
  rel_date;
  batch_no;
  batch_size;
  yield;
  pack_size;
  total_containers;
  mfg_date;
  exp_date;
  entry_for;
  fg_sub_materials = [];
  process_types;
  dosage_type;
  dosage_form;

  constructor(private service : DataAccessService, private datePipe: DatePipe, private router: Router) {
    this.curr_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
   }

  ngOnInit(): void {
      this.curr_date = Date.now();
  }

  getProductsByType(type){
    this.service.get('production/product.php?type=getProductsByType&product_type='+type).subscribe(response => {
      this.products = response;
    });
  }

  // save(data){
  //   if (!data.valid) {
  //     alertify.error('All fields are required');
  //     return;
  //   }  
  //   this.service.post('dispatch/opening.php?type=saveFgStock', JSON.stringify(data.value)).subscribe(response => {
  //     if (response['status'] === 'success') {
  //       this.router.navigate(['/dispatch/manual'])
  //       alertify.success('Form has been saved successfully.');
  //     } else {
  //       alertify.error('An error occured, please try again');
  //     }
  //   });
  // } 
  save(Form){
    if(!Form.valid){
      alertify.error('All fields are required');
      return;
    }
    let temp=Form.value
    this.service.post('dispatch/opening.php?type=saveFgStock',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        this.router.navigate(['/dispatch/manual']);
        alertify.success('data save Successfuly');
        Form.resetForm();
      }else{
        alertify.error('Error Occured');
      }
    });
  }

  getFGMaterials(value) {
    this.service.observableFGTypes.subscribe(response => {
      // let data =response;
      if (value == "") {
        return;
      }
      let idx = -1;
      for (let i = 0; i < response.length; i++) {
        if (response[i]['material_subtype'] == value) {
          idx = i;
        }
      }
      if (idx >= 0) {
        let data = response[idx]
        this.fg_sub_materials = data['sub_materials'];

      } else {
        this.fg_sub_materials = []
      }
    })
  }
  getProductsByDosageForm(type) {
    this.service.get('production/product.php?type=getProductsByDosageForm&product_type=' + type).subscribe(response => {
      this.products = response;
      this.getStepsAndStages(type);
    });
  }
  getStepsAndStages(dosage_form) {
    this.service.get('production/stage.php?type=get_stages_by_process_type&dosage_form=' + dosage_form).subscribe(response => {
      this.process_types = response;

    });
  }
  getProductCode(val) {
    console.log(val, this.products[val-1]);
    this.product_code = this.products[val-1].product_code;
  }
}
