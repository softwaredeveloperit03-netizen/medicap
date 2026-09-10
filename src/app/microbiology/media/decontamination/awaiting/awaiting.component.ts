import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;  

@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css']
})
export class AwaitingComponent implements OnInit {
  requests;
  isView = false;
  selectedResult = [];

  constructor(private service : DataAccessService) {}

  ngOnInit(): void {
    this.getPendingDecontaminationRequests();
  }

  getPendingDecontaminationRequests(){
    this.service.get('microbiology/media.php?type=getPendingDecontaminationRequests').subscribe(response =>{
        this.requests = response;
    });
  }

  view(index){
    this.selectedResult = this.requests[index];
    this.isView =true;
  }

  save(data){
    if(!data.valid){
      alertify.error("All Fields Are Required !!");
      return;
    }
    let temp = data.value;
    temp['id'] = this.selectedResult['id'];
    this.service.post('microbiology/media.php?type=saveObseravation24',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success'){
        this.getPendingDecontaminationRequests();
        this.isView = false;
        data.resetForm();
        alertify.success("Record Save Successfully !!");
      }
      else{
        alertify.error("Error to save Record !!");
      }
    });
  }
  save48(data){
    if(!data.valid){
      alertify.error("All Fields Are Required !!");
      return;
    }
    let temp = data.value;
    temp['id'] = this.selectedResult['id'];
    this.service.post('microbiology/media.php?type=saveObseravation48',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success'){
        this.getPendingDecontaminationRequests();
        this.isView = false;
        data.resetForm();
        alertify.success("Record Save Successfully !!");
      }
      else{
        alertify.error("Error to save Record !!");
      }
    });
  }

}
