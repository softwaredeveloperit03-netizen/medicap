import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css'],
  providers: [DatePipe]
})
export class ApprovalComponent implements OnInit {
  results;
  isView = false;
  materials = [];
  selectedResult;
  work_order_raw_materials = [];
  work_order_packing_materials = [];
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getPlans()
  }
  getPlans() {
    this.service.get('production/workorder.php?type=get_workorders_for_approval').subscribe(response => {
      this.results = response;
    });
  }
  view(idx) {
    this.selectedResult = this.results[idx];
    this.materials = this.results[idx]['materials'];
    this.work_order_raw_materials = [];
    this.work_order_packing_materials = [];
    for (let x = 0; x < this.materials.length; x++) {
      if (this.materials[x]['material_type'] == 'Packing Material') {
        this.work_order_packing_materials.push(this.materials[x]);
      } else {
        this.work_order_raw_materials.push(this.materials[x]);
      }
    }

    this.isView = true;
  }
  saveData(status){
    this.service.get('production/workorder.php?type=approve_work_order&id='+this.selectedResult['id']+'&status='+status).subscribe(response => {
      if (response['status'] == "success") {
        alertify.success('Status has been saved successfully');        
        this.router.navigate(['/production/batch-planning']);
      } else {
        alertify.error('Failed: '+response['status']);
      }
    });
  }

}
