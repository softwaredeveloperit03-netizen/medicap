import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  checkpoints = [{"CheckPoints": "To check the condition of roads.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check the paints condition of compound walls.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check for chocking of rain water drainage line.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check street light out side of the plant and factory premises.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check the overhead light near to security building.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check the door operating of main gate.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check the emergency contact no is available for fire, police and hospital.","observation": "", "compliance": "", "completion_date": ""}];
  
  constructor(private service: DataAccessService,private router:Router) { 
   
  }

  ngOnInit(): void {
  }
  saveData(data){
    if(!data.valid){
      alertify.error("all fields are required!");
      return;
    }
    let temp=[];
    temp=data.value;
    temp['checkpoints']=this.checkpoints;
    this.service.post('engineering/premises.php?type=saveSecurity',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=="success"){
        this.router.navigate(['/engineering/premises/security'])
        alertify.success("Saved Successfully!");
      }else{
        alertify.error("Failed an error occured!")
      }
    });
  }


}
