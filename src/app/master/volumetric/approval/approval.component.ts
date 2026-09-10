import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
// export class ApprovalComponent implements OnInit {
  
//   results;
//   selectresult;
 
 
//   constructor(private service:DataAccessService) { }

//   ngOnInit() {
//     this.getPendingVolumetricMaster();
//   }
//   getPendingVolumetricMaster(){
//     this.service.get('qc/volumetric.php?type=getPendingVolumetricMaster').subscribe(response =>{
//     this.results = response;
//     });
//   }

//   updateVolume(status, id) {
//     this.service.get('qc/volumetric.php?type=updateVolumetricSolution&status='+status+'&id='+id).subscribe(response =>{
//       if(response['status']=='success'){
//         alertify.success("Recored Uptadetd  Succesfully");
//         this.getPendingVolumetricMaster();
//       }else{
//         alertify.error("Failed to updated a Recored");
//       }
//     });
//   }

// }


export class ApprovalComponent implements OnInit {

  results: any[] = [];
  selectresult;


  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingVolumetricMaster();
  }
  getPendingVolumetricMaster() {
    this.service.get('qc/volumetric.php?type=getPendingVolumetricMaster').subscribe(response => {
      this.results = Array.isArray(response) ? response : [];
    });
  }

  getActionLabel(row: any): string {
    const action = (row?.pending_action || '').toLowerCase();
    if (action === 'update') return 'Update';
    if (action === 'delete') return 'Delete';
    if (action === 'new') return 'New';
    return 'New';
  }

  getRequestedDetails(row: any): string {
    const action = (row?.pending_action || '').toLowerCase();
    if (action === 'update') {
      return (row.pending_solution_name || row.solution_name) + ' | ' +
        (row.pending_percentage || '-') + ' | ' +
        (row.pending_strength || '-') + ' ' + (row.pending_unit || '-') + ' | ' +
        (row.pending_standard_type || row.standard_type || '-');
    }
    if (action === 'delete') {
      return row.solution_name + ' | Delete requested';
    }
    return row.solution_name + ' | ' + (row.percentage || '-') + ' | ' + (row.strength || '-') + ' ' + (row.unit || '-') + ' | ' + (row.standard_type || '-');
  }

  updateVolume(action, id) {
    this.service.get('qc/volumetric.php?type=updateVolumetricSolution&action=' + action + '&id=' + id).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success("Record Updated Successfully");
        this.getPendingVolumetricMaster();
      } else {
        alertify.error("Failed to update record");
      }
    });
  }

}
