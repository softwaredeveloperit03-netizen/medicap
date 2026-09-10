import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, Params, } from '@angular/router';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-daily',
  templateUrl: './daily.component.html',
  styleUrls: ['./daily.component.css']
})
export class DailyComponent implements OnInit {
    section_name;

  constructor(private service:DataAccessService, private router: Router) { 
    this.loggedInDept = localStorage.getItem('department');

  }
  isView=false;
  department
date;
Location;
capacity;
bal_name;
bal_id;                               
from;
to;
result=[];


  ngOnInit() {
    this.getSECTIONS();
    this.get_rights();
  }

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {this.service.get('hr/employee.php?type=getrights&emp_id=' 
    +localStorage.getItem('emp_id') +'&dep_name=' +this.loggedInDept     
       )
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
      });
  }



  sections;   
  equipment_id;   
  ids;   

  getSECTIONS() {
    this.service.get('common.php?type=getSECTIONS&department1=Store').subscribe(response => {
      this.sections = response;
    });
  }

  getbal_id(value){
    this.service.get('common.php?type=getbal_id&department1=Store&location='+value).subscribe(response => {
      this.ids = response;
    }); 
  }
  selectedResult=[];
  std_wts :any= []; 
  equip_code;
  calib_log;
  get_std_weight(index){
    this.selectedResult = this.ids[index-1];

    let equip_code = this.selectedResult['equipment_code'];
    console.log(this.selectedResult['id']);
    this.service.get('master/equipment.php?type=get_std_weight&department1=Store&id=' + this.selectedResult['id'] + '&equipment_code=' + equip_code).subscribe(response => {
      this.std_wts = response;
    }); 
    // calib_log(){
      this.service.get('qc/raw.php?type=get_standard_weights&department1=Store&id=' + this.selectedResult['id'] + '&equipment_code=' + equip_code).subscribe(response => {
        this.calib_log = response;
      }); 
    // }
  }
// calib_log(){
//   this.service.get('master/equipment.php?type=get_standard_weights&department1=Quality Control&id='+this.selectedResult['id']).subscribe(response => {
//     this.std_wts = response;
//   }); 
// }


actual_wt = []; // Initialize an empty array to store actual_wt values
isCompliant = true; // Assume compliance until proven otherwise

// checkCompliance() {
//     this.isCompliant = true; // Assume compliance until proven otherwise

//     for (let i = 0; i < this.std_wts.length; i++) {
//         if (
//             this.actual_wt[i] < this.std_wts[i].acceptance_criteria_from &&
//             this.actual_wt[i] > this.std_wts[i].acceptance_criteria_to
//         ) {
//             this.isCompliant = false; // Set to non-compliant if any condition fails
//             break; // Exit the loop as non-compliance is determined
//         }
//     }
// }
checkCompliance() {
  const tolerance = 0.001; // Define a tolerance value for comparisons

  this.isCompliant = true; // Assume compliance until proven otherwise

  for (let i = 0; i < this.std_wts.length; i++) {
      const actualWt = parseFloat(this.actual_wt[i]); // Convert the input to a floating-point number
      const lowerBound = parseFloat(this.std_wts[i].acceptance_criteria_from);
      const upperBound = parseFloat(this.std_wts[i].acceptance_criteria_to);

      if (
          isNaN(actualWt) || // Check if actualWt is not a valid number
          actualWt < lowerBound  || // Apply tolerance to lower bound
          actualWt > upperBound  // Apply tolerance to upper bound
      ) {
          this.isCompliant = false; // Set to non-compliant if any condition fails
          break; // Exit the loop as non-compliance is determined
      }
  }
}


comp_check=[];

save_comp(data){
  if (!data.valid) {
    alert('All fields are required');
    return;
  }
    let temp = data.value;

    temp['actual_qt1'] = [...this.actual_wt]; // Store actual_wt in "actual_qt1" property
    this.comp_check.push(temp); // Push the temp object into the comp_check array
    console.log(this.comp_check);
    data.resetForm();
}
 
downloadReport(){
  
  this.service.open('qc/raw.php?type=downloaddaily&department1=Store&id=' + this.selectedResult['id'] + '&equipment_code=' + this.equipment_id + '&location=' + this.sections + '&section_name=' + this.section_name);
}
del_comp_check(index) {
  this.comp_check.splice(index, 1);
}


location
  saveCalibration(data) {
    console.log(data.value);
    if (!data.valid) {
    alert('All fields are required');
    return;
  }
    let temp = data.value;
    
    temp['location']=this.location;
    temp['equipment_id']=this.equipment_id
    temp['date']=this.date
    temp['comp_check1'] =this.comp_check[0]; // Store actual_wt in "actual_qt1" property.

    // Push the temp object into the comp_check array
    console.log(this.comp_check);
    data.resetForm();
    this.service.post('qc/raw.php?type=save_calibration', JSON.stringify(temp)).subscribe(response => 
    {
      if (response['status'] == 'success') {
        alert('Weight Saved Successfully');
        this.result = [];
        this.router.navigate(['/master/weight/new']);
      } else {
        console.log(response);
        alert('Failed: An error occured, please try again!');
      }
    }); 
  }
  delData(index) {
    this.result.splice(index, 1);
  }
  new(){
    this.isView = true;
    this.location='';
this.equipment_id='';
  }
  addData(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    let tempData = [];
    this.result[this.result.length] = temp;
    console.log(this.result);
    data.resetForm();
  }

}
