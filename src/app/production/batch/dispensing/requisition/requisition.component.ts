import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-requisition',
  templateUrl: './requisition.component.html',
  styleUrls: ['./requisition.component.css']
})
export class RequisitionComponent implements OnInit {
 
  isView = false;
  results;

  selectedResult = [];

  calculate_api = '';
  lineClearance =[
    { "Description":"Name of the previous product" ,"Observation": "" ,"check_by" :"" ,"verify_date":""},
    {"Description":"Medicap Lot No" ,"Observation": "" ,"check_by" :"" ,"verify_date":""},
    {"Description":"Temperature of the room" ,"Observation": "" ,"check_by" :"" ,"verify_date":""},
    {"Description":"Relative humidity" ,"Observation": "" ,"check_by" :"" ,"verify_date":""},
    {"Description":"Pressure differential of the area" ,"Observation": "" ,"check_by" :"" ,"verify_date":""},
    {"Description":"Cleanliness of area" ,"Observation": "" ,"check_by" :"" ,"verify_date":""},
    {"Description":"Removal of previous products" ,"Observation": "" ,"check_by" :"" ,"verify_date":""},
    {"Description":"Verification of balance" ,"Observation": "" ,"check_by" :"" ,"verify_date":""},
    {"Description":"Cleanliness of dispensing booth& dispensing tools" ,"Observation": "" ,"check_by" :"" ,"verify_date":""},
    {"Description":"Is there are approved label affix on a drum of raw material" ,"Observation": "" ,"check_by" :"" ,"verify_date":""},
    {"Description":"Check all the respective" ,"Observation": "" ,"check_by" :"" ,"verify_date":""},
    {"Description":"Relevant status labels affixed for the proceeding batch" ,"Observation": "" ,"check_by" :"" ,"verify_date":""},
    {"Description":"Cleaning Of UV lamp" ,"Observation": "" ,"check_by" :"" ,"verify_date":""},
    {"Description":"Exterior Of lamp" ,"Observation": "" ,"check_by" :"" ,"verify_date":""},
  ];

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingDispensings();
  }

  getPendingDispensings() {
    this.service.get('production/dispensing.php?type=getPendingDispensings').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  sendRequisition(){

    for(let i=0; i< this.lineClearance.length; i++){
      let line = this.lineClearance[i];
      if(line['Observation'] == ''){
        alert('All Feilds are required');
        return;
      }
    }

    let temp={};
    temp['product_code']=this.selectedResult['product_code'];
    temp['bmr_no']=this.selectedResult['std_bmr_no'];
    temp['batch_no']=this.selectedResult['batch_no'];
    temp['batch_size']=this.selectedResult['batch_size'];
    temp['bmr_no'] = this.selectedResult['bmr_no'];
    temp['materials']=this.selectedResult['materials'];
    temp['checkpoints']=this.lineClearance;
    temp['calculate_api'] = this.calculate_api;

    this.service.post('production/dispensing.php?type=sendRequisition',JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success'){
        alert('Requistion Send Successfully!');
        this.isView = false;
        this.getPendingDispensings();
      }else{
        alert('Failed an error occured,Please try again!');
      }
    });
  }

}
