import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-checklist-approval',
  templateUrl: './checklist-approval.component.html',
  styleUrls: ['./checklist-approval.component.css']
})
export class ChecklistApprovalComponent implements OnInit {

  isView = false;
  results;
  selectedResult = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getInprocessAreaChecklists();
  }

  getInprocessAreaChecklists() {
    this.service.get('qa/clearance.php?type=getInprocessAreaChecklists').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  update(status) {
    this.service.get('qa/clearance.php?type=updateAreaChecklist&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Checklist updated successfully');
        this.isView = false;
        this.getInprocessAreaChecklists();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
