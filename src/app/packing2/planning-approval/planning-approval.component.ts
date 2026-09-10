import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-planning-approval',
  templateUrl: './planning-approval.component.html',
  styleUrls: ['./planning-approval.component.css'],
  providers: [DatePipe]
})
export class PlanningApprovalComponent implements OnInit {

  results;
  isView = false;
  materials = [];
  selectedResult;
  work_order_lots=[];
  pack_sizes=[];
  work_order_raw_materials = [];
  work_order_packing_materials = [];
  plant_id;
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getPlans();
    this.plant_id = this.service.getPlantConfigFields("plant_id")
  }
  getPlans() {
    this.pack_sizes=[];
    this.service.get('production/workorder.php?type=get_pm_workorders_for_approval').subscribe(response => {
      this.results = response;
    
    });
  }
  view(idx) {
    this.selectedResult = this.results[idx];
    this.pack_sizes = [];
    this.materials = this.results[idx]['materials'];
    this.pack_sizes = this.results[idx]['pack_sizes'];
    

    this.isView = true;
  }
  saveData(status){
    this.service.get('production/workorder.php?type=approve_work_order&id='+this.selectedResult['id']+'&status='+status).subscribe(response => {
      if (response['status'] == "success") {
        alertify.success('Status has been saved successfully');        
        this.router.navigate(['/packing/planning-approval']);
      } else {
        alertify.error('Failed: '+response['status']);
      }
    });
  }
  saveData_saipro(status){
    this.service.get('production/workorder.php?type=approve_work_order_saipro&id='+this.selectedResult['id']+'&status='+status).subscribe(response => {
      if (response['status'] == "success") {
        alertify.success('Status has been saved successfully');        
        this.router.navigate(['/packing/planning-approval']);
      } else {
        alertify.error('Failed: '+response['status']);
      }
    });
  }

}
