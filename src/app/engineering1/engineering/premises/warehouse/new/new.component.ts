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

  checkpoints = [{"CheckPoints": "To check the overhead light in building area.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check the operation of all doors & availability of door seals.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "All door hinges should be free from rusting.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "Push/Pull plate Shall be provided on all doors","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check the wall, flooring and false ceiling for any cracks, deform or damage.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check the paint for walls, ceiling windows, doors, utility lines for peeling.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check the switch boards for electrical supply & screw/nuts fitting.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "Electrical fittings & cables shall be concealed.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check the switch boards for electrical supply.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check the fire extinguisher are working condition .","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check the ventilation system at all floors.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check the condition of the roof sheets.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check unwanted item are not store on floor.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check street light outside of the plant.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check the lighting facility of sampling booth and dispensing room at warehouse.","observation": "", "compliance": "", "completion_date": ""},
  {"CheckPoints": "To check the door operation of sampling booth and dispensing room at warehouse  .","observation": "", "compliance": "", "completion_date": ""}];

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
    this.service.post('engineering/premises.php?type=saveRawMaterial',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=="success"){
        this.router.navigate(['/engineering/premises/warehouse'])
        alertify.success("Saved Successfully!");
      }else{
        alertify.error("Failed an error occured!")
      }
    });
  }

}
