import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;


@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  results: any[] = [];
  loading = false;
  isView = false;
  materials: any[] = [];
  selectedResult: any = {};

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingIndends();
  }

  getPendingIndends() {
    this.loading = true;
    this.service.get('purchase/indent.php?type=getIndentForApprovalPlantHead&status=TO_PlantHead').subscribe({
      next: (response: any) => {
        this.results = Array.isArray(response) ? response : [];
        this.loading = false;
      },
      error: () => {
        this.results = [];
        this.loading = false;
        alertify.error('Failed to load pending requisitions');
      }
    });
  }

  view(data: any) {
    this.selectedResult = data || {};
    this.materials = (this.selectedResult && this.selectedResult['materials']) || [];
    this.isView = true;
  }

  approveIndend(status: string) {
    if (!this.materials || this.materials.length === 0) {
      alertify.error('No materials to approve');
      return;
    }

    const temp: any = {};
    temp['materials'] = this.materials;

    this.service.post('purchase/indent.php?type=approveIndentFromplandHead&status=' + status, JSON.stringify(temp)).subscribe({
      next: (response: any) => {
        if (response && response['status'] === 'success') {
          alertify.success('Record updated successfully');
          this.isView = false;
          this.getPendingIndends();
        } else {
          alertify.error(response?.message || response?.status || 'Failed: An error occured, please try again!');
        }
      },
      error: () => alertify.error('Failed: An error occured, please try again!')
    });
  }

}
