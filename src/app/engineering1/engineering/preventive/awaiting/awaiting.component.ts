import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css']
})
export class AwaitingComponent implements OnInit {
  results;
  isView = false;
  selectedResult = [];
  selectedEquip = [];
  checklist=[];
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPendingPreventiveMaintenance();
  }
 
  getPendingPreventiveMaintenance(){
    this.service.get('engineering/preventive.php?type=getPendingPreventives').subscribe(response=>{
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }
  
  save(data){ 
    if(!data.valid){
      alertify.error('All fields are required!');
      return;
    }
    let temp=data.value;
    temp['equipment_code']=this.selectedResult['equipment_code'];
    temp['frequency']=this.selectedResult['frequency'];
    temp['checklist']=this.selectedResult['checklist'];
    this.service.post('engineering/preventive.php?type=savePreventiveMaintenance',JSON.stringify(temp)).subscribe(response=>{
      if(response['status'] == 'success'){
        alertify.success("Data Saved Successfully!");
        this.isView = false;
        data.reset();
        this.selectedResult=[];
        this.getPendingPreventiveMaintenance();
      }else{
        alertify.error("Failed an error occured,Please try again!");
      }
    });
  }


}
