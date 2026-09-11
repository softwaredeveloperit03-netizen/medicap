import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-linemaster-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {
  results: any[] = [];
  selectedResult: any;
  isview = false;
  isequip = false;
  equipmentList: any[] = [];
  isStageViewModal = false;
  selectedStages: any[] = [];

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getPending();
  }

  getPending(): void {
    this.service.get('bmr/process.php?type=getLinemasterForChecking').subscribe((response: any) => {
      this.results = Array.isArray(response) ? response : [];
    });
  }

  view(index: number): void {
    this.selectedResult = this.results[index];
    this.isview = true;
  }

  AddEqup(index: number): void {
    this.equipmentList = this.results[index]['equipmentList'] || [];
    this.isequip = true;
  }

  openStageViewModal(index: number): void {
    this.selectedStages = this.results[index]['stages'] || [];
    this.isStageViewModal = true;
  }

  UpdateStatus(status: string): void {
    if (!this.selectedResult || !this.selectedResult.id) {
      return;
    }
    this.service
      .post(
        'bmr/process.php?type=updateLinemasterStatus&status=' + status + '&id=' + this.selectedResult.id,
        JSON.stringify({})
      )
      .subscribe((response: any) => {
        if (response && response['status'] === 'success') {
          alertify.success(status === 'Checked' ? 'Line checked successfully' : 'Line rejected');
          this.isview = false;
          this.getPending();
        } else {
          alertify.error('Failed: An error occurred, please try again!');
        }
      });
  }
}
