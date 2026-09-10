import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  isView = false;
  results;
  selectedBalance: any= [];
  std_wt_dtl:any = [];
  variation;
  departments;
  variationper;
  display;
  employees;
  selected_product = [];
  department;
  trolly;
  trolly_no;
  trolly_dtl:any=[];
 /*  weights = [
    { "id": 1, "standard": 0, "display": 0},
    { "id": 2, "standard": 0, "display": 0},
    { "id": 3, "standard": 0, "display": 0},
    { "id": 4, "standard": 0, "display": 0},
    { "id": 5, "standard": 0, "display": 0}
  ]; */
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingCalibration();
    this.getEmployees();
    this.getDepartments();
   
  }
  getDepartments() {
    this.service.get('hr/employee.php?type=get_department')
      .subscribe(response => {
        this.departments = response;
      });
  }

  getSubMaterials(value){
    this.service.get('hr/employee.php?type=get_trolly&t_dep='+value).subscribe(response => {
      this.trolly = response;
      // this.trolly_dtl=this.trolly["std_wt_dtl"]
      // console.log(this.trolly_dtl);
    });
  }

  getPendingCalibration() {
    this.service.get('calibration/balance.php?type=getPendingCalibrations').subscribe(response => {
      this.results = response;
    });
  }
  // getdetail(index){
  
  //     this.trolly_dtl = this.trolly[index];
  //     this.std_wt_dtl=this.trolly_dtl['std_wt_dtl'];
  //   console.log(this.std_wt_dtl);
  //   console.log(this.trolly_dtl);
  // }
  LoadPoduct() {
    // this.selected_product = this.trolly[idx];
    //  this.trolly_dtl=this.selected_product['std_wt_dtl'];
    // // console.log(this.selected_product['std_wt_dtl']);
    // // console.log('hi');
    //  console.log(this.trolly_dtl);
    this.service.get('calibration/balance.php?type=get_bal_dtl&trolly_no='+this.trolly_no+'&department1='+this.department).subscribe(response => {
      this.trolly_dtl = response;
      console.log(this.trolly_dtl['std_wt_dtl']);
      console.log('hi');
      console.log(this.trolly_dtl['std_wt_dtl'][0]);
      console.log('hi');
      console.log(this.trolly_dtl);
    });
  }
  getEmployees() {
    this.service.get('employee.php?type=getDeptEmployees').subscribe(response => {
      this.employees = response;
    });
  }
  view(index) {
    this.selectedBalance = this.results[index];
    this.isView = true;
  }

  save(data) {
    // if (!data.valid) {
    //   alertify.error('An error occured, please try again!');
    //   return;
    // } 

    // this.selectedBalance['weights'] = this.weights; 
    this.selectedBalance['deviation'] = 0;
    this.service.post('calibration/balance.php?type=saveCalibration', JSON.stringify(this.selectedBalance)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Balance Calibration Records Saved Successfully');
        this.isView = false;
        this.getPendingCalibration();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
  cacluation(index) {
    // let weights =  this.selectedBalance['weights'];
    // let weight = weights[index];
    // let diff = +weight['standard_weight'] - +weight['display'];
    // weight['variation'] = diff;
    // weight['variation_per'] = +parseFloat(((diff * 100) / +weight['standard_weight']) + '').toFixed(2);
    // weights[index] = weight;
    // this.selectedBalance['weights'] = weights;  
    let weights =  this.trolly_dtl;
    let weight = weights[index];
    // let diff = +weight['weight_description'] - +weight['display'];
    weight['variation'] = +weight['weight_description'] - +weight['display'];
    weight['variation_per'] = +parseFloat(((weight['variation'] * 100) / +weight['weight_description']) + '').toFixed(2);
    weights[index] = weight;
    this.selectedBalance['weights'] = weights;  
  }
  number(value){
    if (isNaN(value)){
      alertify.error('Number Only');
      return false;
    }

  }
  
  
}
