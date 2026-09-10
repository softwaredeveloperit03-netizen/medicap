import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  
  results: any = [];
  selectedResult = [];
  isView = false;
  parametersList=[];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingOOC();
  }

  getPendingOOC() {
    this.service.get('qc/calibration.php?type=getPendingOOC').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }
 
  add(data){
    if(!data.valid){
      alertify.error("All Fields required");
      return;
    }
    let temp = data.value;
    this.parametersList[this.parametersList.length] = temp;
    data.resetForm();
  }
 
  del(index) {
    this.parametersList.splice(index, 1);
  }
  save(data){
    if(!data.valid){
      alertify.error("All Fields Are Required !!");
      return;
    }
    let temp = data.value;
    temp['calibration_parameter'] = this.parametersList;
    this.service.post('qc/calibration.php?type=saveOOC',JSON.stringify(temp)).subscribe(response =>{
      if(response['status']== 'success'){
        this.isView = false;
        this.getPendingOOC();
        alertify.success("Records Save Successfully !!!");
      }else{
        alertify.error("Error to save records !!")
      }
    });
  }
}
