import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;
@Component({
  selector: 'app-dailypending',
  templateUrl: './dailypending.component.html',
  styleUrls: ['./dailypending.component.css']
})
export class DailypendingComponent implements OnInit {

  constructor(private service:DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getSECTIONS();

  }
  sections;
  getSECTIONS() {
    this.service.get('common.php?type=getSECTIONS&department1=Quality Control').subscribe(response => {
      this.sections = response;
    });
  }

  ids
  getbal_id(value){
    this.service.get('common.php?type=getbal_id&department1=Quality Control&location='+value).subscribe(response => {
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
    this.service.get('master/equipment.php?type=get_std_weight&department1=Quality Control&id=' + this.selectedResult['id'] + '&equipment_code=' + equip_code).subscribe(response => {
      this.std_wts = response;
    }); 
    // calib_log(){
      this.service.get('qc/raw.php?type=get_standard_weights&department1=Quality Control&id=' + this.selectedResult['id'] + '&equipment_code=' + equip_code).subscribe(response => {
        this.calib_log = response;
      }); 
    // }
  }


  comp_check=[];
  actual_wt = []; 
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

location
equipment_id
date
result
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
  

}
