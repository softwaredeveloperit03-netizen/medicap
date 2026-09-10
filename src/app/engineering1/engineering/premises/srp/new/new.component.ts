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

  checkpoints = [{"CheckPoints": "To check the street light / overhead light in process area at all floor.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check the paint for structure, utility lines and solvent lines etc.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check the grating condition , at every floors.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "Check for the rust on the distillation column","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "Check for the insulation condition on the distillation column.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "Check for the rust on the storage tank / reactor.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "Check for the insulation condition on storage tank / reactor.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "Check for the rust on the utility pipe lines.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "Check for the insulation condition on utility pipe lines.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "Check the condition of level indicator at storage tank .","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "Check the visibility of calibration tag of all measuring devices.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check the leakage / damage in storage tanks and pipe lines.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check the ladder for material movements and man movements.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check unwanted item are not store on floor.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check for chocking of any drainage line.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check the earthling is available for electrical equipment .","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check the switch boards for electrical supply & screw/nuts fitting.","observation": "", "compliance": "", "completion_date": ""}];
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
    this.service.post('engineering/premises.php?type=saveSolventRecovery',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=="success"){
        this.router.navigate(['/engineering/premises/srp'])
        alertify.success("Saved Successfully!");
      }else{
        alertify.error("Failed an error occured!")
      }
    });
  }


}
