import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  checkpoints = [{"CheckPoints": "To check the overhead light in building area.", "observation": "", "compliance": "", "completion_date": ""},{"CheckPoints": "To check the operation of all doors & availability of door seals.", "observation": "", "compliance": "", "completion_date": ""},{"CheckPoints": "To check the wall, flooring and false ceiling for any cracks, deform or damage.", "observation": "", "compliance": "", "completion_date": ""},{"CheckPoints": "To check the paint for walls, ceiling windows, doors, utility lines for peeling.", "observation": "", "compliance": "", "completion_date": ""},{"CheckPoints": "To check the switch boards for electrical supply & screw/nuts fitting.", "observation": "", "compliance": "", "completion_date": ""},{"CheckPoints": "To check the condition of the roof sheets.", "observation": "", "compliance": "", "completion_date": ""},{"CheckPoints": "To check the leakage / damages in residue storage tanks and pipe lines.", "observation": "", "compliance": "", "completion_date": ""},{"CheckPoints": "To check for chocking of any drainage line.", "observation": "", "compliance": "", "completion_date": ""},{"CheckPoints": "To check the condition of fire extinguishers.", "observation": "", "compliance": "", "completion_date": ""},{"CheckPoints": "To check the ladder for material movements and man movements.", "observation": "", "compliance": "", "completion_date": ""}];
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
    this.service.post('engineering/premises.php?type=saveETP',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=="success"){
        this.router.navigate(['/engineering/premises/etp'])
        alertify.success("Saved Successfully!");
      }else{
        alertify.error("Failed an error occured!")
      }
    });
  }


}
