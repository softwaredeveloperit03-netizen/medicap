import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-operation',
  templateUrl: './operation.component.html',
  styleUrls: ['./operation.component.css']
})
export class OperationComponent implements OnInit {

  plants;

  equipment_code='';
  plant_name='';
  tests;
  constructor(private service:DataAccessService) { 
    this.service.observablePlant.subscribe(response=>{
      this.plants=response;
    });
  
  }

  ngOnInit(): void {
    this.getTest();

  }
  getTest(){
    this.service.get('engineering/gauss.php?type=getTest').subscribe(response=>{
      this.tests=response;
    });
  }

  add(data){
    if(!data.valid){
      alertify.success('Data Save Successfuly');
      return;
    }
    let temp=data.value;
    temp['equipment_code'] =this.equipment_code;
    temp['plant_name'] =this.plant_name;
    this.service.post('engineering/gauss.php?type=saveTestReport',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('Data Save successfuly');
        data.reset();
        this.getTest();
      }else{
        alertify.success('some error occured!');
      }
    });
  }

  download(){
    this.service.open('engineering/gauss.php?type=downloadTest');
  }
 
}
