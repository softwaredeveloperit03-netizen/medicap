import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  
  results;
  selectresult;
 
 
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPendingVolumetricMaster();
  }
  getPendingVolumetricMaster(){
    this.service.get('qc/volumetric.php?type=getPendingVolumetricMaster').subscribe(response =>{
    this.results = response;
    });
  }

  updateVolume(status, id) {
    this.service.get('qc/volumetric.php?type=updateVolumetricSolution&status='+status+'&id='+id).subscribe(response =>{
      if(response['status']=='success'){
        alertify.success("Recored Uptadetd  Succesfully");
        this.getPendingVolumetricMaster();
      }else{
        alertify.error("Failed to updated a Recored");
      }
    });
  }

}
