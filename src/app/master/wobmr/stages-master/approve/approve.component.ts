import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
@Component({
  selector: 'app-approve',
  templateUrl: './approve.component.html',
  styleUrls: ['./approve.component.css']
})
export class ApproveComponent implements OnInit {

  isview = false;
  results;
  selectedResult;
  stages;
  fg_sub_materials;
  dosage_form;
  plant_type = 'Formulation';

  constructor(private service: DataAccessService, public route: ActivatedRoute) {}

  ngOnInit(): void {
    this.plant_type = this.service.getPlantConfigFields('plant_type');

    this.getStages();
     
  }

 

  getStages() {
    this.service.get("production/stage.php?type=get_iqpc_stagesForApproval").subscribe((response) => {
        this.results = response;
      });
  }
  

  view(val) {
    this.selectedResult = this.results[val];
    this.stages =  this.selectedResult['stages_test'];
    this.isview = true;
  }


  UpdateStatus(status) {    
    let temp={};
 
    this.service.post('production/stage.php?type=UpdateStageStatus&status='+status+'&id='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
         alert('Stages Revised Successfully');
         this.isview=false;
         this.getStages();
       } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }


 

 
}
