import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-activity',
  templateUrl: './activity.component.html',
  styleUrls: ['./activity.component.css']
})
export class ActivityComponent implements OnInit {

  isView = false;
  results;
  remark = '';
  selectedResult = [];
  operator;
  employees;
  dispensing_start='';
  dispensing_stop='';
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getDispensingActivities();
    this.getOperators();
    this.getStoreWorkers();
  }
  
  
  getStoreWorkers() {
    this.service.get('employee.php?type=getStoreWorkers').subscribe(response => {
      this.employees = response;
    });
  }

  getOperators(){
    this.service.get('common.php?type=getOperators').subscribe(response => {
      this.operator = response;
    });
  }

  getDispensingActivities() {
    this.service.get('store/solvent.php?type=getAcceptedRequisitions').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    console.log(this.selectedResult);
    this.isView = true;
  }
  save() {
    /* if(!data.valid){
      alertify.error("All Fields are required !!");
      return;
    } */
    /*  this.selectedResult['dispensing_from']= this.dispensing_start ;
     this.selectedResult['dispensing_to']=  this.dispensing_stop ; 
     this.selectedResult['remark']= this.remark; */
    this.service.post('store/solvent.php?type=saveDispensingActivity',JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('data save successfuly');
        this.getDispensingActivities();
        this.isView = false;
      } else {
        alertify.error('some error occured!');
      }
    });
  }
}
