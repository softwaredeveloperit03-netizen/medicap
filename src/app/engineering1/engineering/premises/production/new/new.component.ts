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

  checkpoints = [{"CheckPoints": "To check the overhead light in production / process area.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check the paint for walls, ceiling windows, doors, door seals, utility lines and solvent lines etc.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check the wall, flooring and false ceiling for any cracks, deform or damage.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check the ladder for material movements and man movements.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check the ventilation system at all floors.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check the switch boards for electrical supply & screw/nuts fitting.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "Electrical fittings & cables shall be concealed.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check the leakage in storage tanks and pipe lines.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check for chocking of any drainage line.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check unwanted engineering material is not store on place / floor.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check street light outside of the plant.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check the door seal integrity.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "Push/Pull plate Shall be provided on all doors.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "All door hinges should be free from rusting.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "Check for the rust on all equipment.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "Check for the insulation condition on equipment.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "Check for the rust on utility line.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "Check for the the insulation condition on utility line.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check the condition of coving in all area of powder processing area.","observation": "", "compliance": "", "completion_date": ""}];
  

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
    this.service.post('engineering/premises.php?type=saveProductionPlant',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=="success"){
        this.router.navigate(['/engineering/premises/production'])
        alertify.success("Saved Successfully!");
      }else{
        alertify.error("Failed an error occured!")
      }
    });
  }

}
